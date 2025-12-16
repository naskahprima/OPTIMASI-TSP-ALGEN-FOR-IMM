@extends('admin.layouts.app')

@section('title', 'Destinasi Page')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Tambah Data Destinasi</h6>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('destinasi.create.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group row">
                                <div class="col-md-4 mb-3">
                                    <input type="text" class="form-control form-control-user @error('destination_code') is-invalid @enderror" id="destination_code" name="destination_code" placeholder="Destinasi Code" value="{{ old('destination_code') ?? $destinationCode }}">
                                    @error('destination_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <input type="text" class="form-control form-control-user @error('name') is-invalid @enderror" id="name" name="name" placeholder="Name" value="{{ old('name') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <input type="file" class="form-control form-control-user @error('img') is-invalid @enderror" id="img" name="img" accept="image/*">
                                    @error('img')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-12 mb-3">
                                    <textarea class="form-control form-control-user @error('description') is-invalid @enderror" id="description" name="description" placeholder="Description" rows="4">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card shadow p-3 mb-3">
                                <div id="map"></div>
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <input type="text" class="form-control form-control-user @error('lat') is-invalid @enderror" id="lat" name="lat" placeholder="Latitude" readonly value="{{ old('lat') }}">
                                    @error('lat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control form-control-user @error('lng') is-invalid @enderror" id="lng" name="lng" placeholder="Longitude" readonly value="{{ old('lng') }}">
                                    @error('lng')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-user btn-block">Tambah Data</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Inisialisasi map setelah document ready
    document.addEventListener('DOMContentLoaded', function() {
        // Koordinat default (Pontianak)
        const defaultLat = -0.033271;
        const defaultLng = 109.333557;
        
        // Inisialisasi Leaflet map
        const map = L.map('map').setView([defaultLat, defaultLng], 13);
        
        // Tambahkan tile layer (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);
        
        // Buat marker yang bisa di-drag
        const marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map);
        
        // Popup untuk info marker
        marker.bindPopup('<b>Drag marker ini!</b><br>Pilih lokasi destinasi.').openPopup();
        
        // Update input lat/lng saat marker di-drag
        marker.on('dragend', function(event) {
            const position = event.target.getLatLng();
            document.getElementById('lat').value = position.lat.toFixed(6);
            document.getElementById('lng').value = position.lng.toFixed(6);
        });
        
        // Optional: Click map untuk pindah marker
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('lng').value = e.latlng.lng.toFixed(6);
        });
        
        // Set initial values
        document.getElementById('lat').value = defaultLat;
        document.getElementById('lng').value = defaultLng;
    });
</script>
@endpush