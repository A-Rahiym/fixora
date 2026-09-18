<?php

namespace App\Services;

use App\Enums\RepairStatus;
use App\Models\InventoryItem;
use App\Models\Repair;
use App\Models\RepairDiagnosis;
use App\Models\RepairStatusHistory;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Read-model queries behind the dashboard endpoints.
 *
 * Amounts are ZMW numbers (floats); timestamps are ISO-8601. Metric
 * `value` fields stay preformatted strings — the backend owns display
 * formatting ("12500" vs "1.8d" vs "98%").
 */
class DashboardService
{
    protected const int TECHNICIAN_CAPACITY = 15;

    /**
     * Statuses that count as an active job (everything non-terminal).
     *
     * @return list<string>
     */
    public static function activeStatuses(): array
    {
        return array_values(array_map(
            fn (RepairStatus $status) => $status->value,
            array_filter(RepairStatus::cases(), fn (RepairStatus $status) => ! $status->isTerminal()),
        ));
    }

    /**
     * Raw device category labels for the device-mix chart.
     *
     * @return array<string, string>
     */
    public static function deviceCategoryLabels(): array
    {
        return [
            'phone' => 'Smartphone',
            'laptop' => 'Laptop',
            'console' => 'Console',
            'tv' => 'TV',
            'other' => 'Other',
        ];
    }

    /**
     * @return array{metrics: list<array<string, mixed>>, revenue: array<string, mixed>, device_mix: list<array<string, mixed>>}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to, CarbonInterface $previousFrom, CarbonInterface $previousTo): array
    {
        $activeJobs = Repair::whereIn('status', self::activeStatuses())->count();
        $newInRange = $this->createdBetweenCount($from, $to);
        $newInPrevious = $this->createdBetweenCount($previousFrom, $previousTo);

        $turnaround = $this->averageTurnaroundDays($from, $to);
        $previousTurnaround = $this->averageTurnaroundDays($previousFrom, $previousTo);

        $todayRevenue = (float) Repair::whereDate('collected_at', Carbon::today())->sum('final_cost');

        $revenue = $this->revenueSeries($from, $to);

        $efficiency = $this->efficiencyPct($from, $to);
        $previousEfficiency = $this->efficiencyPct($previousFrom, $previousTo);

        return [
            'metrics' => [
                [
                    'id' => 'active-jobs',
                    'label' => 'Active Jobs',
                    'value' => (string) $activeJobs,
                    'caption' => 'Current repairs in progress',
                    'trend' => $this->trend($newInRange, $newInPrevious),
                ],
                [
                    'id' => 'turnaround',
                    'label' => 'Avg. Turnaround',
                    'value' => number_format($turnaround, 1).'d',
                    'caption' => 'Average repair cycle',
                    'trend' => $this->trend($turnaround, $previousTurnaround),
                ],
                [
                    'id' => 'revenue',
                    'label' => "Today's Revenue",
                    'value' => (string) (int) round($todayRevenue),
                    'caption' => 'Collected today',
                ],
                [
                    'id' => 'efficiency',
                    'label' => 'Efficiency',
                    'value' => $efficiency.'%',
                    'caption' => 'First-time fix rate',
                    'trend' => $this->trend($efficiency, $previousEfficiency, ''),
                ],
            ],
            'revenue' => $revenue,
            'device_mix' => $this->deviceMix(),
        ];
    }

    /**
     * @return array{capacity: array{utilization_pct: int, entries: list<array<string, mixed>>}}
     */
    public function repairPipeline(): array
    {
        $technicianRole = Role::where('name', 'technician')->first();

        $technicians = $technicianRole === null
            ? collect()
            : User::where('is_active', true)
                ->where('role_id', $technicianRole->id)
                ->with('role')
                ->withCount(['assignedRepairs as active_jobs_count' => fn ($query) => $query->whereIn('status', self::activeStatuses())])
                ->orderByDesc('active_jobs_count')
                ->orderBy('name')
                ->get();

        $entries = $technicians->map(fn (User $technician) => [
            'technician' => $technician->name,
            'role' => $technician->role?->label ?? 'Technician',
            'load_pct' => min(100, (int) round(($technician->active_jobs_count / self::TECHNICIAN_CAPACITY) * 100)),
            'jobs' => (int) $technician->active_jobs_count,
        ])->values()->all();

        $totalActive = array_sum(array_column($entries, 'jobs'));
        $capacity = count($entries) * self::TECHNICIAN_CAPACITY;

        return [
            'capacity' => [
                'utilization_pct' => $capacity > 0 ? (int) round(($totalActive / $capacity) * 100) : 0,
                'entries' => $entries,
            ],
        ];
    }

    /**
     * @return array{recent_jobs: list<array<string, mixed>>}
     */
    public function recentActivity(int $limit): array
    {
        $jobs = Repair::with(['customer', 'device'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Repair $repair) => [
                'id' => $repair->id,
                'job_number' => $repair->job_number,
                'customer' => $repair->customer?->name ?? '—',
                'device' => trim(($repair->device?->brand ?? '').' '.($repair->device?->model ?? '')) ?: '—',
                'issue' => $repair->reported_problem,
                'status' => $repair->status,
                'priority' => $repair->priority,
                'created_at' => $repair->created_at?->toISOString(),
            ])
            ->all();

        return ['recent_jobs' => $jobs];
    }

    /**
     * @return array{low_stock_items: list<array<string, mixed>>}
     */
    public function lowStockItems(): array
    {
        $items = InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')
            ->orderBy('quantity')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => (int) $item->quantity,
                'threshold' => (int) $item->low_stock_threshold,
            ])
            ->all();

        return ['low_stock_items' => $items];
    }

    protected function createdBetweenCount(CarbonInterface $from, CarbonInterface $to): int
    {
        return Repair::whereBetween('created_at', [$from, $to])->count();
    }

    protected function averageTurnaroundDays(CarbonInterface $from, CarbonInterface $to): float
    {
        $repairs = Repair::whereBetween('collected_at', [$from, $to])
            ->whereNotNull('collected_at')
            ->get(['created_at', 'collected_at']);

        if ($repairs->isEmpty()) {
            return 0.0;
        }

        $total = $repairs->sum(fn (Repair $repair) => $repair->created_at === null
            ? 0.0
            : round($repair->created_at->diffInSeconds($repair->collected_at) / 86400, 2));

        return round($total / $repairs->count(), 1);
    }

    /**
     * Share of repairs collected in range that never went on hold and
     * needed at most one diagnosis. Empty range reads as 100 (no defects).
     */
    protected function efficiencyPct(CarbonInterface $from, CarbonInterface $to): int
    {
        $collectedIds = Repair::whereBetween('collected_at', [$from, $to])
            ->whereNotNull('collected_at')
            ->pluck('id');

        if ($collectedIds->isEmpty()) {
            return 100;
        }

        $heldIds = RepairStatusHistory::whereIn('repair_id', $collectedIds)
            ->where('status', RepairStatus::OnHold->value)
            ->distinct()
            ->pluck('repair_id');

        $multiDiagnosedIds = RepairDiagnosis::whereIn('repair_id', $collectedIds)
            ->select('repair_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('repair_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('repair_id');

        $clean = $collectedIds->diff($heldIds)->diff($multiDiagnosedIds)->count();

        return (int) round(($clean / $collectedIds->count()) * 100);
    }

    /**
     * @return array{current_total: float, previous_total: float, points: list<array<string, mixed>>}
     */
    protected function revenueSeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $length = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $points = [];
        $currentTotal = 0.0;
        $previousTotal = 0.0;

        for ($day = 0; $day < $length; $day++) {
            $currentDate = $from->copy()->addDays($day);
            $previousDate = $currentDate->copy()->subDays($length);

            $current = (float) Repair::whereDate('collected_at', $currentDate)->sum('final_cost');
            $previous = (float) Repair::whereDate('collected_at', $previousDate)->sum('final_cost');

            $currentTotal += $current;
            $previousTotal += $previous;

            $points[] = [
                'label' => $currentDate->format('D'),
                'current' => round($current, 2),
                'previous' => round($previous, 2),
            ];
        }

        return [
            'current_total' => round($currentTotal, 2),
            'previous_total' => round($previousTotal, 2),
            'points' => $points,
        ];
    }

    /**
     * @return list<array{category: string, count: int, pct: int}>
     */
    protected function deviceMix(): array
    {
        /** @var array<string, int> $counts */
        $counts = Repair::whereIn('repairs.status', self::activeStatuses())
            ->join('devices', 'devices.id', '=', 'repairs.device_id')
            ->selectRaw('devices.category as category, COUNT(*) as aggregate')
            ->groupBy('devices.category')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'category')
            ->map(fn ($count) => (int) $count)
            ->all();

        $total = array_sum($counts);
        $labels = self::deviceCategoryLabels();

        $mix = [];
        foreach ($counts as $category => $count) {
            $mix[] = [
                'category' => $labels[$category] ?? ucfirst((string) $category),
                'count' => $count,
                'pct' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $mix;
    }

    /**
     * @return array{direction: string, text: string}
     */
    protected function trend(float|int $current, float|int $previous, string $suffix = '%'): array
    {
        if ($previous == 0) {
            $pct = $current > 0 ? 100 : 0;
        } else {
            $pct = (($current - $previous) / abs($previous)) * 100;
        }

        $rounded = (int) round($pct);

        return [
            'direction' => $rounded >= 0 ? 'up' : 'down',
            'text' => sprintf('%+d%s', $rounded, $suffix),
        ];
    }
}
