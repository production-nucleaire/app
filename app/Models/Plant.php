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

    public function getLatestProductionMwAttribute(): float
    {
        return $this->reactors
            ->map(function ($reactor) {
                return $reactor->records()
                    ->latest('date')
                    ->first()?->value ?? 0;
            })
            ->sum();
    }

    /**
     * Get the total production for the plant.
     */
    public function getTotalProductionMwAttribute(): float
    {
        return $this->reactors
            ->map(function ($reactor) {
                return $reactor->net_power_mw;
            })
            ->sum();
    }

    public function getPercentValueAttribute(): float
    {
        return $this->latest_production_mw / $this->total_production_mw * 100;
    }
}
