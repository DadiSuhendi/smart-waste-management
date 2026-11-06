@extends('layouts.app')
@section('title', $user->exists ? 'Edit pengguna' : 'Tambah pengguna')
@section('content')
    <a class="back-link" href="{{ route('users.index') }}"><x-icon name="back" />Semua pengguna</a>
    <div class="page-heading">
        <div>
            <h1>{{ $user->exists ? 'Edit pengguna' : 'Tambah pengguna' }}</h1>
            <p>Tentukan informasi akun dan hak akses pengguna.</p>
        </div>
    </div>
    <section class="panel form-section narrow">
        <form class="stack" method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf @if ($user->exists)
                @method('PUT')
            @endif
            <label>Nama lengkap<input name="name" required maxlength="100" value="{{ old('name', $user->name) }}"
                    autocomplete="name"></label>
            <label>Email<input name="email" type="email" required value="{{ old('email', $user->email) }}"
                    autocomplete="email"></label>
            <label>Role<select name="role">
                    <option value="user" @selected(old('role', $user->role) === 'user')>User · melaporkan dan memantau</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin · mengelola seluruh laporan dan pengguna
                    </option>
                </select></label>
            <label>Kata sandi {{ $user->exists ? '(opsional)' : '' }}<input type="password" name="password" minlength="8"
                    @required(!$user->exists)
                    autocomplete="new-password"><small>{{ $user->exists ? 'Kosongkan jika tidak ingin mengubah kata sandi. ' : '' }}Minimal
                    8 karakter, huruf dan angka.</small></label>
            <label>Konfirmasi kata sandi<input type="password" name="password_confirmation"
                    autocomplete="new-password"></label>
            <div class="action-row"><a class="button secondary" href="{{ route('users.index') }}">Batal</a><button
                    class="button primary">Simpan pengguna</button></div>
        </form>
    </section>
@endsection
