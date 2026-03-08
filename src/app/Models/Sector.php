<?php

namespace App\Models;

use App\SectorTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $icon
 * @property string $color
 * @property SectorTemplate|null $template
 * @property string|null $registration_button_text
 * @property bool $is_active
 * @property int $sort_order
 */
class Sector extends Model
{
    /** @use HasFactory<\Database\Factories\SectorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'template',
        'description',
        'registration_button_text',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'template' => SectorTemplate::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────

    /** @return HasMany<SectorDocument> */
    public function documents(): HasMany
    {
        return $this->hasMany(SectorDocument::class)->orderBy('sort_order');
    }

    /** @return HasMany<SectorDocument> */
    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(SectorDocument::class)
            ->where('is_required', true)
            ->orderBy('sort_order');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // ── Template Helpers ───────────────────────────────────────────────

    /**
     * Get the Filament panel ID for this sector.
     */
    public function panelId(): string
    {
        return $this->template?->panelId() ?? 'lunar';
    }

    /**
     * Get the supported features for this sector.
     *
     * @return list<string>
     */
    public function supportedFeatures(): array
    {
        return $this->template?->supportedFeatures() ?? [];
    }

    /**
     * Determine if this sector supports a given feature.
     */
    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->supportedFeatures(), true);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Get all required document keys (for validation purposes).
     *
     * @return list<string>
     */
    public function requiredDocumentKeys(): array
    {
        return $this->documents()
            ->where('is_required', true)
            ->pluck('key')
            ->values()
            ->toArray();
    }

    /**
     * Get documents as a plain array (compatible with old enum format).
     *
     * @return array<int, array{key: string, label: string, description: string, required: bool, mimes: string}>
     */
    public function documentsArray(): array
    {
        return $this->documents()
            ->get()
            ->map(fn (SectorDocument $doc): array => [
                'key' => $doc->key,
                'label' => $doc->label,
                'description' => $doc->description ?? '',
                'required' => $doc->is_required,
                'mimes' => $doc->mimes,
            ])
            ->toArray();
    }
}
