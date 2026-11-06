<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#143e34">
    <title>@yield('title', 'Dashboard') · Bersih</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <a class="skip-link" href="#main">Lewati ke konten</a><button class="sidebar-overlay" aria-label="Tutup menu"
        data-close-menu></button>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand-row"><a class="brand" href="{{ route('dashboard') }}"><span
                    class="brand-mark"><x-icon name="leaf" /></span>bersih<span class="brand-dot">.</span></a><button
                class="icon-button sidebar-close" type="button" aria-label="Tutup menu" data-close-menu><x-icon
                    name="close" /></button></div>
        <div class="workspace-label"><span class="tiny-dot"></span> Smart Waste Management</div>
        @if (config('app.demo'))
            <div class="demo-label">Workspace demo · data contoh</div>
        @endif
        <p class="nav-label">WORKSPACE</p>
        <nav aria-label="Navigasi utama">
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}"><x-icon name="grid" />Ringkasan</a>
            <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                href="{{ route('reports.index') }}"><x-icon
                    name="report" />{{ auth()->user()->isAdmin() ? 'Laporan sampah' : 'Laporan saya' }}</a>
            @if (auth()->user()->isAdmin())
                <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
                    href="{{ route('users.index') }}"><x-icon name="users" />Pengguna</a>
            @endif
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-note"><x-icon name="scan" /><strong>Kenali sampah dari foto</strong>
                <p>Organik, plastik, atau kertas. Coba deteksi saat membuat laporan.</p>
            </div>
            <a class="nav-item {{ request()->routeIs('guide') ? 'active' : '' }}" href="{{ route('guide') }}"><x-icon
                    name="help" />Panduan penggunaan</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-item logout"
                    type="submit"><x-icon name="logout" />Keluar</button></form>
            <div class="sidebar-footer">Bersih © {{ now()->year }} <span>v1.0</span></div>
        </div>
    </aside>
    <div class="app-shell">
        <header class="topbar">
            <div class="breadcrumb"><button type="button" class="icon-button mobile-menu" data-toggle-menu
                    aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"><x-icon
                        name="menu" /></button><span>Workspace</span><x-icon
                    name="chevron" /><strong>@yield('title', 'Ringkasan')</strong></div>
            <div class="profile">
                <div class="profile-copy">
                    <strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isAdmin() ? 'Administrator' : 'Warga' }}</small>
                </div><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
        </header>
        <main id="main" class="main-content">
            @if (session('success'))
                <div class="alert success" role="status"><x-icon name="check" />{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert error" role="alert">
                    <div><strong>Periksa kembali isian Anda.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @yield('content')<footer class="content-footer"><span>Bersama, wujudkan lingkungan yang lebih
                    bersih.</span><span>Smart Waste Management</span></footer>
        </main>
    </div>
</body>

</html>
