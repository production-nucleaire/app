import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import rivers from './rivers';

window.addEventListener('reactor-selected', event => {
    const { slug, reactor } = event.detail[0] ?? {};
    history.replaceState(null, '', `/` + slug + `/tranche/` + reactor);
});

window.addEventListener('plant-selected', event => {
    const { plantId, slug } = event.detail[0] ?? {};
    history.replaceState(null, '', `/` + (slug ?? ''));
    if (plantId) {
        selectPlantMarker(plantId);
    }
});

const createPlantMarker = (plant) => {

    let svg = '';
    if (!plant.active_reactors) {
        svg = `/storage/markers/marker-empty.svg`;
    } else if (plant.active_reactors === plant.total_reactors) {
        svg = `/storage/markers/marker-full.svg`;
    } else {
        svg = `/storage/markers/marker-${plant.active_reactors}-${plant.total_reactors}.svg`;
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

const highlightPlantMarker = window.highlightPlantMarker = (id) => {
    // const marker = window.plantMarkers[`plant-${id}`];
    // if (marker) {
    //     marker._icon.classList.add('highlighted-marker');
    //     const source = marker._icon?.querySelector('img')?.src;
    //     if (source) {
    //         const highlightedSource = source.replace('.svg', '-selected.svg');
    //         marker._icon.querySelector('img').src = highlightedSource;
    //     }
    // }
}

const unhighlightPlantMarker = window.unhighlightPlantMarker = (id) => {
    // const marker = window.plantMarkers[`plant-${id}`];
    // if (marker) {
    //     marker._icon.classList.remove('highlighted-marker');
    //     const source = marker._icon?.querySelector('img')?.src;
    //     if (source) {
    //         const unhighlightedSource = source.replace('-selected.svg', '.svg');
    //         marker._icon.querySelector('img').src = unhighlightedSource;
    //     }
    // }
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