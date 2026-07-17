{{-- OG 4c — default brand card. Always dark; sparkline from national spark24h. --}}
<x-og.layout :dark="true">
    <div style="width:1200px; height:630px; background:#0f2c3b; position:relative; display:flex; overflow:hidden;">
        <div style="margin:auto; text-align:center; position:relative; z-index:2; padding-bottom:40px;">
            <div style="width:68px; height:68px; border-radius:19px; background:#fff; display:inline-grid; place-items:center; box-shadow:0 4px 20px rgba(0,0,0,0.25);"><div style="width:22px; height:22px; border-radius:50%; background:#0d8a4f;"></div></div>
            <div style="font:700 58px 'Instrument Sans',sans-serif; color:#fff; margin-top:26px; letter-spacing:-0.01em;">électronucléaire<span style="color:#7fd4a8;">.fr</span></div>
            <div style="font:400 23px 'Instrument Sans',sans-serif; color:#9fb3bd; margin-top:12px;">La production du parc nucléaire français, heure par heure</div>
            <div style="display:flex; justify-content:center; gap:14px; margin-top:30px; font:500 15px 'Spline Sans Mono',monospace; color:#6f8896;">
                <span>{{ $plantsCount }} centrales</span><span style="color:#3d5666;">·</span><span>{{ $reactorsCount }} tranches</span><span style="color:#3d5666;">·</span><span>données RTE</span><span style="color:#3d5666;">·</span><span>mise à jour toutes les heures</span>
            </div>
        </div>
        <div style="position:absolute; left:0; right:0; bottom:0; height:190px; z-index:1;">{!! $sparkSvg !!}</div>
    </div>
</x-og.layout>
