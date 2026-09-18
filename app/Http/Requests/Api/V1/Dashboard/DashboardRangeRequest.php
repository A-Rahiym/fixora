<?php

namespace App\Http\Requests\Api\V1\Dashboard;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Shared range filter for dashboard endpoints.
 *
 * `from`/`to` default to the last 7 days (inclusive). `limit` is only
 * consumed by the recent-activity endpoint.
 */
class DashboardRangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }

    /**
     * Inclusive [start, end] range as date-only bounds (app timezone).
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function range(): array
    {
        $to = $this->date('to') ?? Carbon::today();
        $from = $this->date('from') ?? $to->copy()->subDays(6);

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * The equivalent-length range immediately before the requested one,
     * used for totals and trend comparisons.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function previousRange(): array
    {
        [$from, $to] = $this->range();

        $length = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $previousEnd = $from->copy()->subDay()->endOfDay();

        return [$previousEnd->copy()->subDays($length - 1)->startOfDay(), $previousEnd];
    }

    public function limit(): int
    {
        $limit = $this->integer('limit', 8);

        return max(1, min(25, $limit));
    }
}
