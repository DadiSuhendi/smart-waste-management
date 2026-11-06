# Dokumentasi API Bersih

Base URL lokal: `http://127.0.0.1:8123/api/v1`. Kontrak machine-readable: [openapi.json](openapi.json). Import file tersebut ke Postman atau Swagger Editor. API Python memiliki Swagger otomatis di `http://127.0.0.1:8001/docs`.

## Autentikasi

Kirim `Accept: application/json`. Setelah login/register, gunakan `Authorization: Bearer <token>`. Token Sanctum berlaku 30 hari. Logout hanya mencabut token perangkat yang sedang digunakan. Registrasi selalu menghasilkan role **user**, walau client mencoba mengirim role lain.

```http
POST /api/v1/login
Content-Type: application/json
Accept: application/json

{"email":"user@bersih.test","password":"Bersih123!","device_name":"Android"}
```

Response 200:

```json
{
  "user": {"id": 2, "name": "Nadia Putri", "email": "user@bersih.test", "role": "user"},
  "token": "<token-dari-server>",
  "token_type": "Bearer"
}
```

Objek user juga menyertakan timestamp; password tidak diserialisasi. `POST /register` memerlukan `name`, `email`, `password`, `password_confirmation`, serta `device_name` opsional. Password minimal 8 karakter dan memuat huruf serta angka. Response register: 201 dengan struktur yang sama.

## Endpoint

| Method | Path (setelah /api/v1) | Akses / fungsi |
|---|---|---|
| POST | /register | Publik; registrasi user |
| POST | /login | Publik; login dan token |
| GET | /me | Akun aktif, envelope data |
| POST | /logout | Cabut token aktif |
| GET | /dashboard | Statistik seluruh waktu + tren/kategori periode |
| GET | /reports | Daftar laporan yang dapat diakses |
| POST | /reports | Buat laporan dengan foto |
| GET | /reports/{id} | Detail dan riwayat status |
| PUT/PATCH | /reports/{id} | Edit seluruh field wajib; foto opsional |
| DELETE | /reports/{id} | Hapus laporan, file foto, dan riwayat |
| GET | /reports/{id}/photo | File foto privat; wajib autentikasi |
| PATCH | /reports/{id}/status | Admin; ubah status dengan catatan |
| POST | /predictions | Proxy klasifikasi foto |
| GET | /users | Admin; cari/list pengguna |
| POST | /users | Admin; buat akun |
| PUT/PATCH | /users/{id} | Admin; edit akun/role/password |
| DELETE | /users/{id} | Admin; hapus akun tanpa laporan |

User hanya dapat melihat laporan sendiri. Admin dapat melihat semua. User hanya dapat mengedit/menghapus laporan miliknya yang masih pending. Akses laporan/foto milik orang lain menghasilkan 403.

## Membuat dan mengedit laporan

`multipart/form-data`:

| Field | Tipe | Validasi |
|---|---|---|
| title | string | Wajib, maksimal 150 |
| description | string | Wajib, 10–5000 karakter |
| category | enum | organic / plastic / paper |
| address | string | Wajib, maksimal 255 |
| latitude | number | -90 sampai 90 |
| longitude | number | -180 sampai 180 |
| photo | file | JPEG/PNG/WebP, maksimal 5120 KB, sisi maksimal 8000 px |

Foto wajib saat membuat; opsional saat edit. Kategori akhir adalah pilihan pengguna. Status awal selalu `pending`. Backend mengabaikan field client `user_id`, `status`, `ai_category`, dan `ai_confidence` untuk pembuatan laporan. Prediksi yang disimpan diperoleh langsung dari FastAPI.

Contoh shell (pada PowerShell gunakan `curl.exe`, bukan alias `curl`):

```sh
curl -X POST http://127.0.0.1:8123/api/v1/reports \
  -H "Authorization: Bearer <token>" -H "Accept: application/json" \
  -F "title=Sampah di taman" -F "description=Botol plastik menumpuk di dekat bangku." \
  -F "category=plastic" -F "address=Taman Menteng" \
  -F "latitude=-6.2" -F "longitude=106.8" -F "photo=@waste.jpg"
```

Response 201 menggunakan envelope `{"data":{...}}`. Objek laporan berisi `id`, `reference`, field laporan, `status`, label kategori/status, `photo_url`, `ai_category`, `ai_confidence`, timestamp ISO 8601, serta identitas pelapor. Detail GET menyertakan array `histories` terbaru dahulu: `id,status,status_label,note,actor,created_at`.

Untuk mengganti foto secara kompatibel dengan PHP, gunakan **POST** ke `/reports/{id}` dan tambahkan multipart field `_method=PUT`. PUT JSON cocok jika foto dipertahankan. Semua field teks wajib tetap disertakan; PATCH tidak mengimplementasikan partial field update.

`photo_url` membutuhkan header Bearer yang sama. Jangan menaruh token pada query string URL.

## Pencarian dan pagination

`GET /reports?q=taman&category=plastic&status=pending&page=1`

- `q`: judul/alamat, maksimal 150 karakter.
- `status`: `pending`, `in_progress`, `completed`, `rejected`.
- `category`: `organic`, `plastic`, `paper`.
- 10 item/halaman, urut ID terbaru; filter bisa digabungkan.
- Response: `data`, `links`, `meta`; metadata berisi `current_page,last_page,total,per_page,from,to`.

`GET /users?q=nadia&page=1` menggunakan paginator langsung: `data,current_page,last_page,total,per_page,next_page_url,prev_page_url`. Tiap user menyertakan `reports_count`.

## Dashboard

`GET /dashboard?days=30`, dengan days = 7 / 30 / 90.

`data.stats`: `total,pending,in_progress,completed,rejected,completion_rate` untuk seluruh waktu. `data.trend` berisi tanggal dan jumlah harian termasuk nol; `data.categories` berisi jumlah per kategori dalam periode. Semua query dibatasi berdasarkan role/pemilik.

## Status dan pengguna

```http
PATCH /api/v1/reports/12/status
Authorization: Bearer <admin-token>
Content-Type: application/json

{"status":"rejected","note":"Laporan duplikat dari lokasi yang sudah ditangani."}
```

Admin dapat memilih keempat status, termasuk membuka kembali laporan. Status yang sama tidak menambah riwayat. Catatan wajib untuk rejected, opsional untuk status lain, maksimal 2000 karakter. Perubahan dan audit actor disimpan dalam transaksi.

Manajemen pengguna membutuhkan `name,email,role`. Pembuatan akun juga membutuhkan `password,password_confirmation`. Password opsional untuk update; jika berubah, seluruh token API user tersebut dicabut. Admin tidak dapat menurunkan role sendiri atau menghapus akun sendiri. Akun yang sudah memiliki laporan tidak boleh dihapus (422).

## Deteksi AI

`POST /predictions` dengan field multipart `photo`. Response:

```json
{
  "data": {
    "category": "plastic",
    "label": "Plastic Waste",
    "confidence": 0.93,
    "probabilities": {"organic": 0.02, "paper": 0.05, "plastic": 0.93},
    "needs_review": false,
    "model_version": "<hash-manifest>"
  }
}
```

Angka di atas **contoh format**, bukan hasil evaluasi. `confidence` adalah probabilitas softmax, bukan jaminan kalibrasi. `needs_review=true` saat confidence < 0,70. Model hanya mengenal tiga kelas, tanpa bounding box atau kelas unknown.

Jika Python gagal dihubungi, timeout, model belum ada, atau response tidak valid: proxy menghasilkan **503**. Pembuatan laporan tetap bisa berjalan manual, dengan field AI null.

### API Python langsung

- `GET /health`: `status=ready` atau `model_missing`.
- `POST /predict`: field multipart **file** (bukan photo); response prediksi tanpa envelope data.
- `X-API-Key` wajib jika environment `ML_API_KEY` diatur.
- 401 key tidak valid; 413 > 5 MB; 422 format/isi gambar tidak valid atau field tidak ada; 503 model belum tersedia.
- Format JPEG, PNG, WebP, maksimal 20 megapiksel. Perlindungan decompression bomb Pillow aktif.
- Python hanya perlu terbuka di loopback/jaringan privat; autentikasi end-user dan rate limiting ditangani Laravel.

## Error dan batas request

| Status | Arti |
|---|---|
| 200 / 201 | Berhasil / resource dibuat |
| 401 | Token tidak ada, invalid, atau kedaluwarsa |
| 403 | Role atau kepemilikan tidak diizinkan |
| 404 | Resource tidak ditemukan |
| 422 | Validasi gagal; lihat objek errors |
| 429 | Terlalu banyak request |
| 503 | Layanan AI tidak tersedia |

```json
{"message":"The given data was invalid.","errors":{"photo":["Foto wajib diisi."]}}
```

Pesan validasi bawaan dapat berbahasa Inggris; client menggunakan field `errors`, bukan mencocokkan teks. Website menggunakan CSRF dan cookie session, sedangkan API menggunakan Bearer token.

Batas: login/register 10 request/menit/IP, API terautentikasi 120/menit/user, prediksi 12/menit/user. Batas upload web server/PHP perlu dikonfigurasi terpisah.
