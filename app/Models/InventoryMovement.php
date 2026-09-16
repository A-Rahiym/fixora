<?php

namespace App\Models;

use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only stock ledger. No updates or deletes by design —
 * there is no policy, observer, or route that mutates these rows.
 *
 * @property int $id
 * @property int $inventory_item_id
 * @property string $type
 * @property int $quantity_change
 * @property string $reference_type
 * @property int|null $reference_id
 * @property string|null $note
 * @property int $created_by
 * @property Carbon|null $created_at
 */
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity_change',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
