@extends('layouts.auth')
@section('title', 'Daftar')
@section('content')
    <span class="eyebrow">AMBIL BAGIAN</span>
    <h1>Buat akun Anda</h1>
    <p class="muted auth-intro">Mulai laporkan, bantu lingkungan jadi lebih baik.</p>
    <form method="POST" action="{{ route('register.store') }}" class="stack">@csrf
        <label>Nama lengkap<input name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name"
                placeholder="Nama lengkap Anda"></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                placeholder="nama@email.com"></label>
        <label>Kata sandi<input type="password" name="password" required minlength="8" autocomplete="new-password"
                placeholder="Minimal 8 karakter, huruf dan angka"></label>
        <label>Konfirmasi kata sandi<input type="password" name="password_confirmation" required autocomplete="new-password"
                placeholder="Ulangi kata sandi"></label>
        <button class="button primary full">Buat akun <x-icon name="arrow" /></button>
    </form>
    <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
@endsection
