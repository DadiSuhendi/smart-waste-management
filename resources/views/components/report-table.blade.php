@props(['reports'])
<div class="table-scroll">
    <table class="report-table">
        <thead>
            <tr>
                <th>Laporan</th>
                <th>Kategori</th>
                <th>Status</th>
                @if (auth()->user()->isAdmin())
                    <th>Pelapor</th>
                @endif
                <th>
                    Tanggal</th>
                <th><span class="sr-only">Detail</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $report)
                <tr>
                    <td><a class="report-cell" href="{{ route('reports.show', $report) }}"><img
                                src="{{ route('reports.photo', $report) }}" alt="" class="report-thumb"
                                loading="lazy">
                            <div><span
                                    class="reference">{{ $report->reference() }}</span><strong>{{ $report->title }}</strong><span
                                    class="location-text">{{ $report->address }}</span></div>
                        </a></td>
                    <td data-label="Kategori"><span class="category"><span
                                class="category-dot {{ $report->category->value }}"></span>{{ $report->category->label() }}</span>
                    </td>
                    <td data-label="Status"><x-status :status="$report->status" /></td>
                    @if (auth()->user()->isAdmin())
                        <td data-label="Pelapor"><span class="person-cell"><span
                                    class="avatar small">{{ mb_substr($report->user->name, 0, 1) }}</span>{{ $report->user->name }}</span>
                        </td>
                    @endif
                    <td class="date-cell" data-label="Tanggal">{{ $report->created_at->translatedFormat('d M Y') }}</td>
                    <td class="report-detail-link"><a class="icon-button" href="{{ route('reports.show', $report) }}"
                            aria-label="Lihat {{ $report->title }}"><x-icon name="chevron" /></a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state"><span class="empty-icon"><x-icon name="report" /></span>
                            <h3>Belum ada laporan</h3>
                            <p>Coba ubah filter, atau buat laporan pertama Anda.</p><a class="button secondary"
                                href="{{ route('reports.create') }}"><x-icon name="plus" />Buat laporan</a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
