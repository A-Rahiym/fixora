<?php

namespace App\Models;

use App\Enums\RepairPriority;
use App\Enums\RepairStatus;
use Database\Factories\RepairFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $job_number
 * @property int $customer_id
 * @property int $device_id
 * @property int|null $assigned_technician_id
 * @property string $status
 * @property string $priority
 * @property string $reported_problem
 * @property array<string, mixed>|null $intake_condition
 * @property string|null $estimate_amount
 * @property string|null $final_cost
 * @property Carbon|null $expected_completion_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $collected_at
 * @property int $warranty_days
 * @property Carbon|null $warranty_expires_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Repair extends Model
{
    /** @use HasFactory<RepairFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_number',
        'customer_id',
        'device_id',
        'assigned_technician_id',
        'status',
        'priority',
        'reported_problem',
        'intake_condition',
        'estimate_amount',
        'final_cost',
        'expected_completion_at',
        'approved_at',
        'collected_at',
        'warranty_days',
        'warranty_expires_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'intake_condition' => 'array',
            'estimate_amount' => 'decimal:2',
            'final_cost' => 'decimal:2',
            'expected_completion_at' => 'date',
            'approved_at' => 'datetime',
            'collected_at' => 'datetime',
            'warranty_expires_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<RepairStatusHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(RepairStatusHistory::class)->latest('id');
    }

    /**
     * @return HasMany<RepairDiagnosis, $this>
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(RepairDiagnosis::class)->latest('id');
    }

    /**
     * @return HasMany<RepairNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(RepairNote::class)->latest('id');
    }

    public function statusEnum(): RepairStatus
    {
        return RepairStatus::from($this->status);
    }

    public function priorityEnum(): RepairPriority
    {
        return RepairPriority::from($this->priority);
    }
}
