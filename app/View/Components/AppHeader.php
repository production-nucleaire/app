<?php

namespace App\View\Components;

use App\Services\NationalStats;
use App\Support\Sparkline;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppHeader extends Component
{
    public function __construct(public string $active = 'national')
    {
        //
    }

    public function render(): View
    {
        $stats = NationalStats::get();

        return view('components.app-header', [
            'stats' => $stats,
            'spark' => Sparkline::render($stats['spark24h'], 150, 34, '#124a63', 'rgba(18,74,99,0.08)'),
        ]);
    }
}
