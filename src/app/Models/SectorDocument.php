<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sector_id
 * @property string $key
 * @property string $label
 * @property string $description
 * @property bool $is_required
 * @property string $mimes
 * @property int $sort_order
 */
class SectorDocument extends Model
{
    /** @use HasFactory<\Database\Factories\SectorDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'sector_id',
        'key',
        'label',
        'description',
        'is_required',
        'mimes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Sector, SectorDocument> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
