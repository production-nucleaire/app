<?php

namespace App\Console\Commands;

use App\Models\Reactor;
use App\Models\Unit;
use App\Models\Record;
use App\Models\Technology;
use Illuminate\Support\Carbon;
use App\Services\RteApiService;
use Illuminate\Console\Command;

class ImportRteData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-rte-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $start = $now->copy()->subDays(4)->startOfDay();

        $this->info('Fetching data from RTE API...');

        $this->info('Start: ' . $start->format(DATE_ATOM) . ', End: ' . $now->format(DATE_ATOM));

        // die();

        $data = app(RteApiService::class)->fetchGenerationPerUnit();

        foreach ($data as $entry) {

            if ('NUCLEAR' !== $entry['unit']['production_type']) {
                $this->info('Skipping non-nuclear unit: ' . $entry['unit']['eic_code']);
                continue;
            }

            $reactor = Reactor::where('eic_code', $entry['unit']['eic_code'])->first();
            if (!$reactor) {
                $this->error('Reactor not found for EIC code: ' . $entry['unit']['eic_code']);
                continue;
            }

            foreach ($entry['values'] as $value) {
                Record::updateOrCreate([
                    'reactor_id' => $reactor->id,
                    'date' => Carbon::parse($value['end_date']),
                ], [
                    'value' => (int) $value['value'],
                ]);
            }
        }
    }
}
