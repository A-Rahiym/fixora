<?php

namespace App\Services;

use App\Enums\RepairStatus;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single writer for repair status changes.
 *
 * Every transition updates `repairs.status` and appends one immutable
 * `repair_status_history` row atomically. Steps may be skipped, but
 * leaving a terminal state (collected, unrepairable, cancelled) is
 * rejected, as is a no-op transition to the same status.
 */
class RepairStatusService
{
    public function transition(Repair $repair, RepairStatus $newStatus, User $user, ?string $note = null): Repair
    {
        $current = RepairStatus::from($repair->status);

        if ($current === $newStatus) {
            throw ValidationException::withMessages([
                'status' => 'Repair is already in this status.',
            ]);
        }

        if ($current->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$current->value}.",
            ]);
        }

        return DB::transaction(function () use ($repair, $newStatus, $user, $note) {
            $repair->update(['status' => $newStatus->value]);

            $repair->history()->create([
                'status' => $newStatus->value,
                'changed_by' => $user->id,
                'note' => $note,
            ]);

            return $repair->refresh();
        });
    }
}
