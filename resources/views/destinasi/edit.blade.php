@extends('admin.layouts.app')

@section('title', 'Edit Destinasi')

@section('content')
<div class="container">
    <div class="row">
        <div class="col">
            <div class="card glass-card mb-4 shadow-lg">
                <div class="card-header glass-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">✏️ Edit Data Destinasi</h6>
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

                    <form method="POST" action="{{ route('destinasi.update', $destinasi->id) }}" enctype="multipart/form-data" id="destinasiForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-row">
                            <div class="col-md-4 mb-3">
                                <label for="destination_code"><strong>Kode Destinasi</strong></label>
                                <input type="text" class="form-control glass-input @error('destination_code') is-invalid @enderror" 
                                       id="destination_code" name="destination_code" 
                                       value="{{ old('destination_code', $destinasi->destination_code) }}" readonly>
                                @error('destination_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="name"><strong>Nama Destinasi</strong> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control glass-input @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $destinasi->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="img"><strong>Gambar</strong></label>
                                <input type="file" class="form-control glass-input @error('img') is-invalid @enderror" 
                                       id="img" name="img" accept="image/jpeg,image/png,image/jpg">
                                <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                                @error('img')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <!-- Current image preview -->
                                @if($destinasi->img)
                                <div class="mt-2">
                                    <p class="mb-1"><small>Gambar saat ini:</small></p>
                                    <img src="{{ asset('storage/images/destinations/' . $destinasi->img) }}" 
                                         alt="Current" id="currentImage"
                                         style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                </div>
                                @endif
                                
                                <!-- New image preview -->
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <p class="mb-1"><small>Preview gambar baru:</small></p>
                                    <img id="preview" src="" alt="Preview" 
                                         style="max-width: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description"><strong>Deskripsi</strong> <span class="text-danger">*</span></label>
                            <textarea class="form-control glass-input @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" required>{{ old('description', $destinasi->description) }}</textarea>
                            <small class="form-text text-muted"><span id="charCount">{{ strlen($destinasi->description) }}</span>/1000 karakter</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location Search -->
                        <div class="card glass-card-inner mb-3 shadow">
                            <div class="card-body">
                                <h6 class="mb-3"><strong>🔍 Cari Lokasi Destinasi</strong></h6>
                                <div class="form-group">
                                    <input type="text" class="form-control glass-input" 
                                           id="location-search" 
                                           placeholder="Ketik nama tempat untuk mencari...">
                                    <small class="form-text text-muted">💡 Klik map atau drag marker untuk mengubah lokasi</small>
                                </div>
                                <div id="map" style="height: 400px; border-radius: 10px; overflow: hidden;"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-md-6 mb-3">
                                <label for="lat"><strong>Latitude</strong></label>
                                <input type="text" class="form-control glass-input @error('lat') is-invalid @enderror" 
                                       id="lat" name="lat" readonly required value="{{ old('lat', $destinasi->lat) }}">
                                @error('lat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lng"><strong>Longitude</strong></label>
                                <input type="text" class="form-control glass-input @error('lng') is-invalid @enderror" 
                                       id="lng" name="lng" readonly required value="{{ old('lng', $destinasi->lng) }}">
                                @error('lng')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block glass-button" id="submitBtn">
                            <i class="fas fa-save"></i> Update Destinasi
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

<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css" />

<style>
.glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; }
.glass-card-inner { background: rgba(248, 249, 252, 0.8); border: 1px solid rgba(102, 126, 234, 0.2); border-radius: 10px; }
.glass-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.glass-input { background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px; transition: all 0.3s ease; }
.glass-input:focus { background: white; border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
.glass-button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px; padding: 12px 30px; color: white; font-weight: bold; }
.glass-button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
.glass-button-secondary { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); border: none; border-radius: 10px; padding: 12px 30px; color: white; font-weight: bold; }
.glass-alert-danger { background: rgba(248, 215, 218, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #dc3545; border-radius: 10px; }
</style>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>

<script>
let map, marker, geocoder;

document.addEventListener('DOMContentLoaded', function() {
    const destinasiLat = {{ $destinasi->lat }};
    const destinasiLng = {{ $destinasi->lng }};
    
    map = L.map('map').setView([destinasiLat, destinasiLng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);
    
    marker = L.marker([destinasiLat, destinasiLng], { draggable: true }).addTo(map);
    marker.bindPopup('<b>{{ $destinasi->name }}</b><br>Drag untuk ubah lokasi').openPopup();
    
    marker.on('dragend', function(event) {
        const pos = event.target.getLatLng();
        updateCoordinates(pos.lat, pos.lng);
    });
    
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });
    
    geocoder = L.Control.Geocoder.nominatim({ geocodingQueryParams: { countrycodes: 'id', limit: 5 } });
    
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
                    map.flyTo(result.center, 16, { animate: true, duration: 1.5 });
                    marker.setLatLng(result.center);
                    updateCoordinates(result.center.lat, result.center.lng);
                    marker.bindPopup(`<b>📍 ${result.name}</b>`).openPopup();
                }
            });
        }, 500);
    });
    
    // Image preview
    document.getElementById('img').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2048000) {
                alert('❌ Ukuran file terlalu besar! Maksimal 2MB');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('currentImage').style.opacity = '0.5';
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Character counter
    const desc = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    desc.addEventListener('input', function() {
        const len = this.value.length;
        charCount.textContent = len;
        charCount.style.color = len > 1000 ? 'red' : (len > 800 ? 'orange' : 'inherit');
    });
    
    // Form submit
    document.getElementById('destinasiForm').addEventListener('submit', function() {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengupdate...';
    });
});

function updateCoordinates(lat, lng) {
    document.getElementById('lat').value = lat.toFixed(6);
    document.getElementById('lng').value = lng.toFixed(6);
}
</script>
@endpush