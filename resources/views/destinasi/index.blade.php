@extends('admin.layouts.app')

@section('title', 'Daftar Destinasi')

@section('content')
<div class="container">
    <div class="card glass-card shadow-lg mb-4">
        <div class="card-header glass-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-white">📍 Daftar Destinasi</h6>
            <div>
                <a href="{{ route('destinasi.create') }}" class="btn btn-light btn-sm glass-btn-light">
                    <i class="fas fa-plus"></i> Tambah Data
                </a>
                @if($totalDestinasi > 0)
                <button type="button" class="btn btn-danger btn-sm glass-btn-danger" id="resetAllBtn">
                    <i class="fas fa-exclamation-triangle"></i> Reset Semua Data
                </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <!-- Statistics -->
            <div class="alert glass-alert-info mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <strong>📊 Total Destinasi:</strong> {{ $totalDestinasi }}
                    </div>
                    <div class="col-md-6 text-right">
                        <small class="text-muted">
                            @if($totalDestinasi >= 2)
                                ✅ Siap untuk generate TSP
                            @else
                                ⚠️ Minimal 2 destinasi untuk TSP
                            @endif
                        </small>
                    </div>
                </div>
            </div>

            @if($totalDestinasi == 0)
                <div class="text-center py-5">
                    <!-- Inline SVG 404 Empty State -->
                    <svg width="300" height="300" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.5;">
                        <circle cx="200" cy="200" r="180" fill="#f8f9fc" stroke="#e3e6f0" stroke-width="4"/>
                        <path d="M150 180 C150 170, 160 160, 170 160 C180 160, 190 170, 190 180" stroke="#858796" stroke-width="4" fill="none"/>
                        <path d="M210 180 C210 170, 220 160, 230 160 C240 160, 250 170, 250 180" stroke="#858796" stroke-width="4" fill="none"/>
                        <path d="M140 240 C160 260, 240 260, 260 240" stroke="#858796" stroke-width="4" fill="none" stroke-linecap="round"/>
                        <text x="200" y="330" text-anchor="middle" font-size="24" fill="#858796" font-family="Arial, sans-serif">Belum Ada Data</text>
                    </svg>
                    <h4 class="mt-3 text-muted">Belum Ada Destinasi</h4>
                    <p class="text-muted">Tambahkan destinasi pertama untuk memulai</p>
                    <a href="{{ route('destinasi.create') }}" class="btn btn-primary glass-button mt-2">
                        <i class="fas fa-plus"></i> Tambah Destinasi
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">Kode</th>
                                <th width="25%">Nama</th>
                                <th width="20%">Koordinat</th>
                                <th width="15%">Gambar</th>
                                <th width="15%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($destinasi as $destination)
                            <tr>
                                <td><span class="badge badge-primary">{{ $destination->destination_code }}</span></td>
                                <td>
                                    <strong>{{ $destination->name }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($destination->description, 50) }}</small>
                                </td>
                                <td>
                                    <small>
                                        <strong>Lat:</strong> {{ number_format($destination->lat, 6) }}<br>
                                        <strong>Lng:</strong> {{ number_format($destination->lng, 6) }}
                                    </small>
                                </td>
                                <td>
                                    @if($destination->img)
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" 
                                            data-target="#imageModal" 
                                            data-img="{{ asset('storage/images/destinations/' . $destination->img) }}"
                                            data-name="{{ $destination->name }}">
                                        <i class="fas fa-eye"></i> Lihat
                                    </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('destinasi.edit', $destination->id) }}" 
                                       class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="confirmDelete({{ $destination->id }}, '{{ $destination->name }}')" 
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $destination->id }}" 
                                          action="{{ route('destinasi.destroy', $destination->id) }}" 
                                          method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content glass-modal">
            <div class="modal-header glass-header">
                <h5 class="modal-title text-white" id="imageModalLabel">
                    <i class="fas fa-image"></i> Preview Gambar
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <h6 id="modalDestName" class="mb-3"></h6>
                <img id="modalImage" src="" alt="Image" style="width: 100%; max-height: 500px; object-fit: contain; border-radius: 10px;">
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content glass-modal">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle"></i> Peringatan!
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>⚠️ TINDAKAN INI TIDAK BISA DIBATALKAN!</strong>
                </div>
                <p>Anda akan menghapus <strong>SEMUA DATA</strong> berikut:</p>
                <ul class="text-left">
                    <li>✖️ Semua Destinasi ({{ $totalDestinasi }} data)</li>
                    <li>✖️ Semua Matriks Jarak</li>
                    <li>✖️ Semua Rute Optimal</li>
                    <li>✖️ Semua Gambar Destinasi</li>
                </ul>
                <hr>
                <form id="resetForm" action="{{ route('destinasi.reset') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="resetConfirmation">
                            <strong>Ketik "RESET" untuk konfirmasi:</strong>
                        </label>
                        <input type="text" class="form-control glass-input" 
                               id="resetConfirmation" name="confirmation" 
                               placeholder="Ketik RESET (huruf besar)" 
                               autocomplete="off" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block" id="confirmResetBtn" disabled>
                        <i class="fas fa-trash-alt"></i> Ya, Reset Semua Data
                    </button>
                    <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.glass-card { 
    background: rgba(255, 255, 255, 0.95); 
    backdrop-filter: blur(10px); 
    border-radius: 15px; 
    border: 1px solid rgba(255, 255, 255, 0.3);
}
.glass-header { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    border: none;
}
.glass-btn-light { 
    background: white; 
    border: none; 
    font-weight: 600; 
    transition: all 0.3s; 
}
.glass-btn-light:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
}
.glass-btn-danger { 
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
    color: white; 
    border: none; 
    font-weight: 600; 
}
.glass-btn-danger:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 4px 12px rgba(220,53,69,0.4); 
    color: white;
}
.glass-alert-info { 
    background: rgba(217, 237, 247, 0.95); 
    border-left: 4px solid #17a2b8; 
    border-radius: 10px; 
}
.glass-modal .modal-content { 
    background: rgba(255, 255, 255, 0.98); 
    backdrop-filter: blur(10px); 
    border-radius: 15px; 
}
.glass-button { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    border: none; 
    color: white; 
    font-weight: bold; 
    padding: 10px 25px; 
    border-radius: 8px; 
    transition: all 0.3s;
}
.glass-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
}
.glass-input { 
    border: 2px solid #dc3545; 
    border-radius: 8px; 
}
.glass-input:focus { 
    border-color: #c82333; 
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); 
}
.table-hover tbody tr:hover { 
    background-color: rgba(102, 126, 234, 0.05); 
}
</style>
@endsection

@push('scripts')
<script>
// Image modal
$('#imageModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const imgSrc = button.data('img');
    const name = button.data('name');
    $('#modalImage').attr('src', imgSrc);
    $('#modalDestName').text(name);
});

// Delete confirmation
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Hapus Destinasi?',
        html: `Anda yakin ingin menghapus <strong>"${name}"</strong>?<br><small class="text-muted">Data matriks jarak terkait juga akan dihapus</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
        cancelButtonText: '<i class="fas fa-times"></i> Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
}

// Reset all button
document.getElementById('resetAllBtn')?.addEventListener('click', function() {
    $('#resetModal').modal('show');
});

// Reset confirmation input validation
document.getElementById('resetConfirmation')?.addEventListener('input', function() {
    const btn = document.getElementById('confirmResetBtn');
    if (this.value === 'RESET') {
        btn.disabled = false;
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-danger');
    } else {
        btn.disabled = true;
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-secondary');
    }
});

// Reset modal on close - clear input
$('#resetModal').on('hidden.bs.modal', function () {
    document.getElementById('resetConfirmation').value = '';
    const btn = document.getElementById('confirmResetBtn');
    btn.disabled = true;
    btn.classList.remove('btn-danger');
    btn.classList.add('btn-secondary');
});

// Reset form submit
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('confirmResetBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
});

// Success/Error messages
@if(session('success'))
    Swal.fire({
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        icon: 'success',
        confirmButtonColor: '#667eea',
        confirmButtonText: 'OK'
    });
@endif

@if(session('error'))
    Swal.fire({
        title: 'Error!',
        text: '{{ session('error') }}',
        icon: 'error',
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'OK'
    });
@endif
</script>
@endpush