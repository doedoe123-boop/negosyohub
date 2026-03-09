<?php

namespace App;

enum CouponScope: string
{
    case Global = 'global';
    case Sector = 'sector';
    case Store = 'store';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Global (All Stores)',
            self::Sector => 'Sector-Specific',
            self::Store => 'Store-Specific',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Global => 'primary',
            self::Sector => 'info',
            self::Store => 'gray',
        };
    }
}
