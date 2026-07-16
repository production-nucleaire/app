import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import { createChart, LineSeries, AreaSeries } from 'lightweight-charts';

import rivers from './rivers';

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

    const r = 12, circ = 2 * Math.PI * r, dash = circ * pct / 100;

    const html = `<div style="width:34px;height:34px;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.3));">
        <svg width="34" height="34" viewBox="0 0 34 34">
            <circle cx="17" cy="17" r="15" fill="${surface}"></circle>
            <circle cx="17" cy="17" r="${r}" fill="none" stroke="${track}" stroke-width="3.5"></circle>
            <circle cx="17" cy="17" r="${r}" fill="none" stroke="${color}" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="${dash.toFixed(1)} ${circ.toFixed(1)}" transform="rotate(-90 17 17)"></circle>
            <text x="17" y="20.5" text-anchor="middle" font-family="Spline Sans Mono,monospace" font-size="9" font-weight="600" fill="${ink}">${online}</text>
        </svg>
    </div>`;

    const icon = L.divIcon({
        className: 'plant-marker',
        html,
        iconSize: [34, 34],
        iconAnchor: [17, 17],
    });

    const marker = L.marker([plant.lat, plant.lng], { icon, riseOnHover: true });

    marker.bindTooltip(`<b>${plant.name}</b> · ${new Intl.NumberFormat('fr-FR').format(plant.latest_production_mw)} MW (${pct} %)`);

    marker.on('click', () => {
        if (window.Livewire) {
            window.Livewire.navigate(`/${plant.slug}`);
        } else {
            window.location.href = `/${plant.slug}`;
        }
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

const createReactorChart = window.createReactorChart = (el, series, meta) => {
    el.innerHTML = '';
    el.style.position = 'relative';

    const line = cssVar('--color-line', '#e4e7e7');
    const faint = cssVar('--color-faint', '#8a969b');
    const tints = (meta && meta.tints) || [
        '#124a63',
        '#0d8a4f',
        '#5fb98a',
        '#7fd4a8',
        '#9aa4a9',
        '#2f89b0',
    ];

    const chart = createChart(el, {
        autoSize: true,
        layout: {
            background: {
                color: 'transparent'
            },
            textColor: faint,
            fontFamily: "'Spline Sans Mono', monospace",
            fontSize: 10,
        },
        grid: {
            vertLines: {
                visible: false
            },
            horzLines: {
                color: line
            }
        },
        rightPriceScale: {
            borderColor: line
        },
        timeScale: {
            borderColor: line,
            timeVisible: true,
            secondsVisible: false
        },
        localization: {
            locale: 'fr-FR'
        },
        handleScale: {
            axisPressedMouseMove: false
        },
    });

    const seriesObjs = [];
    const dataByName = {};

    series.forEach((s, i) => {
        const obj = chart.addSeries(LineSeries, {
            color: tints[i % tints.length],
            lineWidth: 2,
            priceLineVisible: false,
            lastValueVisible: false,
            crosshairMarkerRadius: 3,
        });
        obj.setData(s.data);
        seriesObjs.push({ name: s.name, obj });
        dataByName[s.name] = s.data.slice();
    });

    const latestTime = () => {
        let to = 0;
        Object.values(dataByName).forEach(d => { if (d.length) to = Math.max(to, d[d.length - 1].time); });
        return to;
    };

    const setWindow = window.setReactorWindow = (hours) => {
        const to = latestTime();
        if (!to) { chart.timeScale().fitContent(); return; }
        chart.timeScale().setVisibleRange({ from: to - hours * 3600, to });
    };
    setWindow(48);

    // Crosshair tooltip listing every tranche's MW at the hovered hour.
    const tip = document.createElement('div');
    tip.style.cssText = 'position:absolute;display:none;pointer-events:none;z-index:20;background:rgba(20,35,43,0.92);color:#fff;font:11px/1.55 "Spline Sans Mono",monospace;padding:8px 10px;border-radius:8px;white-space:nowrap;';
    el.appendChild(tip);

    chart.subscribeCrosshairMove(param => {
        if (!param.time || !param.point || param.point.x < 0 || param.point.y < 0) {
            tip.style.display = 'none';
            return;
        }
        let rows = '';
        seriesObjs.forEach((so, i) => {
            const d = param.seriesData.get(so.obj);
            if (d) {
                rows += `<div style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:50%;background:${tints[i % tints.length]}"></span>${so.name} : ${new Intl.NumberFormat('fr-FR').format(d.value)} MW</div>`;
            }
        });
        const date = new Date(param.time * 1000).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        tip.innerHTML = `<div style="color:#9fb3bd;margin-bottom:4px">${date}</div>${rows}`;
        tip.style.display = 'block';
        let x = param.point.x + 16;
        const y = param.point.y + 16;
        if (x > el.clientWidth - tip.clientWidth - 8) x = param.point.x - tip.clientWidth - 16;
        tip.style.left = Math.max(4, x) + 'px';
        tip.style.top = y + 'px';
    });

    // "Chargées à la volée" — pull older windows as the user drags back in time.
    let earliest = Math.min(...Object.values(dataByName).map(d => (d.length ? d[0].time : Infinity)));
    let loading = false, exhausted = false;

    chart.timeScale().subscribeVisibleLogicalRangeChange(async (range) => {
        if (!range || loading || exhausted || range.from > 10 || !meta || !meta.slug) return;
        loading = true;
        try {
            const end = earliest;
            const start = end - 30 * 24 * 3600;
            const res = await fetch(`/api/plants/${meta.slug}/records?start=${start}&end=${end}`);
            const older = await res.json();
            let added = 0;

            older.forEach((s, i) => {
                if (!seriesObjs[i]) return;
                const fresh = (s.data || []).filter(p => p.time < earliest);
                if (!fresh.length) return;
                const merged = [...fresh, ...(dataByName[seriesObjs[i].name] || [])];
                dataByName[seriesObjs[i].name] = merged;
                seriesObjs[i].obj.setData(merged);
                added += fresh.length;
            });

            const newEarliest = Math.min(...Object.values(dataByName).map(d => (d.length ? d[0].time : Infinity)));
            if (added === 0 || newEarliest >= earliest) exhausted = true;
            earliest = newEarliest;
        } catch (e) {
            exhausted = true;
        }
        loading = false;
    });

    return chart;
};

const createHistoryChart = window.createHistoryChart = (el, points) => {
    el.innerHTML = '';

    const line = cssVar('--color-line', '#e4e7e7');
    const faint = cssVar('--color-faint', '#8a969b');
    const brand = cssVar('--color-brand', '#124a63');

    const chart = createChart(el, {
        autoSize: true,
        layout: {
            background: {
                color: 'transparent'
            },
            textColor: faint,
            fontFamily: "'Spline Sans Mono', monospace",
            fontSize: 10,
        },
        grid: {
            vertLines: {
                visible: false
            },
            horzLines: {
                color: line
            }
        },
        rightPriceScale: {
            borderColor: line
        },
        timeScale: {
            borderColor: line,
            timeVisible: true,
            secondsVisible: false
        },
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
    area.setData(points);
    chart.timeScale().fitContent();

    window.historyChart = chart;
    return chart;
};
