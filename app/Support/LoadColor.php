<?php

namespace App\Support;

class LoadColor
{
    public static function var(float $mw, float $pct): string
    {
        return 'var('.self::token($mw, $pct).')';
    }

    public static function token(float $mw, float $pct): string
    {
        return match (true) {
            $mw < 0 => '--color-danger',
            $pct > 66 => '--color-accent',
            $pct >= 33 => '--color-accent-mid',
            default => '--color-idle',
        };
    }
}
