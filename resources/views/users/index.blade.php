@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">ADMINISTRASI</span>
            <h1>Manajemen pengguna</h1>
            <p>Kelola akun dan akses workspace Bersih.</p>
        </div><a class="button primary" href="{{ route('users.create') }}"><x-icon name="plus" />Tambah pengguna</a>
    </div>
    <section class="panel">
        <form class="filter-bar">
            <div class="search-field"><x-icon name="search" /><label class="sr-only" for="user-search">Cari
                    pengguna</label><input name="q" id="user-search" value="{{ request('q') }}"
                    placeholder="Cari nama atau email..." maxlength="150"></div><button
                class="button secondary">Cari</button>
        </form>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Laporan</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><span class="person-cell"><span
                                        class="avatar">{{ mb_substr($user->name, 0, 1) }}</span><span><strong>{{ $user->name }}</strong><small
                                            class="block muted">{{ $user->email }}</small></span></span></td>
                            <td><span
                                    class="role-badge {{ $user->isAdmin() ? 'admin' : '' }}">{{ $user->isAdmin() ? 'Admin' : 'User' }}</span>
                            </td>
                            <td>{{ $user->reports_count }}</td>
                            <td>{{ $user->created_at->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="action-row"><a class="icon-button" aria-label="Edit {{ $user->name }}"
                                        href="{{ route('users.edit', $user) }}"><x-icon name="edit" /></a>
                                    @if ($user->id !== auth()->id() && !$user->reports_count)
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                            data-confirm="Hapus akun pengguna ini?">@csrf @method('DELETE')<button
                                                class="icon-button danger-text"
                                                aria-label="Hapus {{ $user->name }}"><x-icon name="trash" /></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <h3>Pengguna tidak ditemukan</h3>
                                    <p>Coba gunakan kata pencarian lain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div><x-pagination :items="$users" />
    </section>
@endsection
