import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import rivers from './rivers';

const generatePinPieSVG = (active, total) => {

    const cx = 256;
    const cy = 212;
    const radius = 137;
    const angleStep = 360 / total;
    const rotationOffset = -90;
    let paths = '';

    for (let i = 0; i < total; i++) {
        const startAngle = angleStep * i + rotationOffset;
        const endAngle = angleStep * (i + 1) + rotationOffset;
        const largeArc = angleStep > 180 ? 1 : 0;

        const x1 = cx + radius * Math.cos(Math.PI / 180 * startAngle);
        const y1 = cy + radius * Math.sin(Math.PI / 180 * startAngle);
        const x2 = cx + radius * Math.cos(Math.PI / 180 * endAngle);
        const y2 = cy + radius * Math.sin(Math.PI / 180 * endAngle);

        const className = i < active
            ? 'class="fill-green-500"'
            : 'class="fill-white stroke-8 stroke-white"';

        paths += `<path d="M${cx},${cy} L${x1},${y1} A${radius},${radius} 0 ${largeArc} 1 ${x2},${y2} Z" ${className} />`;
    }

    return `
        <svg width="40" height="40" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
            <g>
                <!-- Pin shape -->
                <path d="m407.57 62.538a214.108 214.108 0 0 0 -365.678 151.57 212.833 212.833 0 0 0 62.538 151.571l142.609 142.609a12.672 12.672 0 0 0 17.922 0l142.609-142.609a214.946 214.946 0 0 0 0-303.141zm-151.57 287.368c-75.61 0-137.123-61.514-137.123-137.124s61.513-137.124 137.123-137.124 137.124 61.513 137.124 137.124-61.514 137.124-137.124 137.124z" class="group-[:not(.selected-marker)]:fill-sky-600 group-[.selected-marker]:fill-red-500" />
                <!-- Pie chart -->
                <circle cx="256" cy="212" r="137" class="fill-green-500" />
                ${paths}
            </g>
        </svg>
    `;
}

const createPlantMarker = (plant) => {
    const svg = generatePinPieSVG(plant.active_reactors, plant.total_reactors);

    const icon = L.divIcon({
        className: 'group plant-marker',
        html: svg,
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
    });

    const marker = L.marker([plant.lat, plant.lng], { icon, riseOnHover: true })
        .bindPopup(`<strong>${plant.name}</strong><br>${plant.active_reactors}/${plant.total_reactors} reactors producing`);

    marker.on('click', () => {
        window.dispatchEvent(new CustomEvent('select-plant', { detail: { plantId: plant.id } }));
    });

    return marker;
}

const selectPlantMarker = window.selectPlantMarker = (id) => {

    document.querySelectorAll('.selected-marker').forEach(m => {
        m.classList.remove('selected-marker');
    });

    const marker = window.plantMarkers[`plant-${id}`];
    if (marker) {
        marker._icon.classList.add('selected-marker');
    }
}

document.addEventListener('plant-selected', (event) => {
    if (event?.detail[0]?.plantId) {
        selectPlantMarker(event.detail[0].plantId);
    }
});

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