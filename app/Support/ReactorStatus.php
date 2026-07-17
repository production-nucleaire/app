<?php

namespace App\Support;

use App\Models\Reactor;

class ReactorStatus
{
    /**
     * Human-readable operating status for a reactor, derived from its latest reading.
     * Mirrors the labels shown on the plant detail unit cards
     * (resources/views/livewire/plant-map.blade.php).
     */
    public static function label(Reactor $reactor): string
    {
        $value = $reactor->latestRecord?->value ?? 0;
        $pct = $reactor->latestRecord?->percent_value ?? 0;

        return self::fromValues($value, $pct);
    }

    /** Same mapping, from raw MW value + clamped load percentage. */
    public static function fromValues(float $value, int $pct): string
    {
        return match (true) {
            $value < 0 => 'consommatrice',
            $pct <= 1 => 'à l\'arrêt · maintenance',
            $pct >= 98 => 'couplée · pleine puissance',
            $pct >= 90 => 'couplée',
            default => 'couplée · suivi de charge',
        };
    }
}
