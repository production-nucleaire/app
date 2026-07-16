<?php

namespace App\View\Components;

use App\Models\Record;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppFooter extends Component
{
    public function render(): View
    {
        $lastUpdated = cache('rte:last_successful_import');

        if (! $lastUpdated) {
            $lastUpdated = Record::latest('date')->value('date');
            if ($lastUpdated) {
                cache()->forever('rte:last_successful_import', $lastUpdated->format('Y-m-d H:i:s'));
            }
        } else {
            $lastUpdated = Carbon::parse($lastUpdated);
        }

        return view('components.app-footer', [
            'lastUpdated' => $lastUpdated,
        ]);
    }
}
