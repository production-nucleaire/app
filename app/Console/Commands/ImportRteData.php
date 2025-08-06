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
    protected $signature = 'app:import-rte-data {--start= : Start date in YYYY-MM-DD format} {--end= : End date in YYYY-MM-DD format}';

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
        $start = $this->option('start')
            ? Carbon::parse($this->option('start'))->startOfDay()
            : now()->subDays(6)->startOfDay();
        $end = $this->option('end')
            ? Carbon::parse($this->option('end'))->endOfDay()
            : now();

        $this->info('Fetching data from RTE API...');

        $this->info('Start: ' . $start->format(DATE_ATOM) . ', End: ' . $end->format(DATE_ATOM));

        // die();

        try {
            $data = app(RteApiService::class)->fetchGenerationPerUnit($start, $end);
        } catch (\Exception $e) {
            $this->error('Error fetching data from RTE API');
            $this->info($e->getMessage());
            return;
        }

        if (empty($data)) {
            $this->info('No data found for the specified period.');
            return;
        }

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

        $this->info('Data import completed successfully.');

        cache()->forever('rte:last_successful_import', now()->format('Y-m-d H:i:s'));
    }
}
