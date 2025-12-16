@extends('admin.layouts.app')

@section('title', 'Generate Page')

@section('content')
<div class="container">
        @php
            $resultData = $data
        @endphp

        <div class="alert alert-success">
            <h4>Hasil Optimasi:</h4>
            <p>Rute: 
                @php
                    $idToNameMap = $destinasi->pluck('name', 'id')->toArray();
                    $routeNames = array_map(function($id) use ($idToNameMap) {
                        return $idToNameMap[$id] ?? $id;
                    }, $resultData['chromosome']);
                @endphp
                {{ implode(' -> ', $routeNames) }}
            </p>
            <p>Total Jarak: {{ $resultData['distance_km'] }} km</p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Visualisasi Rute</h6>
                    </div>
                    <div class="card-body">
                        <div id="map" style="height: 600px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Petunjuk Arah</h6>
                    </div>
                    <div class="card-body">
                        <div id="directions-panel" style="height: 600px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
        </div>
        <form action="{{ route('optimasi.store') }}" method="POST">
            @csrf
            <input name="solusi" type="hidden" value="{{ json_encode($resultData['chromosome']) }}">
            <input name="jarak" type="hidden" value="{{ $resultData['distance_km'] }}">
            <button type="submit" class="btn btn-primary btn-block mb-5">Simpan Data</button>
        </form>

</div>

<!-- Pindahkan script Google Maps ke sini dan tambahkan libraries yang diperlukan -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places,geometry&callback=initMap" async defer></script>

<style>
    /* Map controls styling */
    .leaflet-control-custom {
        background-color: #fff;
        border: 2px solid rgba(0,0,0,0.2);
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 1px 5px rgba(0,0,0,0.4);
        margin: 10px;
    }
    
    .leaflet-control-custom:hover {
        background-color: #f4f4f4;
    }
    
    /* Directions panel styling */
    #directions-panel {
        font-family: 'Nunito', sans-serif;
        font-size: 0.9em;
        line-height: 1.5;
    }
    
    .direction-step {
        padding: 10px;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .direction-step:hover {
        background-color: #f8f9fc;
    }
    
    .step-distance {
        color: #858796;
        font-size: 0.85em;
    }
    
    .route-summary {
        padding: 15px;
        background-color: #4e73df;
        color: white;
        border-radius: 4px;
        margin-bottom: 10px;
        font-weight: bold;
    }
</style>
<script>
let map;
let markers = [];
let routeLayer;
let isRouteVisible = true;

document.addEventListener('DOMContentLoaded', function() {
    @if(isset($result))
        initializeMap();
    @endif
});

function initializeMap() {
    // Data dari controller (dengan safety check)
    const destinations = @json($destinasi);
    
    @if(isset($result) && isset($result['chromosome']))
        const route = @json($result['chromosome']);
    @else
        console.error('Result data not available');
        return;
    @endif
    
    // Convert route IDs ke koordinat
    const coordinates = route.map(id => {
        const destination = destinations.find(d => d.id == id);
        return {
            id: id,
            lat: parseFloat(destination.lat),
            lng: parseFloat(destination.lng),
            name: destination.name,
            description: destination.description
        };
    });
    
    // Inisialisasi map (center ke titik pertama)
    map = L.map('map').setView([coordinates[0].lat, coordinates[0].lng], 12);
    
    // Tambah tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);
    
    // Buat markers
    createMarkers(coordinates);
    
    // Get directions dari OSRM (ini yang akan buat polyline ngikutin jalan)
    getDirections(coordinates);
    
    // Fit bounds ke semua markers
    const bounds = L.latLngBounds(coordinates.map(c => [c.lat, c.lng]));
    map.fitBounds(bounds, { padding: [50, 50] });
    
    // Tambah toggle button setelah route selesai load
    // (dipindah ke dalam getDirections callback)
}

function createMarkers(coordinates) {
    coordinates.forEach((coord, index) => {
        // Warna marker: Hijau (start), Merah (end), Biru (waypoints)
        let markerColor = '#2196F3'; // Biru default
        if (index === 0) markerColor = '#4CAF50'; // Hijau untuk start
        if (index === coordinates.length - 1) markerColor = '#F44336'; // Merah untuk end
        
        // Custom icon dengan warna
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <div style="
                    background-color: ${markerColor};
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
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
            iconSize: [30, 30],
            iconAnchor: [15, 15],
        });
        
        const marker = L.marker([coord.lat, coord.lng], { icon: customIcon }).addTo(map);
        
        // Popup info
        const popupContent = `
            <div style="min-width: 150px;">
                <h6 style="margin: 0 0 5px 0; color: #4e73df;">${coord.name}</h6>
                <p style="margin: 0 0 5px 0; font-size: 0.9em;">${coord.description || 'Tidak ada deskripsi'}</p>
                <p style="margin: 0; font-size: 0.85em; color: #858796;">
                    <strong>Titik ${String.fromCharCode(65 + index)}</strong><br>
                    ${index === 0 ? '🟢 Titik Awal' : (index === coordinates.length - 1 ? '🔴 Titik Akhir' : '🔵 Persinggahan')}
                </p>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        markers.push(marker);
    });
}

function getDirections(coordinates) {
    // Build OSRM coordinates string (format: lng,lat;lng,lat;...)
    const coordsString = coordinates
        .map(c => `${c.lng},${c.lat}`)
        .join(';');
    
    const osrmUrl = `{{ env('OSRM_SERVER_URL', 'https://router.project-osrm.org') }}/route/v1/driving/${coordsString}`;
    
    // Tampilkan loading di directions panel
    document.getElementById('directions-panel').innerHTML = 
        '<div class="alert alert-info">⏳ Menghitung rute...</div>';
    
    fetch(osrmUrl + '?overview=full&steps=true&geometries=geojson')
        .then(response => response.json())
        .then(data => {
            if (data.code === 'Ok' && data.routes.length > 0) {
                const route = data.routes[0];
                
                // ✅ PENTING: Buat polyline dari geometry OSRM (NGIKUTIN JALAN!)
                const geometry = route.geometry;
                
                // Convert coordinates dari [lng, lat] ke [lat, lng] untuk Leaflet
                const latlngs = geometry.coordinates.map(coord => [coord[1], coord[0]]);
                
                // Buat polyline dengan geometry detail dari OSRM
                routeLayer = L.polyline(latlngs, {
                    color: '#FF0000',
                    weight: 4,
                    opacity: 0.7,
                    smoothFactor: 1
                }).addTo(map);
                
                // Tambah toggle button SETELAH route selesai dibuat
                addToggleButton();
                
                // Display directions
                displayDirections(route, coordinates);
            } else {
                document.getElementById('directions-panel').innerHTML = 
                    '<div class="alert alert-warning">❌ Tidak dapat menghitung directions</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching directions:', error);
            document.getElementById('directions-panel').innerHTML = 
                '<div class="alert alert-danger">❌ Error: ' + error.message + '</div>';
        });
}

function displayDirections(route, coordinates) {
    const panel = document.getElementById('directions-panel');
    let html = '';
    
    // Summary
    const totalDistance = (route.distance / 1000).toFixed(2);
    const totalDuration = Math.round(route.duration / 60);
    
    html += `
        <div class="route-summary">
            📍 Total Jarak: ${totalDistance} km<br>
            ⏱️ Estimasi Waktu: ${totalDuration} menit
        </div>
    `;
    
    // Steps dari setiap leg
    route.legs.forEach((leg, legIndex) => {
        html += `<div style="padding: 10px; background: #f8f9fc; margin-bottom: 5px; font-weight: bold;">
            ${coordinates[legIndex].name} → ${coordinates[legIndex + 1].name}
        </div>`;
        
        leg.steps.forEach((step, stepIndex) => {
            const distance = (step.distance / 1000).toFixed(2);
            const instruction = step.maneuver.type === 'depart' ? 'Mulai perjalanan' :
                               step.maneuver.type === 'arrive' ? 'Tiba di tujuan' :
                               step.name || 'Lanjutkan';
            
            html += `
                <div class="direction-step">
                    <div><strong>${stepIndex + 1}.</strong> ${instruction}</div>
                    <div class="step-distance">${distance} km</div>
                </div>
            `;
        });
    });
    
    panel.innerHTML = html;
}

function addToggleButton() {
    // Custom control untuk toggle route
    const toggleControl = L.control({ position: 'topright' });
    
    toggleControl.onAdd = function(map) {
        const button = L.DomUtil.create('div', 'leaflet-control-custom');
        button.innerHTML = '🗺️ Hide Route';
        
        L.DomEvent.on(button, 'click', function() {
            if (isRouteVisible) {
                map.removeLayer(routeLayer);
                button.innerHTML = '🗺️ Show Route';
            } else {
                routeLayer.addTo(map);
                button.innerHTML = '🗺️ Hide Route';
            }
            isRouteVisible = !isRouteVisible;
        });
        
        return button;
    };
    
    toggleControl.addTo(map);
}
</script>

@endsection