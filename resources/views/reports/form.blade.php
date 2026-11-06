@extends('layouts.app')
@section('title', $report->exists ? 'Edit laporan' : 'Buat laporan')
@section('content')
    <a href="{{ $report->exists ? route('reports.show', $report) : route('reports.index') }}" class="back-link">
        <x-icon name="back" />Kembali ke laporan</a>
    <div class="page-heading">
        <div>
            <h1>{{ $report->exists ? 'Edit laporan' : 'Buat laporan baru' }}</h1>
            <p>Berikan informasi yang jelas agar sampah dapat segera ditangani.</p>
        </div><span
            class="muted">{{ $report->exists ? 'Foto boleh tetap menggunakan foto sebelumnya' : 'Semua kolom wajib diisi' }}</span>
    </div>
    <form action="{{ $report->exists ? route('reports.update', $report) : route('reports.store') }}" method="POST"
        enctype="multipart/form-data" class="report-form" data-report-form>@csrf @if ($report->exists)
            @method('PUT')
        @endif
        <div class="form-main">
            <section class="panel form-section">
                <div class="form-section-title"><span>01</span>
                    <div>
                        <h2>Detail laporan</h2>
                        <p>Ceritakan apa yang Anda temukan.</p>
                    </div>
                </div>
                <div class="stack">
                    <label>Judul laporan<input name="title" value="{{ old('title', $report->title) }}" required
                            maxlength="150" placeholder="Contoh: Tumpukan plastik di tepi jalan"></label>
                    <label>Deskripsi
                        <textarea name="description" rows="5" required minlength="10" maxlength="5000"
                            placeholder="Jelaskan kondisi sampah, jumlah, dan informasi lain yang membantu petugas.">{{ old('description', $report->description) }}</textarea>
                    </label>
                    <label>Kategori sampah<select name="category" id="report-category" required>
                            <option value="">Pilih kategori</option>
                            @foreach (\App\Enums\WasteCategory::cases() as $category)
                                <option value="{{ $category->value }}" @selected(old('category', $report->category?->value) === $category->value)>{{ $category->label() }}
                                </option>
                            @endforeach
                        </select><small>Deteksi foto dapat membantu memilih. Anda tetap bisa mengubahnya.</small></label>
                </div>
            </section>
            <section class="panel form-section">
                <div class="form-section-title"><span>02</span>
                    <div>
                        <h2>Lokasi sampah</h2>
                        <p>Tentukan titik lokasi agar mudah ditemukan.</p>
                    </div>
                </div>
                <div class="stack"><label>Alamat atau patokan<input name="address"
                            value="{{ old('address', $report->address) }}" required maxlength="255"
                            placeholder="Nama jalan, RT/RW, atau patokan terdekat"></label><button type="button"
                        class="button secondary gps-button" data-gps><x-icon name="pin" />Gunakan lokasi saya</button>
                    <p class="field-note" data-gps-message aria-live="polite">Izinkan akses lokasi, atau isi koordinat
                        secara manual.</p>
                    <div class="field-grid"><label>Latitude<input type="number" step="any" min="-90"
                                max="90" name="latitude" value="{{ old('latitude', $report->latitude) }}" required
                                placeholder="-6.200000"></label><label>Longitude<input type="number" step="any"
                                min="-180" max="180" name="longitude"
                                value="{{ old('longitude', $report->longitude) }}" required
                                placeholder="106.816666"></label>
                    </div>
                </div>
            </section>
        </div>
        <aside class="form-aside">
            <section class="panel form-section photo-section">
                <div class="form-section-title"><span>03</span>
                    <div>
                        <h2>Foto sampah</h2>
                        <p>Tunjukkan kondisi di lokasi.</p>
                    </div>
                </div>
                <label class="upload-zone" for="photo"><x-icon name="scan" /><strong>Pilih foto
                        sampah</strong><span>JPG, PNG, atau WebP · Maks. 5 MB</span><input id="photo" name="photo"
                        type="file" accept="image/jpeg,image/png,image/webp" @required(!$report->exists)></label>
                <img id="photo-preview" class="photo-preview"
                    @if ($report->exists) src="{{ route('reports.photo', $report) }}" @else hidden @endif
                    alt="Pratinjau foto laporan">
                <button type="button" class="button secondary full detect-button" data-detect
                    data-url="{{ route('predictions') }}" disabled><x-icon name="scan" />Deteksi kategori foto</button>
                <p class="field-note" data-detect-message aria-live="polite">Unggah foto untuk mendapatkan saran kategori.
                </p>
            </section>
            <div class="form-tip"><x-icon name="help" />
                <div><strong>Foto yang baik membantu</strong>
                    <p>Pastikan sampah terlihat jelas dan pencahayaan cukup. Hindari memotret wajah atau data pribadi.</p>
                </div>
            </div>
        </aside>
        <div class="form-actions"><a class="button secondary" href="{{ route('reports.index') }}">Batal</a><button
                type="submit" class="button primary">{{ $report->exists ? 'Simpan perubahan' : 'Kirim laporan' }}<x-icon
                    name="arrow" /></button></div>
    </form>
@endsection
