# Bersih Mobile — Flutter

Aplikasi Android/iOS untuk membuat laporan sampah dan memantau penanganan melalui API Laravel.

## Fitur

- Register/login dan validasi form.
- Token Sanctum di Flutter Secure Storage; pemulihan sesi saat aplikasi dibuka.
- Ringkasan total, menunggu, diproses, selesai; daftar laporan terbaru.
- Riwayat dengan pencarian, filter status, pagination, dan pull-to-refresh.
- Foto dari kamera/galeri, pemulihan hasil image picker setelah Android menghentikan activity.
- GPS dengan penanganan service/permission/timeout dan koordinat manual.
- Deteksi kategori melalui Laravel → FastAPI; kategori tetap dapat dikoreksi pengguna.
- Detail foto, kategori, lokasi, saran AI, serta riwayat petugas.
- Penanganan error jaringan/validasi/token kedaluwarsa dan keadaan kosong.

## Setup

Flutter yang diuji: **3.47.5**, Dart **3.13.4**. Android SDK 36.1 dan JDK 21 tersedia pada lingkungan build. Gunakan Flutter stable yang kompatibel dengan SDK constraint pada pubspec.

```sh
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8123/api/v1
```

Jalankan Laravel dan Python terlebih dahulu sesuai [README root](../README.md). Default API adalah alamat host Android emulator `http://10.0.2.2:8123/api/v1`.

| Target | API_BASE_URL |
|---|---|
| Android emulator | http://10.0.2.2:8123/api/v1 |
| iOS simulator | http://localhost:8123/api/v1 |
| Android USB dengan adb reverse | http://127.0.0.1:8123/api/v1 |
| Perangkat di jaringan yang sama | http://IP-LAN-KOMPUTER:8123/api/v1 (debug Android) |
| Production | https://domain-api-anda/api/v1 |

Untuk USB Android:

```sh
adb reverse tcp:8123 tcp:8123
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8123/api/v1
```

Untuk perangkat melalui Wi-Fi, jalankan Laravel dengan `php artisan serve --host=0.0.0.0 --port=8123` dan gunakan IP LAN komputer. Atur firewall sesuai jaringan pengujian Anda. Python tetap boleh di loopback karena Flutter mengaksesnya melalui Laravel.

## Build

```sh
flutter analyze
flutter test
flutter build apk --debug --dart-define=API_BASE_URL=http://10.0.2.2:8123/api/v1
```

APK: [build/app/outputs/flutter-apk/app-debug.apk](build/app/outputs/flutter-apk/app-debug.apk).

APK yang dibangun di workspace ini mengarah ke **Android emulator/host komputer**, sehingga perangkat fisik memerlukan rebuild dengan URL yang sesuai. Ini APK demo debug, bukan APK production bertanda tangan release.

Release:

```sh
flutter build apk --release --dart-define=API_BASE_URL=https://domain-api-anda/api/v1
```

Atur release signing sebelum distribusi. Template Android masih memakai konfigurasi debug signing pada build type release sebagai bawaan Flutter; jangan mendistribusikan build tersebut sebagai production. HTTP cleartext hanya diizinkan pada manifest **debug**. Release harus memakai HTTPS.

iOS:

```sh
flutter build ios --no-codesign --dart-define=API_BASE_URL=https://domain-api-anda/api/v1
```

Build iOS harus dilakukan pada macOS dengan Xcode. Konfigurasi izin kamera, galeri, lokasi, serta keychain entitlement sudah disertakan, tetapi build/perangkat iOS belum diverifikasi.

## Struktur

- `lib/core/api_client.dart`: HTTP JSON/multipart, timeout, error, Bearer header.
- `lib/core/session.dart`: secure token storage, login, restore, logout.
- `lib/core/models.dart`: model user, laporan, pagination.
- `lib/screens/`: auth, home/riwayat/akun, form laporan, detail.
- `lib/widgets/common.dart`: komponen bersama.
- `test/`: kontrak API, error/token expiry, serta interaksi form autentikasi.

## Catatan pengujian

Analyzer bersih dan APK debug berhasil dibangun. Unit/widget tests berjalan tanpa perangkat. Kamera, galeri, izin GPS, pemulihan activity, dan keychain/keystore harus diuji pada emulator/perangkat nyata; build yang berhasil tidak membuktikan alur hardware tersebut.

Klasifikasi mengenali satu kategori dominan: organik (food organics), plastik, atau kertas. Foto dengan objek lain atau banyak jenis sampah dapat menghasilkan saran yang keliru.
