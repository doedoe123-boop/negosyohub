<?php

namespace App\Models;

use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Lunar\Models\Language;

class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'locale',
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $translation): void {
            Cache::forget("localization.catalog.{$translation->locale}");
            Cache::forget("localization.overrides.{$translation->locale}");
        });

        static::deleted(function (self $translation): void {
            Cache::forget("localization.catalog.{$translation->locale}");
            Cache::forget("localization.overrides.{$translation->locale}");
        });
    }

    public function localeRecord(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }
}
