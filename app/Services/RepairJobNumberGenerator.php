<?php

namespace App\Services;

use App\Models\Repair;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe REP-{n} job numbers (backend-plan Phase 3).
 *
 * Uses the Postgres `repair_job_seq` sequence (not COUNT(*)) so concurrent
 * creates never collide. On non-pgsql connections (sqlite test runs) it
 * falls back to max(id)+1000 inside the caller's transaction.
 */
class RepairJobNumberGenerator
{
    public function next(): string
    {
        if (DB::getDriverName() === 'pgsql') {
            $next = DB::selectOne("SELECT nextval('repair_job_seq') AS next")->next;

            return 'REP-'.$next;
        }

        $next = ((int) Repair::withTrashed()->max('id') ?? 0) + 1000;

        return 'REP-'.$next;
    }
}
