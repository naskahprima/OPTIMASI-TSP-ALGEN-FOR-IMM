@extends('admin.layouts.app')

@section('title', 'Generate Optimasi TSP')

@section('content')
<div class="container">
    @if(session('result') || isset($result))
        @php
            $resultData = session('result') ?? $result;
        @endphp

        <!-- Alert Hasil Optimasi -->
        <div class="alert alert-success glass-alert">
            <h4>✅ Hasil Optimasi Berhasil!</h4>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>🗺️ Rute Optimal:</strong><br>
                        @php
                            $idToNameMap = $destinasi->pluck('name', 'id')->toArray();
                            $routeNames = array_map(function($id) use ($idToNameMap) {
                                return $idToNameMap[$id] ?? $id;
                            }, $resultData['chromosome']);
                        @endphp
                        <span class="text-muted">{{ implode(' → ', $routeNames) }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>📏 Total Jarak (OSRM):</strong> {{ $resultData['distance_km'] }} km</p>
                    <p><strong>⏱️ Waktu Generate:</strong> {{ $resultData['execution_time'] }}</p>
                    <p><strong>🎯 Fitness Akhir:</strong> {{ number_format($resultData['fitness'], 6) }}</p>
                </div>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="alert alert-info glass-alert-info">
            <h5>⚠️ CATATAN PENTING TENTANG VISUALISASI RUTE:</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>🔵 GARIS BIRU (Jarak Udara):</h6>
                    <ul class="small">
                        <li>Menunjukkan jarak lurus antar destinasi</li>
                        <li>Dihitung menggunakan formula Haversine</li>
                        <li><strong>TIDAK mewakili rute yang bisa dilalui</strong></li>
                        <li>Mungkin melewati sungai, gunung, atau obstacle</li>
                        <li>Hanya untuk <strong>perbandingan jarak teoritis</strong></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>🔴 GARIS MERAH (Rute Sebenarnya):</h6>
                    <ul class="small">
                        <li>Menunjukkan rute yang bisa dilalui kendaraan</li>
                        <li>Dihitung dari data jalan via OSRM</li>
                        <li>Mempertimbangkan jembatan, jalan, dan obstacle</li>
                        <li><strong>GUNAKAN INI sebagai panduan perjalanan</strong></li>
                        <li>Data akurat dari peta jalan sebenarnya</li>
                    </ul>
                </div>
            </div>
            <p class="mb-0 mt-2"><strong>💡 Tips:</strong> Jika perbedaan jarak signifikan (contoh: Biru 8km, Merah 15km), artinya rute jalan memerlukan perputaran atau melewati jembatan.</p>
        </div>

        <div class="row">
            <!-- Map Container -->
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
                <!-- Comparison Panel -->
                <div class="card glass-card shadow-lg mb-3">
                    <div class="card-header glass-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">📊 Perbandingan Jarak</h6>
                    </div>
                    <div class="card-body">
                        <div id="distance-comparison">
                            <div class="comparison-loading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Menghitung jarak...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Directions Panel -->
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

        <!-- Save Button -->
        <form action="{{ route('optimasi.store') }}" method="POST" id="saveForm">
            @csrf
            <input name="solusi" type="hidden" value="{{ json_encode($resultData['chromosome']) }}">
            <input name="jarak" type="hidden" value="{{ $resultData['distance_km'] }}">
            <button type="submit" class="btn btn-primary btn-lg btn-block glass-button mb-3" id="saveBtn">
                <i class="fas fa-save"></i> Simpan Rute Optimal
            </button>
        </form>
        <a href="{{ route('optimasi.generate') }}" class="btn btn-secondary btn-lg btn-block glass-button-secondary mb-5">
            <i class="fas fa-redo"></i> Generate Ulang
        </a>
    @endif

    <!-- Form Generate -->
    <div class="card glass-card shadow-lg mb-4">
        <div class="card-header glass-header py-3">
            <h6 class="m-0 font-weight-bold text-white">⚙️ Parameter Algoritma Genetika</h6>
        </div>
        <div class="card-body">
            <!-- Error Messages -->
            @if ($errors->any())
                <div class="alert alert-danger glass-alert-danger">
                    <h6><strong>❌ Terdapat kesalahan:</strong></h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger glass-alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                </div>
            @endif

            <!-- Warning jika destinasi kurang dari 2 -->
            @if($totalDestinasi < 2)
                <div class="alert alert-warning glass-alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Peringatan!</strong> 
                    Minimal 2 destinasi diperlukan untuk TSP. Saat ini hanya ada <strong>{{ $totalDestinasi }}</strong> destinasi.
                    <br><a href="{{ route('destinasi.create') }}" class="btn btn-sm btn-warning mt-2">
                        <i class="fas fa-plus"></i> Tambah Destinasi Sekarang
                    </a>
                </div>
            @endif

            <form action="{{ route('optimasi.generate.store') }}" method="post" id="generateForm">
                @csrf
                
                <div class="form-row m-2">
                    <div class="col-md-4">
                        <label for="kromosom">
                            <strong>Jumlah Kromosom</strong> 
                            <span class="text-danger">*</span>
                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                               title="Jumlah populasi dalam algoritma genetika (1-1000)"></i>
                        </label>
                        <input type="number" name="kromosom" id="kromosom" 
                               class="form-control glass-input @error('kromosom') is-invalid @enderror" 
                               value="{{ old('kromosom', 10) }}" 
                               min="1" max="1000" required
                               placeholder="Contoh: 10">
                        @error('kromosom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Rekomendasi: 10-100</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="max_gen">
                            <strong>Maksimal Generasi</strong> 
                            <span class="text-danger">*</span>
                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                               title="Jumlah iterasi maksimal (1-10000)"></i>
                        </label>
                        <input type="number" name="max_gen" id="max_gen" 
                               class="form-control glass-input @error('max_gen') is-invalid @enderror" 
                               value="{{ old('max_gen', 10) }}" 
                               min="1" max="10000" required
                               placeholder="Contoh: 10">
                        @error('max_gen')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Rekomendasi: 10-1000</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="titik_awal">
                            <strong>Titik Awal</strong> 
                            <span class="text-danger">*</span>
                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                               title="Destinasi yang menjadi titik awal & akhir rute"></i>
                        </label>
                        <select class="custom-select glass-input @error('titik_awal') is-invalid @enderror" 
                                name="titik_awal" id="titik_awal" required>
                            <option value="">-- Pilih Titik Awal --</option>
                            @foreach($destinasi as $des)
                                <option value="{{ $des->id }}" {{ old('titik_awal') == $des->id ? 'selected' : '' }}>
                                    {{ $des->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('titik_awal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row m-2">
                    <div class="col-md-6">
                        <label for="crossover_rate">
                            <strong>Crossover Rate</strong> 
                            <span class="text-danger">*</span>
                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                               title="Probabilitas perkawinan silang (0-1)"></i>
                        </label>
                        <input type="number" step="0.01" name="crossover_rate" id="crossover_rate" 
                               class="form-control glass-input @error('crossover_rate') is-invalid @enderror" 
                               value="{{ old('crossover_rate', 0.8) }}" 
                               min="0" max="1" required
                               placeholder="Contoh: 0.8">
                        @error('crossover_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Rekomendasi: 0.7-0.9</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="mutation_rate">
                            <strong>Mutation Rate</strong> 
                            <span class="text-danger">*</span>
                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                               title="Probabilitas mutasi (0-1)"></i>
                        </label>
                        <input type="number" step="0.01" name="mutation_rate" id="mutation_rate" 
                               class="form-control glass-input @error('mutation_rate') is-invalid @enderror" 
                               value="{{ old('mutation_rate', 0.1) }}" 
                               min="0" max="1" required
                               placeholder="Contoh: 0.1">
                        @error('mutation_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Rekomendasi: 0.05-0.2</small>
                    </div>
                </div>
                
                <div class="form-row m-2">
                    <div class="col text-center">
                        <button type="submit" class="btn btn-primary btn-lg glass-button" 
                                id="submitBtn" {{ $totalDestinasi < 2 ? 'disabled' : '' }}>
                            <i class="fas fa-play"></i> Generate Rute Optimal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div style="text-align: center; color: white;">
        <div class="spinner-border" style="width: 4rem; height: 4rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <h4 class="mt-3">🔄 Sedang Generate Rute...</h4>
        <p>Mohon tunggu, proses ini bisa memakan waktu beberapa detik</p>
        <small id="loadingTimer">0 detik</small>
    </div>
</div>

<!-- Styles -->
<style>
.glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 15px; overflow: hidden; }
.glass-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
.glass-alert { background: rgba(212, 237, 218, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #28a745; border-radius: 10px; }
.glass-alert-info { background: rgba(217, 237, 247, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #17a2b8; border-radius: 10px; }
.glass-alert-danger { background: rgba(248, 215, 218, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #dc3545; border-radius: 10px; }
.glass-alert-warning { background: rgba(255, 243, 205, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #ffc107; border-radius: 10px; }
.glass-input { background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px; transition: all 0.3s ease; }
.glass-input:focus { background: rgba(255, 255, 255, 1); border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
.glass-button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px; padding: 12px 30px; color: white; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
.glass-button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); color: white; }
.glass-button:disabled { opacity: 0.6; cursor: not-allowed; }
.glass-button-secondary { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); border: none; border-radius: 10px; padding: 12px 30px; color: white; font-weight: bold; }
.leaflet-control-custom { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 2px solid rgba(102, 126, 234, 0.3); border-radius: 10px; padding: 10px 15px; cursor: pointer; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.2); margin: 10px; transition: all 0.3s ease; }
.leaflet-control-custom:hover { background: rgba(255, 255, 255, 1); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
.leaflet-control-custom.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.comparison-item { padding: 15px; margin-bottom: 10px; border-radius: 10px; transition: all 0.3s ease; }
.comparison-blue { background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.2) 100%); border-left: 4px solid #2196F3; }
.comparison-red { background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.2) 100%); border-left: 4px solid #F44336; }
.comparison-diff { background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.2) 100%); border-left: 4px solid #FFC107; }
.comparison-loading { text-align: center; padding: 30px; color: #667eea; }
#directions-panel { font-family: 'Nunito', sans-serif; font-size: 0.9em; }
.route-summary { padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; margin-bottom: 10px; font-weight: bold; }
.direction-step { padding: 12px; border-bottom: 1px solid #e3e6f0; transition: all 0.2s ease; }
.direction-step:hover { background: rgba(102, 126, 234, 0.05); }
.step-distance { color: #858796; font-size: 0.85em; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.glass-card { animation: fadeIn 0.5s ease; }
.leaflet-popup-content-wrapper { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
.leaflet-popup-tip { background: rgba(255, 255, 255, 0.95); }
</style>

<script>
let map;
let markers = [];
let straightPolyline;
let routePolyline;
let isStraightVisible = true;
let isRouteVisible = true;
let loadingTimer;
let loadingSeconds = 0;
let mapInitialized = false;

// Client-side validation
document.getElementById('generateForm')?.addEventListener('submit', function(e) {
    const kromosom = parseInt(document.getElementById('kromosom').value);
    const maxGen = parseInt(document.getElementById('max_gen').value);
    const crossoverRate = parseFloat(document.getElementById('crossover_rate').value);
    const mutationRate = parseFloat(document.getElementById('mutation_rate').value);
    const titikAwal = document.getElementById('titik_awal').value;
    
    if (!titikAwal) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Error!', text: '❌ Titik awal harus dipilih!', confirmButtonColor: '#dc3545' });
        return false;
    }
    if (kromosom < 1 || kromosom > 1000) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Error!', text: '❌ Jumlah kromosom harus antara 1-1000!', confirmButtonColor: '#dc3545' });
        return false;
    }
    if (maxGen < 1 || maxGen > 10000) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Error!', text: '❌ Maksimal generasi harus antara 1-10000!', confirmButtonColor: '#dc3545' });
        return false;
    }
    if (crossoverRate < 0 || crossoverRate > 1) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Error!', text: '❌ Crossover rate harus antara 0-1!', confirmButtonColor: '#dc3545' });
        return false;
    }
    if (mutationRate < 0 || mutationRate > 1) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Error!', text: '❌ Mutation rate harus antara 0-1!', confirmButtonColor: '#dc3545' });
        return false;
    }
    showLoading();
});

document.getElementById('saveForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
});

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = 'flex';
    loadingSeconds = 0;
    loadingTimer = setInterval(() => {
        loadingSeconds++;
        document.getElementById('loadingTimer').textContent = loadingSeconds + ' detik';
    }, 1000);
}

function hideLoading() {
    clearInterval(loadingTimer);
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Initialize tooltips - WAIT FOR JQUERY
function initTooltips() {
    if (typeof $ !== 'undefined' && $.fn && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    } else {
        setTimeout(initTooltips, 100);
    }
}

// ✅ ULTIMATE FIX - Map initialization
window.addEventListener('load', function() {
    console.log('Window loaded, starting initialization...');
    
    @if(isset($result) && isset($result['chromosome']))
        setTimeout(() => {
            console.log('Checking for result card...');
            const resultCard = document.querySelector('.alert-success.glass-alert');
            const mapContainer = document.getElementById('map');
            
            console.log('Result card:', resultCard ? 'FOUND' : 'NOT FOUND');
            console.log('Map container:', mapContainer ? 'FOUND' : 'NOT FOUND');
            console.log('Leaflet loaded:', typeof L !== 'undefined' ? 'YES' : 'NO');
            
            if (resultCard && mapContainer && typeof L !== 'undefined') {
                if (mapInitialized) {
                    console.log('Map already initialized, skipping');
                    return;
                }
                
                console.log('All checks passed! Initializing map now...');
                
                // Force clean
                if (typeof map !== 'undefined' && map) {
                    console.log('Cleaning old map...');
                    try {
                        map.off();
                        map.remove();
                    } catch(e) {
                        console.log('Clean error (ignore):', e);
                    }
                }
                
                map = null;
                markers = [];
                straightPolyline = null;
                routePolyline = null;
                
                try {
                    initializeMap();
                    mapInitialized = true;
                    console.log('✅ Map initialized successfully!');
                } catch(error) {
                    console.error('❌ FATAL ERROR:', error);
                    alert('Map initialization failed: ' + error.message);
                }
            } else {
                console.log('❌ Prerequisites not met');
                if (!resultCard) console.log('- Missing result card');
                if (!mapContainer) console.log('- Missing map container');
                if (typeof L === 'undefined') console.log('- Leaflet not loaded');
            }
            
            // Init tooltips after everything
            initTooltips();
        }, 300);
    @else
        console.log('No result data from backend');
        initTooltips();
    @endif
});

function initializeMap() {
    console.log('=== initializeMap() called ===');
    
    // ✅ DESTROY OLD MAP FIRST
    if (typeof map !== 'undefined' && map) {
        console.log('Removing existing map instance...');
        try {
            map.off();
            map.remove();
        } catch(e) {
            console.log('Remove error (ignore):', e);
        }
        map = null;
    }
    
    // Clear arrays
    markers = [];
    straightPolyline = null;
    routePolyline = null;
    
    console.log('Creating fresh map...');
    
    const destinations = @json($destinasi);
    
    @if(isset($result) && isset($result['chromosome']))
        const route = @json($result['chromosome']);
    @else
        console.error('Result data not available');
        return;
    @endif
    
    console.log('Destinations:', destinations.length);
    console.log('Route:', route);
    
    const coordinates = route.map(id => {
        const destination = destinations.find(d => d.id == id);
        if (!destination) {
            console.error('Destination not found for id:', id);
            return null;
        }
        return {
            id: id,
            lat: parseFloat(destination.lat),
            lng: parseFloat(destination.lng),
            name: destination.name,
            description: destination.description,
            img: destination.img
        };
    }).filter(c => c !== null);
    
    console.log('Coordinates:', coordinates);
    
    if (coordinates.length === 0) {
        console.error('No valid coordinates!');
        return;
    }
    
    console.log('Creating Leaflet map...');
    map = L.map('map').setView([coordinates[0].lat, coordinates[0].lng], 12);
    console.log('Map created:', map);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        attribution: '&copy; OpenStreetMap', 
        maxZoom: 19 
    }).addTo(map);
    
    console.log('Creating markers...');
    createMarkers(coordinates);
    
    console.log('Creating straight polyline...');
    createStraightPolyline(coordinates);
    
    console.log('Getting OSRM route...');
    getOSRMRoute(coordinates);
    
    const bounds = L.latLngBounds(coordinates.map(c => [c.lat, c.lng]));
    map.fitBounds(bounds, { padding: [50, 50] });
    
    console.log('=== initializeMap() completed ===');
}

function createMarkers(coordinates) {
    coordinates.forEach((coord, index) => {
        let markerColor = index === 0 ? '#4CAF50' : (index === coordinates.length - 1 ? '#F44336' : '#2196F3');
        
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: ${markerColor}; width: 35px; height: 35px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">${String.fromCharCode(65 + index)}</div>`,
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
                <p style="margin: 0; font-size: 0.85em; color: #858796;"><strong>Titik ${String.fromCharCode(65 + index)}</strong><br>${index === 0 ? '🟢 Titik Awal' : (index === coordinates.length - 1 ? '🔴 Titik Akhir' : '🔵 Persinggahan ' + index)}</p>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        marker.on('click', function() { map.flyTo([coord.lat, coord.lng], 15, { animate: true, duration: 1.5 }); });
        markers.push(marker);
    });
}

function createStraightPolyline(coordinates) {
    const latlngs = coordinates.map(c => [c.lat, c.lng]);
    let totalStraightDistance = 0;
    for (let i = 0; i < coordinates.length - 1; i++) {
        totalStraightDistance += haversineDistance(coordinates[i].lat, coordinates[i].lng, coordinates[i + 1].lat, coordinates[i + 1].lng);
    }
    straightPolyline = L.polyline(latlngs, { color: '#2196F3', weight: 4, opacity: 0.7, dashArray: '15, 10', smoothFactor: 1 }).addTo(map);
    window.straightDistance = totalStraightDistance / 1000;
}

function getOSRMRoute(coordinates) {
    const coordsString = coordinates.map(c => `${c.lng},${c.lat}`).join(';');
    const osrmUrl = `{{ env('OSRM_SERVER_URL', 'https://router.project-osrm.org') }}/route/v1/driving/${coordsString}`;
    
    document.getElementById('directions-panel').innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div><p class="mt-2">Menghitung rute...</p></div>';
    document.getElementById('distance-comparison').innerHTML = '<div class="comparison-loading"><div class="spinner-border text-primary"></div><p class="mt-2">Menghitung jarak...</p></div>';
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000);
    
    fetch(osrmUrl + '?overview=full&steps=true&geometries=geojson', { signal: controller.signal })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('OSRM Response:', data);
            
            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                const geometry = route.geometry;
                const latlngs = geometry.coordinates.map(coord => [coord[1], coord[0]]);
                
                routePolyline = L.polyline(latlngs, { color: '#F44336', weight: 5, opacity: 0.8, smoothFactor: 1 }).addTo(map);
                window.routeDistance = route.distance / 1000;
                updateComparisonPanel();
                addToggleControls();
                displayDirections(route, coordinates);
            } else {
                console.error('OSRM Error:', data);
                handleOSRMError('OSRM tidak mengembalikan rute yang valid');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Fetch Error:', error);
            if (error.name === 'AbortError') {
                handleOSRMError('Request timeout (>15 detik). Server OSRM mungkin lambat atau tidak tersedia.');
            } else {
                handleOSRMError('Error: ' + error.message);
            }
        });
}

function handleOSRMError(message) {
    document.getElementById('directions-panel').innerHTML = `<div class="alert alert-warning m-2"><strong>⚠️ Tidak dapat menghitung rute OSRM</strong><br><small>${message}</small><br><small class="text-muted">Hanya akan ditampilkan jarak udara (garis lurus).</small></div>`;
    const straight = window.straightDistance || 0;
    document.getElementById('distance-comparison').innerHTML = `<div class="comparison-item comparison-blue"><div class="d-flex justify-content-between align-items-center"><div><strong>🔵 Jarak Udara</strong><p class="mb-0 small text-muted">Garis lurus (Haversine)</p></div><h4 class="mb-0 text-primary">${straight.toFixed(2)} km</h4></div></div><div class="alert alert-warning mt-2 mb-0"><small><strong>⚠️ Rute jalan tidak tersedia</strong><br>OSRM server tidak dapat dihubungi. Hanya jarak udara yang ditampilkan.</small></div>`;
    addToggleControlStraightOnly();
}

function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180, φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180, Δλ = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ/2) * Math.sin(Δλ/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function updateComparisonPanel() {
    const straight = window.straightDistance || 0, route = window.routeDistance || 0;
    const diff = route - straight, diffPercent = straight > 0 ? ((diff / straight) * 100).toFixed(1) : 0;
    document.getElementById('distance-comparison').innerHTML = `<div class="comparison-item comparison-blue"><div class="d-flex justify-content-between align-items-center"><div><strong>🔵 Jarak Udara</strong><p class="mb-0 small text-muted">Garis lurus (Haversine)</p></div><h4 class="mb-0 text-primary">${straight.toFixed(2)} km</h4></div></div><div class="comparison-item comparison-red"><div class="d-flex justify-content-between align-items-center"><div><strong>🔴 Rute Jalan</strong><p class="mb-0 small text-muted">Mengikuti jalan (OSRM)</p></div><h4 class="mb-0 text-danger">${route.toFixed(2)} km</h4></div></div><div class="comparison-item comparison-diff"><div class="d-flex justify-content-between align-items-center"><div><strong>📈 Selisih</strong><p class="mb-0 small text-muted">Perbedaan jarak</p></div><div class="text-right"><h4 class="mb-0 text-warning">${diff.toFixed(2)} km</h4><p class="mb-0 small text-muted">(+${diffPercent}%)</p></div></div></div>`;
}

function addToggleControls() {
    const toggleStraight = L.control({ position: 'topright' });
    toggleStraight.onAdd = function(map) {
        const button = L.DomUtil.create('div', 'leaflet-control-custom active');
        button.innerHTML = '🔵 Jarak Udara';
        L.DomEvent.on(button, 'click', function() {
            if (isStraightVisible) { map.removeLayer(straightPolyline); button.classList.remove('active'); button.style.opacity = '0.5'; }
            else { straightPolyline.addTo(map); button.classList.add('active'); button.style.opacity = '1'; }
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
            if (isRouteVisible) { map.removeLayer(routePolyline); button.classList.remove('active'); button.style.opacity = '0.5'; }
            else { routePolyline.addTo(map); button.classList.add('active'); button.style.opacity = '1'; }
            isRouteVisible = !isRouteVisible;
        });
        return button;
    };
    toggleRoute.addTo(map);
}

function addToggleControlStraightOnly() {
    const toggleStraight = L.control({ position: 'topright' });
    toggleStraight.onAdd = function(map) {
        const button = L.DomUtil.create('div', 'leaflet-control-custom active');
        button.innerHTML = '🔵 Jarak Udara';
        L.DomEvent.on(button, 'click', function() {
            if (isStraightVisible) { map.removeLayer(straightPolyline); button.classList.remove('active'); button.style.opacity = '0.5'; }
            else { straightPolyline.addTo(map); button.classList.add('active'); button.style.opacity = '1'; }
            isStraightVisible = !isStraightVisible;
        });
        return button;
    };
    toggleStraight.addTo(map);
}

function displayDirections(route, coordinates) {
    const panel = document.getElementById('directions-panel');
    const totalDistance = (route.distance / 1000).toFixed(2), totalDuration = Math.round(route.duration / 60);
    let html = `<div class="route-summary">📍 Total: ${totalDistance} km<br>⏱️ Waktu: ${totalDuration} menit</div>`;
    route.legs.forEach((leg, legIndex) => {
        html += `<div style="padding: 12px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); margin: 5px 10px; border-radius: 8px; font-weight: bold;">${coordinates[legIndex].name} → ${coordinates[legIndex + 1].name}</div>`;
        leg.steps.forEach((step, stepIndex) => {
            const distance = (step.distance / 1000).toFixed(2);
            const instruction = step.maneuver.type === 'depart' ? '🚗 Mulai perjalanan' : (step.maneuver.type === 'arrive' ? '🏁 Tiba di tujuan' : '➡️ ' + (step.name || 'Lanjutkan'));
            html += `<div class="direction-step"><div><strong>${stepIndex + 1}.</strong> ${instruction}</div><div class="step-distance">${distance} km</div></div>`;
        });
    });
    panel.innerHTML = html;
}

@if(session('success'))
    Swal.fire({ title: 'Berhasil!', text: '{{ session('success') }}', icon: 'success', confirmButtonColor: '#667eea' });
@endif
</script>
@endsection
