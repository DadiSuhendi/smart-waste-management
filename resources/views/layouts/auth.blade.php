<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title') · Bersih</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">
    <div class="auth-aside"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><x-icon
                    name="leaf" /></span>bersih.</a>
        <div class="auth-message"><span class="eyebrow">MULAI DARI SEKITAR KITA</span>
            <h1>Lingkungan bersih,<br>mulai dari kita.</h1>
            <p>Temukan sampah di sekitar Anda? Kirim foto dan lokasinya, lalu pantau penanganannya.</p>
            <div class="auth-category-list">
                <div><x-icon name="leaf" /><span>Organik</span></div>
                <div><x-icon name="bottle" /><span>Plastik</span></div>
                <div><x-icon name="report" /><span>Kertas</span></div>
            </div>
            <div class="auth-steps"><span>01 &nbsp; Temukan</span><span>02 &nbsp; Laporkan</span><span>03 &nbsp;
                    Pantau</span></div>
        </div><small>Smart Waste Management System</small>
    </div>
    <main class="auth-main">
        <div class="auth-form"><a class="brand auth-mobile-brand" href="{{ route('login') }}"><span
                    class="brand-mark"><x-icon name="leaf" /></span>bersih.</a>
            @if ($errors->any())
                <div class="alert error" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif @yield('content')
        </div><small class="muted">Satu laporan Anda bisa membuat perbedaan.</small>
    </main>
</body>

</html>
