{{-- OG 4a — national overview. Data assembled by ShareImageService::nationalData(). --}}
<x-og.layout :dark="$dark">
    {{-- Left: tile-less SVG map of France --}}
    <div style="flex:0 0 545px; position:relative; background:linear-gradient(160deg,var(--map-1),var(--map-2)); border-right:1px solid var(--line);">
        <div style="position:absolute; inset:0; display:grid; place-items:center;">{!! $mapSvg !!}</div>
        @php $legend = ['green' => 'couplée', 'green-soft' => 'charge partielle', 'off' => "à l'arrêt", 'neg' => 'consommatrice']; @endphp
        <div style="position:absolute; left:26px; bottom:20px; display:flex; gap:16px;">
            @foreach ($legend as $var => $label)
                <span style="display:flex; align-items:center; gap:7px; font:500 14px 'Spline Sans Mono',monospace; color:var(--muted-2);"><span style="width:11px; height:11px; border-radius:50%; background:var(--{{ $var }});"></span>{{ $label }}</span>
            @endforeach
        </div>
    </div>

    {{-- Right: figures --}}
    <div style="flex:1; min-width:0; background:var(--card); padding:38px 48px 30px; display:flex; flex-direction:column;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:30px; height:30px; border-radius:9px; background:var(--brand); display:grid; place-items:center;"><div style="width:10px; height:10px; border-radius:50%; background:var(--brand-dot);"></div></div>
            <div style="font:700 20px 'Instrument Sans',sans-serif; color:var(--ink);">électronucléaire<span style="color:var(--muted-3);">.fr</span></div>
            <div style="flex:1;"></div>
            <div style="font:500 15px 'Spline Sans Mono',monospace; color:var(--muted-3);">{{ $dateLabel }}</div>
        </div>

        <div style="margin-top:40px; font:600 15px 'Instrument Sans',sans-serif; letter-spacing:0.12em; color:var(--muted-3);">LE PARC NUCLÉAIRE FRANÇAIS, EN DIRECT</div>
        <div style="display:flex; align-items:baseline; gap:14px; margin-top:2px;">
            <span style="font:700 92px 'Spline Sans Mono',monospace; color:var(--ink); letter-spacing:-0.02em;">{{ $gw }}</span>
            <span style="font:600 34px 'Spline Sans Mono',monospace; color:var(--muted-2);">GW</span>
        </div>
        <div style="font:500 19px 'Instrument Sans',sans-serif; color:var(--muted); margin-top:2px;">injectés sur le réseau @if ($deltaText)· <b style="color:var(--{{ $deltaColor }}); font-family:'Spline Sans Mono',monospace;">{{ $deltaText }}</b> sur la dernière heure @endif</div>

        <div style="display:flex; gap:0; margin-top:28px; border-top:1px solid var(--line); border-bottom:1px solid var(--line); padding:16px 0;">
            <div style="flex:1;"><div style="font:700 30px 'Spline Sans Mono',monospace; color:var(--ink);">{{ $coupled }}<span style="color:var(--muted-3); font-size:20px;">/{{ $totalReactors }}</span></div><div style="font:500 14px 'Instrument Sans',sans-serif; color:var(--muted-3); margin-top:2px;">tranches couplées</div></div>
            <div style="width:1px; background:var(--line); margin:0 22px;"></div>
            <div style="flex:1;"><div style="font:700 30px 'Spline Sans Mono',monospace; color:var(--green);">{{ $loadPct }} %</div><div style="font:500 14px 'Instrument Sans',sans-serif; color:var(--muted-3); margin-top:2px;">de la capacité du parc</div></div>
            <div style="width:1px; background:var(--line); margin:0 22px;"></div>
            <div style="flex:1;"><div style="font:700 30px 'Spline Sans Mono',monospace; color:var(--ink);">{{ $plantsCount }}</div><div style="font:500 14px 'Instrument Sans',sans-serif; color:var(--muted-3); margin-top:2px;">centrales en exploitation</div></div>
        </div>

        <div style="display:flex; flex-direction:column; gap:9px; margin-top:22px;">
            @foreach ($highlights as $h)
                <div style="display:flex; align-items:center; gap:10px; font:500 16.5px 'Instrument Sans',sans-serif; color:var(--ink-2);"><span style="width:9px; height:9px; border-radius:50%; background:var(--{{ $h['color'] }}); flex:0 0 auto;"></span>{{ $h['text'] }}</div>
            @endforeach
        </div>

        <div style="flex:1;"></div>
        <div style="font:400 14px 'Spline Sans Mono',monospace; color:var(--faint);">electronucleaire.fr · données RTE éCO2mix</div>
    </div>
</x-og.layout>
