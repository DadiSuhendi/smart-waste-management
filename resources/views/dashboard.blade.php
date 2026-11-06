@extends('layouts.app')
@section('title', 'Ringkasan')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">{{ now()->translatedFormat('l, d F Y') }}</span>
            <h1>Halo, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
            <p>{{ auth()->user()->isAdmin() ? 'Berikut perkembangan laporan di lingkungan Anda.' : 'Pantau laporan Anda dan proses penanganannya di sini.' }}
            </p>
        </div><a class="button primary" href="{{ route('reports.create') }}"><x-icon name="plus" />Buat laporan</a>
    </div>
    <div class="overview-grid">
        <section class="progress-card" aria-label="Progres penanganan seluruh laporan">
            <div class="progress-card-top"><span>Laporan selesai ditangani</span><x-icon name="leaf" /></div>
            <div class="progress-value"><strong>{{ number_format($stats['completed']) }}</strong><span>dari
                    {{ number_format($stats['total']) }} laporan</span></div>
            <div class="progress-track" role="progressbar" aria-label="Persentase laporan selesai"
                aria-valuenow="{{ $stats['completion_rate'] }}" aria-valuemin="0" aria-valuemax="100"><span
                    style="width: {{ $stats['completion_rate'] }}%"></span></div>
            <div class="progress-card-foot"><span>{{ $stats['completion_rate'] }}% terselesaikan</span><a
                    href="{{ route('reports.index', ['status' => 'completed']) }}">Lihat laporan selesai</a></div>
        </section>
        @foreach ([['pending', 'Menunggu ditinjau', 'clock', 'Lihat antrean laporan'], ['in_progress', 'Dalam penanganan', 'truck', 'Pantau progresnya']] as [$key, $label, $icon, $hint])
            <a class="stat-card" href="{{ route('reports.index', ['status' => $key]) }}">
                <div class="stat-top"><span>{{ $label }}</span><span class="stat-icon"><x-icon
                            :name="$icon" /></span></div><strong
                    class="stat-number">{{ number_format($stats[$key]) }}</strong><span
                    class="stat-hint">{{ $hint }}<x-icon name="arrow" /></span>
            </a>
        @endforeach
    </div>
    <div class="section-heading">
        <h2>Aktivitas laporan</h2>
        <form method="GET"><label class="sr-only" for="days">Periode grafik</label><select id="days"
                name="days" data-autosubmit>
                @foreach ([7, 30, 90] as $option)
                    <option value="{{ $option }}" @selected($days === $option)>{{ $option }} hari terakhir
                    </option>
                @endforeach
            </select>
            <noscript><button class="button secondary">Terapkan</button></noscript>
        </form>
    </div>
    <div class="analytics-grid">
        <section class="panel trend-panel">
            <div class="panel-heading">
                <div>
                    <h3>Tren pelaporan</h3>
                    <p>Jumlah laporan yang masuk setiap hari</p>
                </div><span class="chart-legend"><span class="tiny-dot"></span>Laporan masuk</span>
            </div>
            @php
                $maximum = max(4, $trend->max('total'));
                $points = $trend
                    ->map(
                        fn($day, $i) => round(44 + ($i * 640) / max(1, $trend->count() - 1), 2) .
                            ',' .
                            round(174 - ($day['total'] / $maximum) * 144, 2),
                    )
                    ->implode(' ');
            @endphp
            <div class="trend-chart"><svg viewBox="0 0 720 220" role="img"
                    aria-label="Grafik laporan {{ $days }} hari terakhir, total {{ $trend->sum('total') }} laporan">
                    @foreach ([0, 1, 2, 3, 4] as $tick)
                        <line x1="44" x2="684" y1="{{ 174 - $tick * 36 }}" y2="{{ 174 - $tick * 36 }}"
                            stroke="#e3e6d7" stroke-dasharray="3 4" /><text x="27" y="{{ 178 - $tick * 36 }}"
                            text-anchor="end" class="chart-text">{{ round(($maximum * $tick) / 4) }}</text>
                    @endforeach
                    <polygon points="44,174 {{ $points }} 684,174" fill="#edf2df" />
                    <polyline points="{{ $points }}" fill="none" stroke="#497d40" stroke-width="2.5"
                        stroke-linejoin="round" />
                    @foreach ($trend as $i => $day)
                        <circle cx="{{ 44 + ($i * 640) / max(1, $trend->count() - 1) }}"
                            cy="{{ 174 - ($day['total'] / $maximum) * 144 }}" r="3" fill="#497d40">
                            <title>{{ $day['label'] }}: {{ $day['total'] }} laporan</title>
                        </circle>
                    @endforeach
                    @foreach ([0, (int) (($trend->count() - 1) / 3), (int) ((2 * ($trend->count() - 1)) / 3), $trend->count() - 1] as $i)
                        <text x="{{ 44 + ($i * 640) / max(1, $trend->count() - 1) }}" y="207" text-anchor="middle"
                            class="chart-text">{{ $trend[$i]['label'] }}</text>
                    @endforeach
                </svg></div>
            <div class="chart-foot"><strong>{{ $trend->sum('total') }}</strong> laporan dalam {{ $days }} hari
                terakhir <span>Data diperbarui saat halaman dimuat</span></div>
        </section>
        <section class="panel category-panel">
            <div class="panel-heading">
                <div>
                    <h3>Komposisi sampah</h3>
                    <p>{{ $days }} hari terakhir</p>
                </div>
            </div>
            @php
                $total = $categories->sum('total');
            @endphp
            <div class="category-total"><strong>{{ $total }}</strong><span>laporan masuk</span></div>
            <div class="category-list">
                @foreach ($categories as $category)
                    <div class="category-row {{ $category['key'] }}">
                        <span class="category-symbol {{ $category['key'] }}"><x-icon :name="['organic' => 'leaf', 'plastic' => 'bottle', 'paper' => 'report'][$category['key']]" /></span>
                        <div><strong>{{ $category['label'] }}</strong><span class="category-meter" aria-hidden="true"><span
                                    style="width: {{ $total ? round(($category['total'] / $total) * 100) : 0 }}%"></span></span>
                        </div>
                        <div>
                            <strong>{{ $category['total'] }}</strong><small>{{ $total ? round(($category['total'] / $total) * 100) : 0 }}%</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
    <section class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Laporan terbaru</h2>
                <p>{{ number_format($stats['total']) }} laporan tercatat · {{ $stats['rejected'] }} ditolak</p>
            </div><a class="text-link" href="{{ route('reports.index') }}">Lihat semua <x-icon name="arrow" /></a>
        </div><x-report-table :reports="$recent" />
    </section>
    <div class="bottom-grid">
        <section class="impact-panel"><span class="impact-icon"><x-icon name="leaf" /></span>
            <div>
                <h3>Temukan. Foto. Laporkan.</h3>
                <p>Sertakan foto yang jelas, titik lokasi, dan deskripsi kondisi sampah agar laporan mudah ditindaklanjuti.
                </p>
            </div><a href="{{ route('guide') }}" class="text-link">Panduan membuat laporan <x-icon name="arrow" /></a>
        </section>
        <section class="panel activity-panel">
            <h3>Aktivitas terbaru</h3>
            @forelse($activity as $item)
                <a class="activity-item" href="{{ route('reports.show', $item->waste_report_id) }}"><span
                        class="activity-dot"></span>
                    <div><strong>SW-{{ str_pad($item->waste_report_id, 4, '0', STR_PAD_LEFT) }} ·
                            {{ $item->status->label() }}</strong><small>{{ $item->user?->name ?? 'Pengguna' }} ·
                            {{ $item->created_at->diffForHumans() }}</small></div><x-icon name="chevron" />
            </a>@empty<p class="muted">Aktivitas akan tampil setelah laporan pertama dibuat.</p>
            @endforelse
        </section>
    </div>
@endsection
