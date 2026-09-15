<?php

namespace App\Enums;

/**
 * Repair lifecycle statuses (database-design §4, backend-plan Phase 3).
 *
 * Stored as VARCHAR and validated at the FormRequest layer so sqlite
 * test runs stay portable. Progression is mostly linear but steps may
 * be skipped — only terminal-state exits are blocked (see
 * RepairStatusService).
 */
enum RepairStatus: string
{
    case Received = 'received';
    case Diagnosing = 'diagnosing';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case InRepair = 'in_repair';
    case QualityCheck = 'quality_check';
    case ReadyForCollection = 'ready_for_collection';
    case Collected = 'collected';
    case OnHold = 'on_hold';
    case Unrepairable = 'unrepairable';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Collected, self::Unrepairable, self::Cancelled], true);
    }
}
