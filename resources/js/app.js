import './bootstrap';

import L, { tooltip } from 'leaflet';
import 'leaflet/dist/leaflet.css';

import { createChart, BaselineSeries, LineSeries, AreaSeries } from 'lightweight-charts';
import ApexCharts from 'apexcharts';

import rivers from './rivers';

document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.toggle(
        "dark",
        localStorage.theme === "dark" ||
        (!("theme" in localStorage) && window.matchMedia("(prefers-color-scheme: dark)").matches),
    );
});

const createPlantMarker = (plant) => {

    let svg = '';
    if (!plant.active_reactors_count) {
        svg = `/storage/markers/marker-empty.svg`;
    } else if (plant.active_reactors_count === plant.total_reactors_count) {
        svg = `/storage/markers/marker-full.svg`;
    } else {
        svg = `/storage/markers/marker-${plant.active_reactors_count}-${plant.total_reactors_count}.svg`;
    }

    const icon = L.divIcon({
        className: 'plant-marker',
        html: `<img src="${svg}" alt="${plant.name}" class="w-10 h-10" />`,
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
    });

    const marker = L.marker([plant.lat, plant.lng], { icon, riseOnHover: true });

    marker.on('click', () => {
        window.dispatchEvent(new CustomEvent('select-plant', { detail: { plantId: plant.id } }));
    });

    return marker;
}

const selectPlantMarker = window.selectPlantMarker = (id) => {

    document.querySelectorAll('.selected-marker').forEach(m => {
        m.querySelector('img').src = m.querySelector('img').src.replace('-selected.svg', '.svg');
        m.classList.remove('selected-marker');
    });

    const marker = window.plantMarkers[`plant-${id}`];
    if (marker) {
        marker._icon.classList.add('selected-marker');
        const source = marker._icon?.querySelector('img')?.src;
        if (source) {
            const selectedSource = source.replace('.svg', '-selected.svg');
            marker._icon.querySelector('img').src = selectedSource;
        }
    }
}

const createPlantMap = window.createPlantMap = (plants) => {

    let plantMarkers = window.plantMarkers = {};

    const map = window.plantmap = L.map('map', {
        center: [46.5, 2.5],
        zoom: 7,
        minZoom: 5,
        maxZoom: 12,
        maxBounds: [
            [41.0, -5.0],
            [52.0, 10.0]
        ],
        maxBoundsViscosity: 1.0
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    for (const [name, geojson] of Object.entries(rivers)) {
        L.geoJSON(geojson, { className: "stroke-sky-600" }).addTo(map);
    }

    const markerGroup = L.featureGroup();

    plants.forEach(plant => {
        const marker = createPlantMarker(plant);

        plantMarkers[`plant-${plant.id}`] = marker;

        markerGroup.addLayer(marker);
    });

    markerGroup.addTo(map);

    map.fitBounds(markerGroup.getBounds(), { padding: [20, 20] });

    return map;
}

const createApexPlantChart = window.createApexPlantChart = (el, records) => {

    var options = {
        series: records,
        chart: {
            id: "area-datetime",
            type: "area",
            height: 260,
            stacked: true,
            animations: {
                speed: 200,
                animateGradually: {
                    enabled: false,
                },
            },
            toolbar: {
                show: false,
            },
            zoom: {
                autoScaleYaxis: true,
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: false,
        },
        markers: {
            size: 0,
            style: "hollow",
            colors: ['#00c950'],
            strokeWidth: 1,
            hover: {
                size: 3,
                sizeOffset: 0
            }
        },
        xaxis: {
            type: "datetime",
            min: records.length ? records[0].data[0][0] + 3600000 : undefined,
            max: records.length ? records[0].data[records[0].data.length -1][0] : undefined,
            tickAmount: 24,
            tooltip: {
                enabled: false,
            },
        },
        yaxis: {
            //max: 1750,
            min: 0,
            stepSize: 500,
            labels: {
                formatter: function (val) {
                    return val.toFixed(0);
                },
            },
        },
        grid: {
            borderColor: '#e2e8f0',
            strokeDashArray: 3,
        },
        tooltip: {
            custom: function({series, seriesIndex, dataPointIndex, w}) {

                const time = (new Date(w.globals.seriesX[seriesIndex][dataPointIndex]))
                    .toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                const totalProduction = series.reduce((total, s) => {
                    return total + s[dataPointIndex];
                }, 0).toFixed(0);

                let content = '<div class="bg-white rounded shadow-xs text-xs p-2">';
                    content += `<div>Production à ${time} : <span class="font-semibold">${(new Intl.NumberFormat('fr-FR').format(totalProduction))} MWh</span></div>`;
                    content += `<div>`;

                const details = [];

                w.config.series.forEach((s, i) => {

                    const color = w.config.fill.colors[i] ?? null;

                    let detail = '<div class="flex items-center justify-between gap-2 mt-1">';

                    if (color) {
                        detail += `<span class="block w-3 h-3 rounded-full shrink-0" style="background-color:${color}"></span>`;
                    }
                    detail += `<span>${s.name} : </span>`;
                    detail += `<span class="w-full h-px bg-slate-200"></span>`;
                    detail += `<span class="font-semibold">${(new Intl.NumberFormat('fr-FR').format(series[i][dataPointIndex]))} MWh</span>`;
                    detail += `</div>`;

                    details.push(detail);
                });

                content += details.reverse().join('');

                content += `</div>`;

                return content;
            },
            x: {
                format: "dd MMM yyyy",
            },
        },
        fill: {
            type: "solid",
            colors: [
                'var(--color-green-300)',
                'var(--color-green-400)',
                'var(--color-green-500)',
                'var(--color-green-600)',
                'var(--color-green-700)',
                'var(--color-green-800)',
            ],
        },
        stroke: {
            curve: "smooth",
            width: 1.5,
            colors: ["#fff"],
        },
        grid: {
            borderColor: '#e2e8f0',
            strokeDashArray: 3,
        },
        plotOptions: {
            area: {
                fillTo: 'origin',
            },
            // line: {
            //     colors: {
            //     threshold: 0,
            //     colorAboveThreshold: '#00c950',
            //     colorBelowThreshold: '#e6000b',
            //     },
            // },
        }
    };

    const chart = window.apexPlantChart = new ApexCharts(el, options);

    chart.render();
}