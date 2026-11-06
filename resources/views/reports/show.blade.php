@extends('layouts.app')
@section('title', 'Detail laporan')
@section('content')
    <a class="back-link" href="{{ route('reports.index') }}">
        <x-icon name="back" />Semua laporan</a>
    <div class="page-heading">
        <div><span class="eyebrow">{{ $report->reference() }} ·
                {{ $report->created_at->translatedFormat('d F Y, H:i') }}</span>
            <h1>{{ $report->title }}</h1>
            <p>Dilaporkan oleh {{ $report->user->name }}</p>
        </div><x-status :status="$report->status" />
    </div>
    <div class="detail-grid">
        <div>
            <section class="panel detail-photo"><img src="{{ route('reports.photo', $report) }}" alt="{{ $report->title }}">
            </section>
            <section class="panel form-section">
                <h2>Informasi laporan</h2>
                <div class="detail-meta"><span class="category"><span
                            class="category-dot {{ $report->category->value }}"></span>{{ $report->category->label() }}</span>
                    @if ($report->ai_category)
                        <span class="muted">Saran AI: {{ \App\Enums\WasteCategory::from($report->ai_category)->label() }} ·
                            {{ round($report->ai_confidence * 100, 1) }}%</span>
                    @endif
                </div>
                <p class="description">{{ $report->description }}</p>
                <div class="location-block"><x-icon name="pin" />
                    <div><strong>{{ $report->address }}</strong>
                        <p>{{ $report->latitude }}, {{ $report->longitude }}</p><a class="text-link"
                            href="https://www.openstreetmap.org/?mlat={{ $report->latitude }}&mlon={{ $report->longitude }}#map=17/{{ $report->latitude }}/{{ $report->longitude }}"
                            target="_blank" rel="noopener noreferrer">Buka lokasi di peta <x-icon name="arrow" /></a>
                    </div>
                </div>
            </section>
        </div>
        <aside>
            @if (auth()->user()->isAdmin())
                <section class="panel form-section">
                    <h2>Perbarui status</h2>
                    <p class="muted">Perubahan dapat dilihat oleh pelapor.</p>
                    <form class="stack" method="POST" action="{{ route('reports.status', $report) }}">@csrf
                        @method('PATCH')<label>Status<select name="status" data-status-select>
                                @foreach (\App\Enums\ReportStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $report->status->value) === $status->value)>
                                        {{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </label><label>Catatan petugas
                            <textarea name="note" rows="3" maxlength="2000" placeholder="Wajib diisi jika laporan ditolak"
                                data-status-note>{{ old('note') }}</textarea>
                        </label><button class="button primary full">Simpan status</button></form>
                </section>
            @endif
            <section class="panel form-section">
                <h2>Riwayat penanganan</h2>
                <div class="timeline">
                    @foreach ($report->histories as $history)
                        <div class="timeline-item"><span
                                class="timeline-dot"></span><strong>{{ $history->status->label() }}</strong><small>{{ $history->created_at->translatedFormat('d M Y, H:i') }}
                                · {{ $history->user?->name ?? 'Pengguna' }}</small>
                            @if ($history->note)
                                <p>{{ $history->note }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
            @can('update', $report)
                <section class="panel form-section">
                    <h3>Kelola laporan</h3>
                    <p class="muted">
                        {{ auth()->user()->isAdmin() ? 'Perbaiki informasi atau hapus laporan ini.' : 'Anda dapat mengubah laporan selama masih menunggu.' }}
                    </p>
                    <div class="action-row"><a class="button secondary" href="{{ route('reports.edit', $report) }}"><x-icon
                                name="edit" />Edit</a>
                        <form method="POST" action="{{ route('reports.destroy', $report) }}"
                            data-confirm="Hapus laporan ini beserta foto dan riwayatnya? Tindakan ini tidak dapat dibatalkan.">
                            @csrf @method('DELETE')<button class="button danger"><x-icon name="trash" />Hapus</button></form>
                    </div>
                </section>
            @endcan
        </aside>
    </div>
@endsection
