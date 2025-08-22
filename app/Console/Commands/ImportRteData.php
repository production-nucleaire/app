<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Models\Reactor;
use Carbon\CarbonPeriod;
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
    protected $signature = 'app:import-rte-data {--id=} {--eic=} {--start= : Start date in YYYY-MM-DD format} {--end= : End date in YYYY-MM-DD format} {--unofficial : Use unofficial RTE API}';

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
            : now()->startOfDay();
        $end = $this->option('end')
            ? Carbon::parse($this->option('end'))->endOfDay()
            : now();

        $this->info('Fetching data from RTE API...');

        $this->info('Start: ' . $start->format(DATE_ATOM) . ', End: ' . $end->format(DATE_ATOM));

        if ($this->option('unofficial')) {

            $this->info('/!\ ---- Using unofficial RTE API ---- /!\\');

            if ($this->option('id')) {
                $reactors = Reactor::where('id', $this->option('id'))->get();
            } elseif ($this->option('eic')) {
                $reactors = Reactor::where('eic_code', $this->option('eic'))->get();
            } else {
                $reactors = Reactor::all();
            }

            if ($reactors->isEmpty()) {
                $this->error('No reactors found in the database.');
                return;
            }

            foreach ($reactors as $reactor) {
                $this->info(' + Processing reactor: ' . $reactor->name);

                $period = CarbonPeriod::create($start, $end);
                foreach ($period as $date) {
                    $this->info(' +--- Fetching data for date: ' . $date->format('Y-m-d'));

                    try {
                        $data = app(RteApiService::class)->fetchGenerationForUnit($reactor->eic_code, $date);
                    } catch (\Exception $e) {
                        $this->error('Error fetching data for date: ' . $date->format('Y-m-d'));
                        $this->info($e->getMessage());
                        continue;
                    }

                    if (empty($data)) {
                        $this->error(' +--- No data found for date: ' . $date->format('Y-m-d'));
                        continue;
                    }

                    foreach( $data as $entry ) {
                        Record::updateOrCreate([
                            'reactor_id' => $reactor->id,
                            'date' => $entry['date'],
                        ], [
                            'value' => (int) $entry['group'],
                        ]);
                    }
                }

                $this->info('Data import completed successfully.');
            }

        } else {

            $this->info('/!\ ---- Using official RTE API ---- /!\\');

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

                    $this->info('Processing data for reactor: ' . $reactor->name . ' on ' . $value['end_date']);

                    Record::updateOrCreate([
                        'reactor_id' => $reactor->id,
                        'date' => Carbon::parse($value['end_date']),
                    ], [
                        'value' => (int) $value['value'],
                    ]);
                }
            }

            $this->info('Data import completed successfully.');
        }

        cache()->forever('rte:last_successful_import', now()->format('Y-m-d H:i:s'));
    }
}
