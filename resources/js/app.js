import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import { createChart, AreaSeries, BaselineSeries } from 'lightweight-charts';

import rivers from './rivers';

// #RRGGBB (or #RGB) → rgba() with the given alpha.
const hexToRgba = (hex, a) => {
    const h = hex.replace('#', '').trim();
    const full = h.length === 3 ? h.split('').map(c => c + c).join('') : h;
    const n = parseInt(full, 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
};

document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.toggle(
        "dark",
        localStorage.theme === "dark" ||
        (!("theme" in localStorage) && window.matchMedia("(prefers-color-scheme: dark)").matches),
    );
});

// Make sure map/charts appy light/dark theme
const cssVar = (name, fallback) => {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
};

const createPlantMarker = (plant) => {
    const pct = Math.max(0, Math.min(100, Math.round(plant.percent_value)));
    const online = plant.active_reactors_count;

    const color = plant.latest_production_mw < 0
        ? cssVar('--color-danger', '#b5471d')
        : (pct > 5 ? cssVar('--color-accent', '#0d8a4f') : cssVar('--color-idle', '#c3ccc7'));
    const surface = cssVar('--color-surface', '#ffffff');
    const ink = cssVar('--color-ink', '#14232b');
    const track = cssVar('--color-line-strong', '#dde0e0');

    const r = 15, circ = 2 * Math.PI * r, dash = circ * pct / 100;

    const html = `<div style="width:40px;height:40px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3));">
        <svg width="40" height="40" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="${surface}"></circle>
            <circle cx="20" cy="20" r="${r}" fill="none" stroke="${track}" stroke-width="4"></circle>
            <circle cx="20" cy="20" r="${r}" fill="none" stroke="${color}" stroke-width="4" stroke-linecap="round" stroke-dasharray="${dash.toFixed(1)} ${circ.toFixed(1)}" transform="rotate(-90 20 20)"></circle>
            <text x="20" y="24" text-anchor="middle" font-family="Spline Sans Mono,monospace" font-size="11" font-weight="600" fill="${ink}">${online}</text>
        </svg>
    </div>`;

    const icon = L.divIcon({
        className: 'plant-marker',
        html,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
    });

    const marker = L.marker([plant.lat, plant.lng], { icon, riseOnHover: true });

    marker.bindTooltip(`<b>${plant.name}</b> · ${new Intl.NumberFormat('fr-FR').format(plant.latest_production_mw)} MW (${pct} %)`);

    marker.on('click', () => {
        window.dispatchEvent(new CustomEvent('preview-plant', { detail: { plantId: plant.id } }));
    });

    return marker;
};

const createPlantMap = window.createPlantMap = (plants) => {
    const plantMarkers = window.plantMarkers = {};

    const map = window.plantmap = L.map('map', {
        center: [46.5, 2.5],
        zoom: 6,
        minZoom: 5,
        maxZoom: 12,
        zoomControl: false,
        maxBounds: [[41.0, -5.0], [52.0, 10.0]],
        maxBoundsViscosity: 1.0,
    });

    L.control.zoom({ position: 'topright' }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    for (const [, geojson] of Object.entries(rivers)) {
        L.geoJSON(geojson, { className: "stroke-sky-600" }).addTo(map);
    }

    const markerGroup = L.featureGroup();

    plants.forEach(plant => {
        const marker = createPlantMarker(plant);
        plantMarkers[`plant-${plant.id}`] = marker;
        markerGroup.addLayer(marker);
    });

    markerGroup.addTo(map);
    map.fitBounds(markerGroup.getBounds(), { padding: [30, 30] });

    return map;
};

/*
 * Shared behaviour for the time-series charts: drag left to lazily load older windows
 * (skipping data gaps until the fleet's earliest record) and lock the right edge so the
 * chart can't be scrolled into the empty future.
 *
 * Model-agnostic so it fits both the flat history series and the derived stacked chart:
 *   opts.seriesData()          -> current raw arrays (aligned to fetchOlder's response)
 *   opts.lastIndex()           -> index of the most recent bar (future clamp)
 *   opts.prepend(freshArrays)  -> merge older raw data + refresh the chart's series
 *   opts.fetchOlder(start,end) -> Promise<array-of-arrays> aligned to seriesData()
 */
const enableLazyLoad = (chart, opts) => {
    const ts = chart.timeScale();
    const minTime = Number.isFinite(opts.minTime) ? opts.minTime : -Infinity;
    const chunk = opts.chunkSeconds;
    const oldest = () => Math.min(...opts.seriesData().map(a => (a.length ? a[0].time : Infinity)));

    let earliest = oldest();   // oldest loaded timestamp (dedupe + view anchor)
    let nextEnd = earliest;    // end of the next fetch window (walks back, skips gaps)
    let loading = false;
    let exhausted = !(nextEnd > minTime);
    let internal = false;      // guard our own programmatic range changes
    let armed = false;         // only load after a real user grab, never on render/resize

    if (opts.element) {
        const arm = () => { armed = true; };
        opts.element.addEventListener('pointerdown', arm, { passive: true });
        opts.element.addEventListener('wheel', arm, { passive: true });
    }

    ts.subscribeVisibleLogicalRangeChange(async (range) => {
        if (!range || internal) return;

        // Lock the future: never let the right edge move past the most recent bar.
        const li = opts.lastIndex();
        if (li >= 0 && range.to > li + 0.5) {
            internal = true;
            ts.setVisibleLogicalRange({ from: range.from - (range.to - li), to: li });
            internal = false;
            return;
        }

        if (!armed || loading || exhausted || range.from > 40 || !opts.fetchOlder) return;
        loading = true;
        try {
            for (let i = 0; i < 4 && nextEnd > minTime; i++) {
                const start = Math.max(minTime, nextEnd - chunk);
                const older = await opts.fetchOlder(start, nextEnd);
                nextEnd = start;

                const saved = ts.getVisibleRange();
                const fresh = older.map(arr => (arr || []).filter(p => p.time < earliest));
                const added = fresh.reduce((n, a) => n + a.length, 0);

                if (added > 0) {
                    internal = true;
                    opts.prepend(fresh);
                    internal = false;
                    earliest = oldest();
                    if (saved) { internal = true; ts.setVisibleRange(saved); internal = false; }
                    break; // got a chunk of history — stop for this drag
                }
                // empty chunk (data gap) — keep walking older within this trigger
            }
            if (nextEnd <= minTime) exhausted = true;
        } catch (e) {
            // transient error — allow a retry on the next drag
        }
        loading = false;
    });
};

const createReactorChart = window.createReactorChart = (el, series, meta) => {
    el.innerHTML = '';
    el.style.position = 'relative';

    const line = cssVar('--color-line', '#e4e7e7');
    const faint = cssVar('--color-faint', '#8a969b');
    const danger = cssVar('--color-danger', '#b5471d');
    const tints = (meta && meta.tints) || ['#124a63', '#0d8a4f', '#5fb98a', '#7fd4a8', '#9aa4a9', '#2f89b0'];
    const maxMw = (meta && meta.maxMw) || 1000;
    const fmt = (v) => new Intl.NumberFormat('fr-FR').format(v);

    const chart = createChart(el, {
        autoSize: true,
        layout: { background: { color: 'transparent' }, textColor: faint, fontFamily: "'Spline Sans Mono', monospace", fontSize: 10 },
        grid: { vertLines: { visible: false }, horzLines: { color: line } },
        rightPriceScale: { borderColor: line, scaleMargins: { top: 0.06, bottom: 0.04 } },
        timeScale: { borderColor: line, timeVisible: true, secondsVisible: false },
        localization: { locale: 'fr-FR' },
        handleScale: { axisPressedMouseMove: false },
    });

    // Pin the Y axis to -100 .. installed capacity so it never rescales.
    const fixedScale = { autoscaleInfoProvider: () => ({ priceRange: { minValue: -100, maxValue: maxMw } }) };

    // Raw per-reactor data (kept as the source of truth; the stack is derived from it).
    const raw = series.map((s, i) => ({ name: s.name, color: tints[i % tints.length], data: s.data.slice() }));
    const N = raw.length;
    const hidden = new Set();

    // One "band" per stack slot, added bottom-z first (holds the largest cumulative). Baseline
    // series (baseValue 0) so a band only fills ABOVE zero — the sub-zero strip stays clear for
    // the red consuming area below.
    const clear = 'rgba(0,0,0,0)';
    const bands = [];
    for (let p = 0; p < N; p++) {
        bands.push(chart.addSeries(BaselineSeries, {
            baseValue: { type: 'price', price: 0 },
            bottomLineColor: clear, bottomFillColor1: clear, bottomFillColor2: clear,
            lineWidth: 1, priceLineVisible: false, lastValueVisible: false, ...fixedScale,
        }));
    }
    // Below-zero (consuming) production, drawn red under the axis.
    const neg = chart.addSeries(BaselineSeries, {
        baseValue: { type: 'price', price: 0 },
        topLineColor: 'rgba(0,0,0,0)', topFillColor1: 'rgba(0,0,0,0)', topFillColor2: 'rgba(0,0,0,0)',
        bottomLineColor: danger, bottomFillColor1: hexToRgba(danger, 0.5), bottomFillColor2: hexToRgba(danger, 0.12),
        lineWidth: 1, priceLineVisible: false, lastValueVisible: false, ...fixedScale,
    });

    const timelineTimes = () => {
        let base = [];
        raw.forEach(r => { if (r.data.length > base.length) base = r.data; });
        return base.map(p => p.time);
    };
    let maps = [];
    const rebuild = () => {
        maps = raw.map(r => { const m = new Map(); r.data.forEach(p => m.set(p.time, p.value)); return m; });
        const times = timelineTimes();
        const visible = raw.map((r, i) => ({ r, i })).filter(({ i }) => !hidden.has(i));
        const k = visible.length;

        const cumData = visible.map(() => []); // cumData[j] = cumulative of first j+1 visible reactors
        const negData = [];
        times.forEach(t => {
            let cum = 0, negSum = 0;
            visible.forEach(({ i }, j) => {
                const v = maps[i].get(t) ?? 0;
                cum += Math.max(v, 0);
                negSum += Math.min(v, 0);
                cumData[j].push({ time: t, value: cum });
            });
            negData.push({ time: t, value: negSum });
        });

        // Bottom-z slot gets the largest cumulative; each slot is coloured by the reactor it exposes.
        for (let p = 0; p < N; p++) {
            if (p < k) {
                const cumIdx = k - 1 - p;
                const col = visible[cumIdx].r.color;
                bands[p].setData(cumData[cumIdx]);
                bands[p].applyOptions({ topLineColor: col, topFillColor1: hexToRgba(col, 0.9), topFillColor2: hexToRgba(col, 0.9) });
            } else {
                bands[p].setData([]);
            }
        }
        neg.setData(negData);
    };
    rebuild();

    window.reactorToggle = (i) => {
        if (hidden.has(i)) hidden.delete(i); else hidden.add(i);
        rebuild();
    };

    const latestTime = () => Math.max(0, ...raw.map(r => (r.data.length ? r.data[r.data.length - 1].time : 0)));
    window.setReactorWindow = (hours) => {
        const to = latestTime();
        if (!to) { chart.timeScale().fitContent(); return; }
        chart.timeScale().setVisibleRange({ from: to - hours * 3600, to });
    };
    window.setReactorWindow(48);

    // Crosshair tooltip: per-tranche MW at the hovered hour + total.
    const tip = document.createElement('div');
    tip.style.cssText = 'position:absolute;display:none;pointer-events:none;z-index:20;background:rgba(20,35,43,0.92);color:#fff;font:11px/1.55 "Spline Sans Mono",monospace;padding:8px 10px;border-radius:8px;white-space:nowrap;';
    el.appendChild(tip);

    chart.subscribeCrosshairMove(param => {
        if (!param.time || !param.point || param.point.x < 0 || param.point.y < 0) {
            tip.style.display = 'none';
            return;
        }
        const t = param.time;
        let rows = '', total = 0, any = false;
        raw.forEach((r, i) => {
            if (hidden.has(i)) return;
            const v = maps[i].get(t);
            if (v == null) return;
            any = true;
            total += v;
            rows += `<div style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:50%;background:${r.color}"></span>${r.name} : ${fmt(v)} MW</div>`;
        });
        if (!any) { tip.style.display = 'none'; return; }
        const date = new Date(t * 1000).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        tip.innerHTML = `<div style="color:#9fb3bd;margin-bottom:4px">${date}</div><div style="margin-bottom:4px">Total : ${fmt(total)} MW</div>${rows}`;
        tip.style.display = 'block';
        let x = param.point.x + 16;
        const y = param.point.y + 16;
        if (x > el.clientWidth - tip.clientWidth - 8) x = param.point.x - tip.clientWidth - 16;
        tip.style.left = Math.max(4, x) + 'px';
        tip.style.top = y + 'px';
    });

    enableLazyLoad(chart, {
        element: el,
        minTime: meta && meta.minTime,
        chunkSeconds: 30 * 24 * 3600,
        seriesData: () => raw.map(r => r.data),
        lastIndex: () => timelineTimes().length - 1,
        prepend: (fresh) => {
            fresh.forEach((arr, i) => { if (raw[i] && arr.length) raw[i].data = [...arr, ...raw[i].data]; });
            rebuild();
        },
        fetchOlder: (meta && meta.slug)
            ? async (start, end) => {
                const res = await fetch(`/api/plants/${meta.slug}/records?start=${start}&end=${end}`);
                const json = await res.json();
                return json.map(s => s.data || []);
            }
            : null,
    });

    return chart;
};

const createHistoryChart = window.createHistoryChart = (el, points, meta) => {
    el.innerHTML = '';
    el.style.position = 'relative';

    const line = cssVar('--color-line', '#e4e7e7');
    const faint = cssVar('--color-faint', '#8a969b');
    const brand = cssVar('--color-brand', '#124a63');

    const chart = createChart(el, {
        autoSize: true,
        layout: { background: { color: 'transparent' }, textColor: faint, fontFamily: "'Spline Sans Mono', monospace", fontSize: 10 },
        grid: { vertLines: { visible: false }, horzLines: { color: line } },
        rightPriceScale: { borderColor: line },
        timeScale: { borderColor: line, timeVisible: true, secondsVisible: false },
        localization: {
            locale: 'fr-FR',
            priceFormatter: (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(v),
        },
    });

    const area = chart.addSeries(AreaSeries, {
        lineColor: brand,
        topColor: 'rgba(18,74,99,0.18)',
        bottomColor: 'rgba(18,74,99,0.02)',
        lineWidth: 2,
        priceLineVisible: false,
        lastValueVisible: false,
    });
    let data = points.slice();
    area.setData(data);
    chart.timeScale().fitContent();

    // Older windows are fetched at the current granularity (len 13/10/7 → hour/day/month).
    const len = meta && meta.len;
    const chunkFor = (l) => l === 13 ? 30 * 24 * 3600 : (l === 10 ? 365 * 24 * 3600 : 5 * 365 * 24 * 3600);

    enableLazyLoad(chart, {
        element: el,
        minTime: meta && meta.minTime,
        chunkSeconds: chunkFor(len),
        seriesData: () => [data],
        lastIndex: () => data.length - 1,
        prepend: (fresh) => { data = [...fresh[0], ...data]; area.setData(data); },
        fetchOlder: len
            ? async (start, end) => {
                const res = await fetch(`/api/history?len=${len}&start=${start}&end=${end}`);
                return [await res.json()];
            }
            : null,
    });

    window.historyChart = chart;
    return chart;
};

/* ----------------------------------------------------------------- Eggs --- */

const toast = window.nucToast = (msg) => {
    const t = document.createElement('div');
    t.className = 'nuc-toast';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; }, 2600);
    setTimeout(() => t.remove(), 3100);
};

// Scatter little atoms out of the header logo (7-click fission).
window.fizzLogo = (anchor) => {
    const r = anchor.getBoundingClientRect();
    const cx = r.left + 18, cy = r.top + r.height / 2;
    for (let i = 0; i < 14; i++) {
        const s = document.createElement('div');
        s.className = 'nuc-particle';
        s.textContent = '⚛';
        s.style.left = cx + 'px';
        s.style.top = cy + 'px';
        const ang = (Math.PI * 2 * i) / 14, dist = 60 + (i % 5) * 22;
        s.style.setProperty('--dx', Math.cos(ang) * dist + 'px');
        s.style.setProperty('--dy', Math.sin(ang) * dist + 'px');
        document.body.appendChild(s);
        setTimeout(() => s.remove(), 900);
    }
};

const atomeRain = () => {
    for (let i = 0; i < 26; i++) {
        const a = document.createElement('div');
        a.className = 'nuc-rain';
        a.textContent = '⚛';
        a.style.left = Math.random() * 100 + 'vw';
        a.style.fontSize = 12 + Math.random() * 20 + 'px';
        a.style.animationDelay = Math.random() * 0.8 + 's';
        a.style.animationDuration = 2.4 + Math.random() * 1.8 + 's';
        document.body.appendChild(a);
        setTimeout(() => a.remove(), 5000);
    }
};

const chainReaction = () => {
    document.querySelector('.js-logo')?.classList.add('nuc-spin');
    setTimeout(() => document.querySelector('.js-logo')?.classList.remove('nuc-spin'), 1200);
    const markers = Object.values(window.plantMarkers || {});
    markers.forEach((m, i) => {
        const icon = m && m._icon;
        if (!icon) return;
        setTimeout(() => {
            icon.classList.add('nuc-ripple');
            setTimeout(() => icon.classList.remove('nuc-ripple'), 700);
        }, i * 70);
    });
    toast('⚛️ Réaction en chaîne !');
};

let eggsInit = false;
const initEggs = () => {
    if (eggsInit) return;
    eggsInit = true;

    // Konami: ↑↑↓↓←→←→ b a
    const seq = ['arrowup', 'arrowup', 'arrowdown', 'arrowdown', 'arrowleft', 'arrowright', 'arrowleft', 'arrowright', 'b', 'a'];
    let ki = 0;
    window.addEventListener('keydown', (e) => {
        const k = e.key.toLowerCase();
        ki = (k === seq[ki]) ? ki + 1 : (k === seq[0] ? 1 : 0);
        if (ki === seq.length) { ki = 0; chainReaction(); }
    });

    // Type "atome" (ignored while typing in a field).
    let buf = '';
    window.addEventListener('keydown', (e) => {
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) return;
        if (e.key.length !== 1) return;
        buf = (buf + e.key.toLowerCase()).slice(-5);
        if (buf === 'atome') { buf = ''; atomeRain(); }
    });

    // Logo fission: 7 clicks on the nucleus square (capture phase so wire:navigate on the
    // surrounding link never fires — the tiny dot is the egg trigger, not a home link).
    let fizzN = 0, fizzT = null;
    document.addEventListener('click', (e) => {
        const dot = e.target.closest && e.target.closest('.js-fizz');
        if (!dot) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        fizzN++;
        clearTimeout(fizzT);
        fizzT = setTimeout(() => { fizzN = 0; }, 1500);
        if (fizzN >= 7) { fizzN = 0; window.fizzLogo(dot.closest('.js-logo') || dot); }
    }, true);

    // Napping plant toast (dispatched from the server when a consuming plant is opened).
    const wireNap = () => window.Livewire && window.Livewire.on('easter-nap', (d) => {
        const name = (Array.isArray(d) ? d[0]?.name : d?.name) || 'Cette centrale';
        toast(`Chut… ${name} fait la sieste 😴`);
    });
    if (window.Livewire) wireNap(); else document.addEventListener('livewire:init', wireNap);
};

initEggs();
