@extends('admin.layouts.app')

@section('title', 'Tambah Destinasi')

@section('content')
<div class="container">
    <div class="row">
        <div class="col">
            <div class="card glass-card mb-4 shadow-lg">
                <div class="card-header glass-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">📍 Tambah Data Destinasi</h6>
                </div>
                <div class="card-body">
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
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('destinasi.create.store') }}" enctype="multipart/form-data" id="destinasiForm">
                        @csrf
                        
                        <div class="form-row">
                            <div class="col-md-4 mb-3">
                                <label for="destination_code"><strong>Kode Destinasi</strong> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control glass-input @error('destination_code') is-invalid @enderror" 
                                       id="destination_code" name="destination_code" 
                                       placeholder="001" value="{{ old('destination_code') ?? $destinationCode }}" readonly>
                                @error('destination_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="name"><strong>Nama Destinasi</strong> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control glass-input @error('name') is-invalid @enderror" 
                                       id="name" name="name" 
                                       placeholder="Contoh: Masjid Mujahidin" 
                                       value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="img"><strong>Gambar</strong> <span class="text-danger">*</span></label>
                                <input type="file" class="form-control glass-input @error('img') is-invalid @enderror" 
                                       id="img" name="img" accept="image/jpeg,image/png,image/jpg" required>
                                <small class="form-text text-muted">Max 2MB, format: JPG, PNG</small>
                                @error('img')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <img id="preview" src="" alt="Preview" style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description"><strong>Deskripsi</strong> <span class="text-danger">*</span></label>
                            <textarea class="form-control glass-input @error('description') is-invalid @enderror" 
                                      id="description" name="description" 
                                      placeholder="Deskripsi destinasi..." rows="3" required>{{ old('description') }}</textarea>
                            <small class="form-text text-muted"><span id="charCount">0</span>/1000 karakter</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location Search -->
                        <div class="card glass-card-inner mb-3 shadow">
                            <div class="card-body">
                                <h6 class="mb-3"><strong>🔍 Cari Lokasi Destinasi</strong></h6>
                                <div class="form-group">
                                    <label for="location-search"><strong>Ketik nama tempat atau alamat</strong></label>
                                    <input type="text" class="form-control glass-input" 
                                           id="location-search" 
                                           placeholder="Contoh: Masjid Mujahidin Pontianak">
                                    <small class="form-text text-muted">💡 Tips: Ketik nama tempat lengkap untuk hasil lebih akurat</small>
                                </div>
                                <div id="map" style="height: 400px; border-radius: 10px; overflow: hidden;"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-md-6 mb-3">
                                <label for="lat"><strong>Latitude</strong> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control glass-input @error('lat') is-invalid @enderror" 
                                       id="lat" name="lat" placeholder="-0.033271" readonly required value="{{ old('lat') }}">
                                @error('lat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lng"><strong>Longitude</strong> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control glass-input @error('lng') is-invalid @enderror" 
                                       id="lng" name="lng" placeholder="109.333557" readonly required value="{{ old('lng') }}">
                                @error('lng')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block glass-button" id="submitBtn">
                            <i class="fas fa-save"></i> Simpan Destinasi
                        </button>
                        <a href="{{ route('destinasi.index') }}" class="btn btn-secondary btn-lg btn-block glass-button-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet Geocoder CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css" />

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
}

.glass-card-inner {
    background: rgba(248, 249, 252, 0.8);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 10px;
}

.glass-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.glass-input {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.glass-input:focus {
    background: rgba(255, 255, 255, 1);
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.glass-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    padding: 12px 30px;
    color: white;
    font-weight: bold;
    transition: all 0.3s ease;
}

.glass-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.glass-button-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border: none;
    border-radius: 10px;
    padding: 12px 30px;
    color: white;
    font-weight: bold;
}

.glass-alert-danger {
    background: rgba(248, 215, 218, 0.95);
    backdrop-filter: blur(10px);
    border-left: 4px solid #dc3545;
    border-radius: 10px;
}

/* Geocoder custom style */
.leaflet-control-geocoder {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.leaflet-control-geocoder-form input {
    border-radius: 8px;
    padding: 8px 12px;
}
</style>
@endsection

@push('scripts')
<!-- Leaflet Geocoder JS -->
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>

<script>
let map, marker, geocoder;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const defaultLat = -0.033271;
    const defaultLng = 109.333557;
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
    
    // Draggable marker
    marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
    marker.bindPopup('<b>📍 Drag marker ini!</b><br>Atau cari lokasi di atas').openPopup();
    
    // Update coordinates on drag
    marker.on('dragend', function(event) {
        const position = event.target.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });
    
    // Click map to move marker
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });
    
    // Initialize Geocoder
    geocoder = L.Control.Geocoder.nominatim({
        geocodingQueryParams: {
            countrycodes: 'id', // Prioritize Indonesia
            limit: 5
        }
    });
    
    // Location search functionality
    const searchInput = document.getElementById('location-search');
    let searchTimeout;
    
    searchInput.addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 3) return;
        
        searchTimeout = setTimeout(() => {
            geocoder.geocode(query, function(results) {
                if (results && results.length > 0) {
                    const result = results[0];
                    const latlng = result.center;
                    
                    // Move map and marker
                    map.flyTo(latlng, 16, {
                        animate: true,
                        duration: 1.5
                    });
                    
                    marker.setLatLng(latlng);
                    updateCoordinates(latlng.lat, latlng.lng);
                    
                    marker.bindPopup(`<b>📍 ${result.name}</b><br>${result.html || ''}`).openPopup();
                }
            });
        }, 500);
    });
    
    // Set initial values
    updateCoordinates(defaultLat, defaultLng);
    
    // Image preview
    document.getElementById('img').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Check file size
            if (file.size > 2048000) {
                alert('❌ Ukuran file terlalu besar! Maksimal 2MB');
                e.target.value = '';
                document.getElementById('imagePreview').style.display = 'none';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Character counter
    const descTextarea = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    
    descTextarea.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;
        
        if (length > 1000) {
            charCount.style.color = 'red';
        } else {
            charCount.style.color = length > 800 ? 'orange' : 'inherit';
        }
    });
    
    // Form validation & submit handling
    document.getElementById('destinasiForm').addEventListener('submit', function(e) {
        const lat = document.getElementById('lat').value;
        const lng = document.getElementById('lng').value;
        
        if (!lat || !lng || lat === '0' || lng === '0') {
            e.preventDefault();
            alert('❌ Lokasi belum dipilih! Pilih lokasi di map terlebih dahulu.');
            return false;
        }
        
        // Disable submit button to prevent double-click
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    });
});

function updateCoordinates(lat, lng) {
    document.getElementById('lat').value = lat.toFixed(6);
    document.getElementById('lng').value = lng.toFixed(6);
}
</script>
@endpush