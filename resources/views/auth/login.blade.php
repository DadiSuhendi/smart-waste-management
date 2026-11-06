@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
    <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
    <h1>Masuk ke Bersih</h1>
    <p class="muted auth-intro">Lanjutkan langkah baik untuk lingkungan Anda.</p>
    <form method="POST" action="{{ route('login.store') }}" class="stack">@csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                placeholder="nama@email.com" autofocus></label>
        <label>Kata sandi<input type="password" name="password" required autocomplete="current-password"
                placeholder="Masukkan kata sandi"></label>
        <label class="checkbox-label"><input type="checkbox" name="remember" value="1">Ingat saya</label>
        <button type="submit" class="button primary full">Masuk <x-icon name="arrow" /></button>
    </form>
    <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
@endsection
