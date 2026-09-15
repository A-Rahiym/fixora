<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $repair_id
 * @property int $diagnosed_by
 * @property string $findings
 * @property string|null $recommended_action
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RepairDiagnosis extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'repair_id',
        'diagnosed_by',
        'findings',
        'recommended_action',
    ];

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
    public function diagnosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diagnosed_by');
    }
}
