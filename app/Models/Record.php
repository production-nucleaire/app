<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reactor_id',
        'date',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'value' => 'integer',
        ];
    }

    /**
     * Get the reactor that owns the record.
     */
    public function reactor()
    {
        return $this->belongsTo(Reactor::class);
    }

    public function getPercentValueAttribute(): int
    {
        $percent = ceil(($this->value / $this->reactor->net_power_mw) * 100);

        return $percent > 100 ? 100 : max($percent, 0);
    }
}
