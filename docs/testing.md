# Verifikasi dan panduan uji

## Verifikasi otomatis

- `php artisan test --compact`: **17 test, 92 assertions lulus** untuk login/register, autentikasi Bearer, akses role/pemilik, CRUD, foto privat, input GPS/gambar, admin status, alasan penolakan, pencarian/filter/statistik, fallback AI, manajemen pengguna, rendering Blade, dan command admin.
- `npm run build`: bundling Vite untuk CSS/JavaScript.
- `php artisan view:cache`: kompilasi semua Blade.
- `flutter analyze`: analisis static; tidak ada isu pada verifikasi akhir.
- `flutter test`: **4 test lulus** untuk header token/filter/pagination API, expired token callback, validation/non-JSON error, serta login/register UI.
- `flutter build apk --debug --dart-define=API_BASE_URL=http://10.0.2.2:8123/api/v1`: menghasilkan APK.
- `python -m pytest -q` dari machine-learning: **9 test** health/model nyata, probabilitas prediksi, file invalid/oversize, API key, model missing, input missing, hash/atribusi/split dataset dan deduplikasi.
- `node tests/integration.mjs`: **lolos** terhadap Laravel dan FastAPI yang berjalan nyata; menggunakan foto dataset, bukan HTTP mock.

Feature tests Laravel menggunakan SQLite in-memory dan HTTP fake untuk mengisolasi kasus error. Test integrasi terpisah memverifikasi koneksi nyata. Dataset audit membaca seluruh 1.200 file dan membandingkan hash dengan manifest.

## Uji integrasi nyata

Nyalakan layanan Laravel port 8123 dan FastAPI port 8001, seed database lokal, lalu jalankan dari root:

```sh
node tests/integration.mjs
```

Script login akun admin/user demo, membuat laporan sementara, memverifikasi inferensi Python, mengakses foto dengan Bearer token, mengganti status pending → in_progress → completed, memeriksa 3 riwayat dan statistik user, lalu membersihkan laporan serta token pengujian dalam blok finally.

Jika server berada di lokasi lain, set environment `API_BASE_URL`. Jangan menjalankan smoke test yang memakai akun demo terhadap production.

## Pemeriksaan manual sebelum demonstrasi

1. Buka website pada lebar desktop, tablet, dan 390 px. Pastikan menu mobile, form, tabel yang dapat digeser, dan pagination dapat digunakan.
2. Masuk admin/user pada sesi terpisah. Pastikan user tidak melihat laporan atau foto orang lain.
3. Buat laporan dari foto asli; periksa hasil deteksi dan ubah kategori manual.
4. Tolak izin GPS, matikan GPS, lalu uji koordinat manual. Uji izin berhasil pada perangkat yang berada di lokasi sampah.
5. Pilih foto >5 MB dan file bukan gambar; pastikan pesan error membantu dan input tidak terkirim.
6. Matikan Python; prediksi harus memberi pesan unavailable tetapi laporan manual tetap diterima.
7. Ubah status melalui admin; refresh mobile dan periksa catatan serta status.
8. Coba edit/hapus laporan milik user setelah diproses; server harus menolak.
9. Uji token expiry dan koneksi putus. Form tidak boleh menyatakan berhasil saat request gagal.
10. Uji pembaca layar/keyboard: label input, fokus tombol, skip link, dan menu.

## Batas verifikasi sesi ini

- Browser automation berhenti karena alat tidak dapat memastikan URL browser aktif. **Pemeriksaan visual langsung website belum selesai**, meskipun rendering server dan build berhasil.
- **Belum ada uji interaksi pada perangkat Android/iOS**; APK berhasil dibuild dan widget tests lolos, tetapi GPS/kamera/galeri native belum dibuktikan di perangkat.
- Build iOS memerlukan macOS/Xcode dan belum dijalankan di Windows.
- Database MySQL belum diuji; SQLite adalah database yang diverifikasi.
- Tidak ada deployment hosting publik yang dilakukan.
- Model diuji pada subset RealWaste; belum dievaluasi pada test set lapangan independen.

Versi FastAPI/Starlette saat pengujian mengeluarkan satu peringatan deprecation terkait httpx TestClient; seluruh test Python tetap lulus. Peringatan ini tidak memengaruhi respons inferensi.
