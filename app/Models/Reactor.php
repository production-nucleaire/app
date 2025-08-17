<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reactor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'eic_code',
        'plant_id',
        'reactor_index',
        'stage',
        'thermal_power_mw',
        'raw_power_mw',
        'net_power_mw',
        'build_start_date',
        'first_reaction_date',
        'grid_link_date',
        'exploitation_start_date',
        'mox_authorization_date',
        'cooling_tower_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'build_start_date' => 'date',
            'first_reaction_date' => 'date',
            'grid_link_date' => 'date',
            'exploitation_start_date' => 'date',
            'mox_authorization_date' => 'date',
        ];
    }

    public function latestRecord()
    {
        return $this->hasOne(Record::class)->latestOfMany('date');
    }

    /**
     * Get the plant that owns the reactor.
     */
    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the records for the reactor.
     */
    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
