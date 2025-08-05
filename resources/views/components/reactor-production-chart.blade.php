<div
    x-data="{
        selectedRecord: {},
        records: [
            @foreach ($records as $record)
                { time: '{{ $record['time'] }}', value: {{ $record['value'] }}, percent_value: {{ $record['percent_value'] }} },
            @endforeach
        ],
    }"
    x-init="
        selectedRecord = records[records.length - 1];
        $watch('selectedRecord', (value) => {
            if (value) {
                const record = records.find(r => r.time === value.time);
                if (record) {
                    selectedRecord = record;
                }
            }
        });
    "
    class="border-t border-t-slate-200 pt-4 mt-4"
>
    <div class="flex items-center justify-between px-2">
        @if ($previousDay)
            <a href="{{ route('welcome', ['plant' => $reactor->plant_id, 'reactor' => $reactor->id, 'day' => $previousDay->format('Y-m-d')]) }}" x-on:click.prevent="$wire.set('day', '{{ $previousDay->format('Y-m-d') }}')" class="w-10 h-10 flex items-center justify-center hover:bg-slate-100 roundedtext-slate-600 hover:text-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3 h-3 fill-slate-600"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M163 320L185.6 342.6L377.6 534.6L400.2 557.2L445.5 511.9L422.9 489.3L253.5 319.9L422.9 150.5L445.5 127.9L400.2 82.6L377.6 105.2L185.6 297.2L163 319.8z"/></svg>
            </a>
        @else
            <div class="w-10 h-10"></div>
        @endif
        <div>
            <div class="text-sm font-semibold text-slate-800 text-center">{{ $day->format('d/m/Y') }}</div>
            <div
                x-cloak
                x-show="selectedRecord"
                class="text-sm text-slate-600 text-center"
            >
                À <strong x-text="selectedRecord ? selectedRecord.time : ''"></strong>&nbsp;:&nbsp;<strong x-text="selectedRecord ? selectedRecord.value : ''"></strong> MW (<strong x-text="selectedRecord ? selectedRecord.percent_value : ''"></strong>%)
            </div>
        </div>
        @if ($nextDay)
            <a href="{{ route('welcome', ['plant' => $reactor->plant_id, 'reactor' => $reactor->id, 'day' => $nextDay->format('Y-m-d')]) }}" x-on:click.prevent="$wire.set('day', '{{ $nextDay->format('Y-m-d') }}')" class="w-10 h-10 flex items-center justify-center hover:bg-slate-100 roundedtext-slate-600 hover:text-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3 h-3 fill-slate-600"><path d="M477.5 320L454.9 342.6L262.9 534.6L240.3 557.3L195 512L217.6 489.4L387 320L217.6 150.6L195 128L240.3 82.7L262.9 105.4L454.9 297.4L477.5 320z"/></svg>
            </a>
        @else
            <div class="w-10 h-10"></div>
        @endif
    </div>
    {!! $chart !!}
</div>
