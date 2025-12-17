@extends('admin.layouts.app')

@section('title', 'Rute Optimal')

@section('content')

@if(!is_null($data["distance_km"]))
<div class="container">
    @php
        $resultData = $data
    @endphp

    <!-- Alert -->
    <div class="alert alert-success glass-alert">
        <h4>✅ Rute Optimal Terbaik:</h4>
        <p><strong>Rute:</strong> 
            @php
                $idToNameMap = $destinasi->pluck('name', 'id')->toArray();
                $routeNames = array_map(function($id) use ($idToNameMap) {
                    return $idToNameMap[$id] ?? $id;
                }, $resultData['chromosome']);
            @endphp
            {{ implode(' → ', $routeNames) }}
        </p>
        <p><strong>Total Jarak (OSRM):</strong> {{ $resultData['distance_km'] }} km</p>
    </div>

    <!-- Disclaimer -->
    <div class="alert alert-info glass-alert-info">
        <h5>⚠️ INFORMASI PENTING:</h5>
        <div class="row">
            <div class="col-md-6">
                <h6>🔵 GARIS BIRU (Jarak Udara):</h6>
                <p class="small mb-0">Jarak teoritis garis lurus. <strong>TIDAK bisa dilalui!</strong></p>
            </div>
            <div class="col-md-6">
                <h6>🔴 GARIS MERAH (Rute Jalan):</h6>
                <p class="small mb-0">Rute sebenarnya. <strong>Gunakan ini untuk perjalanan!</strong></p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Map -->
        <div class="col-md-8">
            <div class="card glass-card shadow-lg mb-4">
                <div class="card-header glass-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">🗺️ Visualisasi Rute</h6>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 600px;"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Comparison -->
            <div class="card glass-card shadow-lg mb-3">
                <div class="card-header glass-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">📊 Perbandingan Jarak</h6>
                </div>
                <div class="card-body">
                    <div id="distance-comparison">
                        <div class="comparison-loading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Menghitung...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Directions -->
            <div class="card glass-card shadow-lg mb-4">
                <div class="card-header glass-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">🧭 Petunjuk Arah</h6>
                </div>
                <div class="card-body p-0">
                    <div id="directions-panel" style="height: 400px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="container">
    <div class="card glass-card shadow-lg text-center p-5">
        <img src="{{ asset('storage/images/404.svg') }}" alt="No Route" style="max-width: 300px; margin: 0 auto;">
        <h3 class="mt-4" style="color: #667eea;">Belum Ada Rute Optimal</h3>
        <p class="text-muted">Silakan hubungi admin untuk generate rute optimal</p>
    </div>
</div>
@endif

<!-- Styles -->
<style>
.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    overflow: hidden;
}

.glass-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.glass-alert {
    background: rgba(212, 237, 218, 0.95);
    backdrop-filter: blur(10px);
    border-left: 4px solid #28a745;
    border-radius: 10px;
}

.glass-alert-info {
    background: rgba(217, 237, 247, 0.95);
    backdrop-filter: blur(10px);
    border-left: 4px solid #17a2b8;
    border-radius: 10px;
}

.leaflet-control-custom {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 10px;
    padding: 10px 15px;
    cursor: pointer;
    font-family: 'Nunito', sans-serif;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    margin: 10px;
    transition: all 0.3s ease;
}

.leaflet-control-custom:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateY(-2px);
}

.leaflet-control-custom.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.comparison-item {
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 10px;
}

.comparison-blue {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.2) 100%);
    border-left: 4px solid #2196F3;
}

.comparison-red {
    background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.2) 100%);
    border-left: 4px solid #F44336;
}

.comparison-diff {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.2) 100%);
    border-left: 4px solid #FFC107;
}

.comparison-loading {
    text-align: center;
    padding: 30px;
    color: #667eea;
}

.route-summary {
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}

.direction-step {
    padding: 12px;
    border-bottom: 1px solid #e3e6f0;
    transition: all 0.2s ease;
}

.direction-step:hover {
    background: rgba(102, 126, 234, 0.05);
}

.step-distance {
    color: #858796;
    font-size: 0.85em;
}

.leaflet-popup-content-wrapper {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.leaflet-popup-tip {
    background: rgba(255, 255, 255, 0.95);
}
</style>

<!-- JavaScript -->
<script>
let map;
let markers = [];
let straightPolyline;
let routePolyline;
let isStraightVisible = true;
let isRouteVisible = true;

document.addEventListener('DOMContentLoaded', function() {
    @if(!is_null($data["distance_km"]))
        initializeMap();
    @endif
});

function initializeMap() {
    const destinations = @json($destinasi);
    const route = @json($data['chromosome']);
    
    const coordinates = route.map(id => {
        const destination = destinations.find(d => d.id == id);
        return {
            id: id,
            lat: parseFloat(destination.lat),
            lng: parseFloat(destination.lng),
            name: destination.name,
            description: destination.description,
            img: destination.img
        };
    });
    
    map = L.map('map').setView([coordinates[0].lat, coordinates[0].lng], 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
    
    createMarkers(coordinates);
    createStraightPolyline(coordinates);
    getOSRMRoute(coordinates);
    
    const bounds = L.latLngBounds(coordinates.map(c => [c.lat, c.lng]));
    map.fitBounds(bounds, { padding: [50, 50] });
}

function createMarkers(coordinates) {
    coordinates.forEach((coord, index) => {
        let markerColor = '#2196F3';
        if (index === 0) markerColor = '#4CAF50';
        if (index === coordinates.length - 1) markerColor = '#F44336';
        
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <div style="
                    background: ${markerColor};
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    font-size: 14px;
                ">
                    ${String.fromCharCode(65 + index)}
                </div>
            `,
            iconSize: [35, 35],
            iconAnchor: [17.5, 17.5],
        });
        
        const marker = L.marker([coord.lat, coord.lng], { icon: customIcon }).addTo(map);
        
        const imgUrl = coord.img ? `{{ asset('storage/images/destinations/') }}/${coord.img}` : '';
        const popupContent = `
            <div style="min-width: 200px;">
                ${coord.img ? `<img src="${imgUrl}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;" onerror="this.style.display='none'">` : ''}
                <h6 style="margin: 0 0 8px 0; color: #667eea; font-weight: bold;">${coord.name}</h6>
                <p style="margin: 0 0 8px 0; font-size: 0.9em; color: #555;">${coord.description || 'Tidak ada deskripsi'}</p>
                <p style="margin: 0; font-size: 0.85em; color: #858796;">
                    <strong>Titik ${String.fromCharCode(65 + index)}</strong><br>
                    ${index === 0 ? '🟢 Titik Awal' : (index === coordinates.length - 1 ? '🔴 Titik Akhir' : '🔵 Persinggahan ' + index)}
                </p>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        
        marker.on('click', function() {
            map.flyTo([coord.lat, coord.lng], 15, {
                animate: true,
                duration: 1.5
            });
        });
        
        markers.push(marker);
    });
}

function createStraightPolyline(coordinates) {
    const latlngs = coordinates.map(c => [c.lat, c.lng]);
    
    let totalStraightDistance = 0;
    for (let i = 0; i < coordinates.length - 1; i++) {
        totalStraightDistance += haversineDistance(
            coordinates[i].lat, coordinates[i].lng,
            coordinates[i + 1].lat, coordinates[i + 1].lng
        );
    }
    
    straightPolyline = L.polyline(latlngs, {
        color: '#2196F3',
        weight: 4,
        opacity: 0.7,
        dashArray: '15, 10',
        smoothFactor: 1
    }).addTo(map);
    
    window.straightDistance = totalStraightDistance / 1000;
}

function getOSRMRoute(coordinates) {
    const coordsString = coordinates.map(c => `${c.lng},${c.lat}`).join(';');
    const osrmUrl = `{{ env('OSRM_SERVER_URL', 'https://router.project-osrm.org') }}/route/v1/driving/${coordsString}`;
    
    document.getElementById('directions-panel').innerHTML = 
        '<div class="text-center p-3"><div class="spinner-border text-primary"></div><p class="mt-2">Menghitung rute...</p></div>';
    
    fetch(osrmUrl + '?overview=full&steps=true&geometries=geojson')
        .then(response => response.json())
        .then(data => {
            if (data.code === 'Ok' && data.routes.length > 0) {
                const route = data.routes[0];
                const latlngs = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);
                
                routePolyline = L.polyline(latlngs, {
                    color: '#F44336',
                    weight: 5,
                    opacity: 0.8,
                    smoothFactor: 1
                }).addTo(map);
                
                window.routeDistance = route.distance / 1000;
                updateComparisonPanel();
                addToggleControls();
                displayDirections(route, coordinates);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('directions-panel').innerHTML = 
                '<div class="alert alert-danger m-2">❌ Error: ' + error.message + '</div>';
        });
}

function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function updateComparisonPanel() {
    const straight = window.straightDistance || 0;
    const route = window.routeDistance || 0;
    const diff = route - straight;
    const diffPercent = straight > 0 ? ((diff / straight) * 100).toFixed(1) : 0;
    
    document.getElementById('distance-comparison').innerHTML = `
        <div class="comparison-item comparison-blue">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>🔵 Jarak Udara</strong>
                    <p class="mb-0 small text-muted">Garis lurus</p>
                </div>
                <h4 class="mb-0 text-primary">${straight.toFixed(2)} km</h4>
            </div>
        </div>
        <div class="comparison-item comparison-red">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>🔴 Rute Jalan</strong>
                    <p class="mb-0 small text-muted">Via jalan</p>
                </div>
                <h4 class="mb-0 text-danger">${route.toFixed(2)} km</h4>
            </div>
        </div>
        <div class="comparison-item comparison-diff">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>📈 Selisih</strong>
                    <p class="mb-0 small text-muted">Perbedaan</p>
                </div>
                <div class="text-right">
                    <h4 class="mb-0 text-warning">${diff.toFixed(2)} km</h4>
                    <p class="mb-0 small text-muted">(+${diffPercent}%)</p>
                </div>
            </div>
        </div>
    `;
}

function addToggleControls() {
    const toggleStraight = L.control({ position: 'topright' });
    toggleStraight.onAdd = function(map) {
        const button = L.DomUtil.create('div', 'leaflet-control-custom active');
        button.innerHTML = '🔵 Jarak Udara';
        L.DomEvent.on(button, 'click', function() {
            if (isStraightVisible) {
                map.removeLayer(straightPolyline);
                button.classList.remove('active');
                button.style.opacity = '0.5';
            } else {
                straightPolyline.addTo(map);
                button.classList.add('active');
                button.style.opacity = '1';
            }
            isStraightVisible = !isStraightVisible;
        });
        return button;
    };
    toggleStraight.addTo(map);
    
    const toggleRoute = L.control({ position: 'topright' });
    toggleRoute.onAdd = function(map) {
        const button = L.DomUtil.create('div', 'leaflet-control-custom active');
        button.innerHTML = '🔴 Rute Jalan';
        L.DomEvent.on(button, 'click', function() {
            if (isRouteVisible) {
                map.removeLayer(routePolyline);
                button.classList.remove('active');
                button.style.opacity = '0.5';
            } else {
                routePolyline.addTo(map);
                button.classList.add('active');
                button.style.opacity = '1';
            }
            isRouteVisible = !isRouteVisible;
        });
        return button;
    };
    toggleRoute.addTo(map);
}

function displayDirections(route, coordinates) {
    const panel = document.getElementById('directions-panel');
    let html = `<div class="route-summary">📍 Total: ${(route.distance/1000).toFixed(2)} km<br>⏱️ Waktu: ${Math.round(route.duration/60)} menit</div>`;
    
    route.legs.forEach((leg, legIndex) => {
        html += `<div style="padding: 12px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); margin: 5px 10px; border-radius: 8px; font-weight: bold;">
            ${coordinates[legIndex].name} → ${coordinates[legIndex + 1].name}
        </div>`;
        
        leg.steps.forEach((step, stepIndex) => {
            const instruction = step.maneuver.type === 'depart' ? '🚗 Mulai' :
                               step.maneuver.type === 'arrive' ? '🏁 Tiba' :
                               '➡️ ' + (step.name || 'Lanjut');
            html += `<div class="direction-step"><div><strong>${stepIndex + 1}.</strong> ${instruction}</div><div class="step-distance">${(step.distance/1000).toFixed(2)} km</div></div>`;
        });
    });
    
    panel.innerHTML = html;
}
</script>

@endsection