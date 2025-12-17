<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon rotate-n-15">
            {{-- <img src="/img/kominfo.svg" width="40dp" alt=""> --}}
        </div>
        <div class="sidebar-brand-text mx-3">IMM UM PONTIANAK</div>
    </a>
    <hr class="sidebar-divider my-0">

    <!-- Menu untuk Admin -->
    @if(Auth::user()->role == 'admin')
    <li class="nav-item {{ Request::routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="nav-item {{ Request::routeIs('destinasi.index') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('destinasi.index') }}">
            <i class="fas fa-map"></i>
            <span>Destinasi</span>
        </a>
    </li>

    <li class="nav-item {{ Request::routeIs('bobot') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('bobot') }}">
            <i class="fas fa-calculator"></i>
            <span>Bobot</span>
        </a>
    </li>

    <li class="nav-item {{ Request::routeIs('optimasi') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('optimasi') }}">
            <i class="fas fa-route"></i>
            <span>Optimasi</span>
        </a>
    </li>
    @endif

    <!-- Menu untuk Pengguna -->
    @if(Auth::user()->role == 'user')
        <li class="nav-item {{ Request::routeIs('dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item {{ Request::routeIs('rute_optimal') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('rute_optimal') }}">
                <i class="fas fa-route"></i>
                <span>Rute Terbaik</span>
            </a>
        </li>
    @endif

    <!-- Menu Kembali ke Home -->
    <li class="nav-item">
        <a class="nav-link" href="{{ url('/') }}">
            <i class="fas fa-home"></i>
            <span>Kembali ke Home</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
