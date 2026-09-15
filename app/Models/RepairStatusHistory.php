<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only status audit trail. No updates or deletes by design —
 * there is no policy, observer, or route that mutates these rows.
 *
 * @property int $id
 * @property int $repair_id
 * @property string $status
 * @property int $changed_by
 * @property string|null $note
 * @property Carbon|null $created_at
 */
class RepairStatusHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $table = 'repair_status_history';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'repair_id',
        'status',
        'changed_by',
        'note',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo<Repair, $this>
     */
    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
