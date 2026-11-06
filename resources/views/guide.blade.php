@extends('layouts.app')
@section('title', 'Panduan')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">PUSAT BANTUAN</span>
            <h1>Langkah kecil, lingkungan lebih baik</h1>
            <p>Semua yang perlu Anda ketahui untuk menggunakan Bersih.</p>
        </div>
    </div>
    <div class="guide-grid">
        @foreach ([['01', 'Buat laporan', 'Foto sampah dengan jelas, tuliskan kondisi dan alamat, lalu gunakan GPS atau masukkan koordinat lokasi.'], ['02', 'Periksa kategori', 'Gunakan deteksi foto untuk saran organik, plastik, atau kertas. Hasil AI merupakan bantuan; sesuaikan dengan kondisi sebenarnya.'], ['03', 'Pantau penanganan', 'Lihat status dan catatan petugas di halaman detail. Laporan milik Anda dapat diedit atau dihapus selama masih menunggu.']] as [$num, $title, $body])
            <section class="panel form-section"><span class="guide-number">{{ $num }}</span>
                <h2>{{ $title }}</h2>
                <p class="muted">{{ $body }}</p>
            </section>
        @endforeach
    </div>
    <section class="panel form-section narrow">
        <h2>Arti status laporan</h2>
        <dl class="status-guide">
            @foreach ([['pending', 'Laporan diterima dan akan ditinjau admin.'], ['in_progress', 'Petugas sedang menangani laporan.'], ['completed', 'Penanganan telah selesai.'], ['rejected', 'Laporan tidak dapat diproses. Baca alasan pada riwayat penanganan.']] as [$status, $description])
                <div>
                    <dt><x-status :status="\App\Enums\ReportStatus::from($status)" /></dt>
                    <dd>{{ $description }}</dd>
                </div>
            @endforeach
        </dl><a class="button primary" href="{{ route('reports.create') }}">Buat laporan pertama <x-icon name="arrow" /></a>
    </section>
@endsection
