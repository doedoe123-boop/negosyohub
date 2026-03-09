<?php

namespace App;

enum FeaturedType: string
{
    case Store = 'store';
    case Product = 'product';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Store => 'Store',
            self::Product => 'Product',
            self::Service => 'Service',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Store => 'primary',
            self::Product => 'success',
            self::Service => 'info',
        };
    }
}
