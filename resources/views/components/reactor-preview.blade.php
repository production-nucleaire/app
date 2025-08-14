<div class="flex items-center gap-2">
    <div class="w-8 h-10 flex bg-white dark:bg-slate-800 border border-slate-950 dark:border-slate-300 rounded-t-xl overflow-hidden" style="--height:{{ $reactor->latestRecord->percent_value ?? 0 }}%">
        <div class="relative w-full h-[var(--height)] bg-gradient-to-b from-green-400 dark:from-green-600 to-green-500 dark:to-green-700 mt-auto">
            <div class="absolute -bottom-px -left-px w-4 h-4 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-950 dark:border-slate-300 text-[.65rem] font-bold text-slate-950 dark:text-slate-300">{{ $reactor->reactor_index }}</div>
        </div>
    </div>
    <div class="flex flex-col">
        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $reactor->name }}</span>
        <span class="text-xs text-slate-500 dark:text-slate-400">
            {{ $reactor->stage }}&nbsp;<span class="font-semibold">&middot;</span>&nbsp;{{ Number::format($reactor->net_power_mw, locale: 'fr') }}&nbsp;MW
        </span>
    </div>
    <div class="font-semibold text-right ml-auto">
        <div class="text-sm text-slate-900 dark:text-slate-300">{{ Number::format($reactor->latestRecord?->value ?? 0, locale: 'fr') }}&nbsp;MW</div>
        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $reactor->latestRecord?->percent_value ?? 0 }}%</div>
    </div>
</div>