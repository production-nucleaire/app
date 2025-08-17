<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'cooling_type',
        'cooling_place',
        'wiki_link',
        'asn_link',
        'edf_link',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * Get the reactors for the plant.
     */
    public function reactors()
    {
        return $this->hasMany(Reactor::class);
    }

    /**
     * Get the records for the plant.
     */
    public function records()
    {
        return $this->hasManyThrough(Record::class, Reactor::class);
    }

    public function getActiveReactorsCountAttribute(): int
    {
        return $this->reactors
            ->filter(fn ($r) => $r->latestRecord?->percent_value >= 5)
            ->count();
    }

    public function getLatestProductionMwAttribute(): float
    {
        return $this->reactors->sum(fn ($r) => $r->latestRecord?->value ?? 0);
    }

    public function getTotalProductionMwAttribute(): float
    {
        return $this->reactors->sum('net_power_mw');
    }

    public function getPercentValueAttribute(): float
    {
        $total = $this->total_production_mw;
        return $total > 0 ? ($this->latest_production_mw / $total * 100) : 0;
    }
}
