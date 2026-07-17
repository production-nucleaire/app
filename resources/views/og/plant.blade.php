{{-- OG 4b — plant overview. Data assembled by ShareImageService::plantData(). --}}
<x-og.layout :dark="$dark">
    <div style="width:1200px; height:630px; background:var(--card-2); display:flex; flex-direction:column; overflow:hidden;">
        {{-- Brand header --}}
        <div style="flex:0 0 auto; padding:34px 48px 0; display:flex; align-items:center; gap:10px;">
            {!! \App\Support\OgSvg::logo(30) !!}
            <div style="font:700 20px 'Instrument Sans',sans-serif; color:var(--ink);">électronucléaire<span style="color:var(--muted-3);">.fr</span></div>
            <div style="flex:1;"></div>
            <div style="font:500 15px 'Spline Sans Mono',monospace; color:var(--muted-3);">{{ $dateLabel }}</div>
        </div>

        {{-- Name + current output --}}
        <div style="flex:0 0 auto; padding:26px 48px 0; display:flex; align-items:flex-end; gap:24px;">
            <div>
                <div style="font:600 15px 'Instrument Sans',sans-serif; letter-spacing:0.12em; color:var(--muted-3);">{{ $descriptor }}</div>
                <div style="font:700 50px 'Instrument Sans',sans-serif; color:var(--ink); margin-top:4px; letter-spacing:-0.01em;">{{ $name }}</div>
            </div>
            <div style="flex:1;"></div>
            <div style="text-align:right;">
                <div style="font:700 50px 'Spline Sans Mono',monospace; color:var(--{{ $mwColor }}); letter-spacing:-0.02em;">{{ $mw }} <span style="font-size:24px; color:var(--muted-2);">MW</span></div>
                <div style="font:500 17px 'Spline Sans Mono',monospace; color:var(--muted-2); margin-top:2px;">{{ $loadPct }} % de la capacité · {{ $coupledCount }}/{{ $totalCount }} tranches couplées</div>
            </div>
        </div>

        {{-- Per-reactor status chips --}}
        <div style="flex:0 0 auto; padding:20px 48px 0; display:flex; gap:10px;">
            @foreach ($chips as $chip)
                @php $off = $chip['status'] === 'off'; @endphp
                <span style="display:flex; align-items:center; gap:8px; white-space:nowrap; font:500 15px 'Spline Sans Mono',monospace; color:var(--{{ $off ? 'chip-off-ink' : 'chip-green-ink' }}); background:var(--{{ $off ? 'chip-off-bg' : 'chip-green-bg' }}); border-radius:8px; padding:7px 14px;"><span style="width:9px; height:9px; border-radius:50%; background:var(--{{ $chip['status'] === 'full' ? 'green' : ($chip['status'] === 'partial' ? 'green-soft' : 'off') }});"></span>{{ $chip['label'] }}</span>
            @endforeach
            <span style="flex:1;"></span>
            <span style="font:400 14px 'Spline Sans Mono',monospace; color:var(--faint); align-self:center; white-space:nowrap;">dernières 24 h</span>
        </div>

        {{-- Full-width 24h chart --}}
        <div style="flex:1; min-height:0; padding:10px 40px 24px;">{!! $chartSvg !!}</div>
    </div>
</x-og.layout>
