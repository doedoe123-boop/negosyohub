<?php

namespace App;

enum ShipmentProvider: string
{
    case Manual = 'manual';
    case Lalamove = 'lalamove';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual / In-house',
            self::Lalamove => 'Lalamove',
            self::External => 'External Provider',
        };
    }
}
