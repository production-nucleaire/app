<div class="flex items-center gap-2">
    <div class="w-8 h-10 flex bg-white border border-slate-950 rounded-t-xl overflow-hidden" style="--height:{{ $reactor->latestRecord->percent_value ?? 0 }}%">
        <div class="relative w-full h-[var(--height)] bg-green-500 mt-auto">
            <div class="absolute -bottom-px -left-px w-4 h-4 flex items-center justify-center bg-white border border-slate-950 text-[.65rem] font-bold text-slate-950">{{ $reactor->reactor_index }}</div>
        </div>
    </div>
    <div class="flex flex-col">
        <span class="text-sm font-semibold text-slate-600">{{ $reactor->name }}</span>
        <span class="text-xs text-slate-500">
            {{ $reactor->stage }}&nbsp;<span class="font-semibold">&middot;</span>&nbsp;{{ Number::format($reactor->net_power_mw, locale: 'fr') }}&nbsp;MW
        </span>
    </div>
    <div class="font-semibold text-right ml-auto">
        <div class="text-sm text-slate-900">{{ Number::format($reactor->latestRecord->value, locale: 'fr') }}&nbsp;MW</div>
        <div class="text-xs text-slate-500">{{ $reactor->latestRecord->percent_value }}%</div>
    </div>
</div>