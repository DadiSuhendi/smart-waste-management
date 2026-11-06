@extends('layouts.app')
@section('title', 'Laporan sampah')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">KELOLA & PANTAU</span>
            <h1>{{ auth()->user()->isAdmin() ? 'Laporan sampah' : 'Laporan saya' }}</h1>
            <p>Cari laporan berdasarkan lokasi, kategori, atau status penanganannya.</p>
        </div><a href="{{ route('reports.create') }}" class="button primary"><x-icon name="plus" />Buat laporan</a>
    </div>
    <div class="status-tabs"><a class="{{ !request('status') ? 'selected' : '' }}"
            href="{{ route('reports.index', request()->except('status', 'page')) }}">Semua laporan</a>
        @foreach (\App\Enums\ReportStatus::cases() as $status)
            <a class="{{ request('status') === $status->value ? 'selected' : '' }}"
                href="{{ route('reports.index', array_merge(request()->except('page'), ['status' => $status->value])) }}">{{ $status->label() }}</a>
        @endforeach
    </div>
    <section class="panel">
        <form class="filter-bar" method="GET">
            <div class="search-field"><x-icon name="search" /><label class="sr-only" for="search">Cari
                    laporan</label><input id="search" name="q" value="{{ request('q') }}"
                    placeholder="Cari judul atau lokasi laporan..." maxlength="150"></div><label class="sr-only"
                for="category-filter">Kategori</label><select id="category-filter" name="category">
                <option value="">Semua kategori</option>
                @foreach (\App\Enums\WasteCategory::cases() as $category)
                    <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            <input type="hidden" name="status" value="{{ request('status') }}"><button
                class="button secondary">Terapkan</button>
            @if (request('q') || request('category'))
                <a class="text-link" href="{{ route('reports.index') }}">Reset</a>
            @endif
        </form><x-report-table :reports="$reports" /><x-pagination :items="$reports" />
    </section>
@endsection
