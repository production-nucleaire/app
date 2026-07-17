{{-- Shared scaffold for the Open Graph share images (1200x630). Fonts are
     inlined (no network), and a light/dark token set is switched via
     data-theme so night-time renders (22h-6h) get a dark variant. --}}
<!doctype html>
<html>
<head>
<meta charset="utf-8">
{!! \App\Support\OgFonts::styleTag() !!}
<style>
    *{margin:0;padding:0;box-sizing:border-box;}
    html,body{width:1200px;height:630px;}
    body{font-family:'Instrument Sans',sans-serif;-webkit-font-smoothing:antialiased;}
    .og{
        width:1200px;height:630px;overflow:hidden;position:relative;display:flex;
        --ink:#14232b;--ink-2:#3d4a51;--muted:#55554f;--muted-2:#68757b;--muted-3:#8a969b;--faint:#a8b2b6;
        --line:#e4e7e7;--line-2:#dde0e0;--card:#f7f8f8;--card-2:#ffffff;--map-1:#f1f4f2;--map-2:#e8efe9;
        --brand:#124a63;--brand-dot:#7fd4a8;--green:#0d8a4f;--green-soft:#5fb98a;--off:#c3ccc7;--neg:#b5471d;
        --chip-green-bg:#eaf4ee;--chip-green-ink:#14232b;--chip-off-bg:#f2f5f5;--chip-off-ink:#8a969b;
    }
    .og[data-theme="dark"]{
        --ink:#eef4f5;--ink-2:#c7d6db;--muted:#a6b8bf;--muted-2:#90a6ae;--muted-3:#789098;--faint:#4e6773;
        --line:#23454f;--line-2:#1d3d49;--card:#0e2530;--card-2:#123039;--map-1:#102b37;--map-2:#0b2029;
        --brand:#17516a;--brand-dot:#7fd4a8;--green:#2ecc80;--green-soft:#4bab84;--off:#47616d;--neg:#e0623a;
        --chip-green-bg:#123a2c;--chip-green-ink:#dff3e8;--chip-off-bg:#16323d;--chip-off-ink:#8aa0aa;
    }
    .mono{font-family:'Spline Sans Mono',monospace;}
</style>
</head>
<body>
<div class="og" data-theme="{{ ($dark ?? false) ? 'dark' : 'light' }}">
{{ $slot }}
</div>
</body>
</html>
