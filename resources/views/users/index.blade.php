@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
    <div class="page-heading directory-page-heading">
        <div>
            <h1 id="users-heading">Pengguna</h1>
            <p>Kelola akun dan akses ke Bersih.</p>
        </div>
        <a class="button primary" href="{{ route('users.create') }}"><x-icon name="plus" />Tambah pengguna</a>
    </div>

    <section class="panel directory-panel" aria-labelledby="users-heading">
        <div class="directory-toolbar">
            <form class="directory-search" method="GET" action="{{ route('users.index') }}" role="search">
                <div class="directory-search-field">
                    <label class="sr-only" for="user-search">Cari nama atau email pengguna</label>
                    <x-icon name="search" />
                    <input type="search" name="q" id="user-search" value="{{ request('q') }}"
                        placeholder="Cari nama atau email" maxlength="150">
                </div>
                <button class="button secondary" type="submit">Cari</button>
                @if (request('q'))
                    <a class="icon-button directory-reset" href="{{ route('users.index') }}" aria-label="Hapus pencarian"
                        title="Hapus pencarian"><x-icon name="close" /></a>
                @endif
            </form>
            <span class="directory-total">{{ number_format($users->total()) }} pengguna</span>
        </div>

        @if ($users->isNotEmpty())
            <div class="directory-head" aria-hidden="true">
                <span>Nama & email</span>
                <span>Akses</span>
                <span>Laporan</span>
                <span>Bergabung</span>
                <span class="directory-actions-heading">Aksi</span>
            </div>
            <ul class="directory-list" role="list">
                @foreach ($users as $user)
                    <li class="directory-row">
                        <div class="directory-person">
                            <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <div class="directory-person-copy">
                                <div class="directory-name">
                                    <strong>{{ $user->name }}</strong>
                                    @if ($user->id === auth()->id())
                                        <span class="directory-self">Anda</span>
                                    @endif
                                </div>
                                <span class="directory-email">{{ $user->email }}</span>
                            </div>
                        </div>
                        <div class="directory-role">
                            <span class="sr-only">Akses: </span>
                            <span
                                class="role-badge {{ $user->isAdmin() ? 'admin' : '' }}">{{ $user->isAdmin() ? 'Admin' : 'User' }}</span>
                        </div>
                        <div class="directory-metadata">
                            <div class="directory-reports">
                                <strong>{{ number_format($user->reports_count) }}</strong>
                                <span class="directory-meta-label">laporan</span>
                            </div>
                            <div class="directory-joined">
                                <span class="directory-meta-label">Bergabung</span>
                                <time
                                    datetime="{{ $user->created_at->toDateString() }}">{{ $user->created_at->translatedFormat('d M Y') }}</time>
                            </div>
                        </div>
                        <div class="directory-actions">
                            <a class="button secondary" href="{{ route('users.edit', $user) }}"
                                aria-label="Edit {{ $user->name }}"><x-icon name="edit" />Edit</a>
                            @if ($user->id !== auth()->id() && !$user->reports_count)
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                    data-confirm="Hapus akun pengguna ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-button directory-delete" type="submit"
                                        aria-label="Hapus {{ $user->name }}" title="Hapus pengguna"><x-icon
                                            name="trash" /></button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty-state">
                <span class="empty-icon"><x-icon name="users" /></span>
                <h2>Pengguna tidak ditemukan</h2>
                <p>Coba gunakan nama atau email lain.</p>
                <a class="button secondary" href="{{ route('users.index') }}">Tampilkan semua pengguna</a>
            </div>
        @endif
        <x-pagination :items="$users" />
    </section>
@endsection
