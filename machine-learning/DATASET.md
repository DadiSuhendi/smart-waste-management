# Dataset dan sumber

Dataset yang disertakan adalah **subset RealWaste** untuk tugas pendidikan klasifikasi tiga kategori. Ini adalah foto nyata, bukan gambar sintetis atau hasil generasi AI.

- Pemilik/sumber: Sam Single dan tim, **RealWaste**.
- Repository asli: https://github.com/sam-single/realwaste
- Paper asli: [RealWaste: A Novel Real-Life Data Set for Landfill Waste Classification Using Deep Learning](https://www.mdpi.com/2078-2489/14/12/633).
- Commit sumber yang dipatok: `156e8e9154eeb163df30fcfd767f55b19633bb06`.
- Lisensi yang dinyatakan repository sumber: [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/).
- Penggunaan gambar dibatasi ketentuan atribusi, nonkomersial, dan share-alike dari lisensi tersebut. Lisensi data tidak diubah oleh proyek ini.

Gambar RealWaste dikumpulkan dari fasilitas Whyte's Gully Waste and Resource Recovery di Wollongong, Australia. Lokasi foto sumber tidak sama dengan lokasi contoh yang ditampilkan seeder website.

## Pemetaan label

| Folder sumber | Label API | Nama kategori | Diunduh |
|---|---|---|---:|
| Food Organics | organic | Organic Waste | 400 |
| Paper | paper | Paper Waste | 400 |
| Plastic | plastic | Plastic Waste | 400 |
| **Total** | | | **1.200** |

Folder lokal: `data/organic`, `data/paper`, `data/plastic`. Label organik pada model ini berasal dari **Food Organics**, bukan seluruh bentuk sampah organik; daun/vegetasi bukan bagian training ini.

## Pemilihan dan pencegahan duplikasi

`download_dataset.py` mengurutkan daftar sumber, mengacak dengan seed 42, lalu memilih 400 gambar per kelas. Gambar disimpan tanpa perubahan byte; nama file mengganti spasi dengan underscore.

Sebelum split, gambar diperiksa dengan hash pixel RGB dan difference hash 64-bit. Gambar dengan pixel identik atau Hamming distance <= 5 terhadap representatif kelas yang dipertahankan ditandai `excluded_duplicate`. Gambar tersebut tetap disertakan untuk audit tetapi **tidak digunakan** pada training, validation, atau testing.

| Kategori | Train | Validation | Test | Dikecualikan |
|---|---:|---:|---:|---:|
| Organic | 275 | 59 | 59 | 7 |
| Paper | 277 | 59 | 60 | 4 |
| Plastic | 280 | 60 | 60 | 0 |
| **Total** | **832** | **178** | **179** | **11** |

Rasio kira-kira 70/15/15, per kelas setelah deduplikasi. Dataset efektif **1.189 gambar**, jauh di atas syarat minimal 150.

## Audit dan reproduksi

- [data/manifest.csv](data/manifest.csv): satu baris per gambar, path, kategori, URL sumber lengkap, SHA-256 file, hash pixel, dHash, dan split.
- [data/dataset_summary.json](data/dataset_summary.json): jumlah tiap kelas/split, seed, commit, dan lisensi.
- `python download_dataset.py --per-class 400` untuk mengunduh ulang.
- Downloader memverifikasi Git blob hash terhadap commit sumber sebelum memakai file lokal maupun hasil download. Script gagal secara eksplisit jika download/gambar tidak valid; tidak menghasilkan data palsu sebagai fallback.
- Test dataset memeriksa semua SHA-256, pemisahan hash, minimum jumlah gambar, dan kemiripan lintas split dalam satu kelas.

Tiga foto yang disalin ke `../public/demo/` berasal dari dataset yang sama dan tunduk pada lisensi yang sama. Foto tersebut hanya ilustrasi laporan demo.

## Keterbatasan

Split dibuat pada tingkat gambar, dengan deduplikasi visual heuristik. Dataset tidak menyediakan ID objek/sesi pengambilan yang dipakai untuk split, sehingga pemisahan objek fisik yang sama tidak dapat dijamin hanya melalui dHash. Background dan kondisi fasilitas relatif spesifik; pengujian lapangan Indonesia merupakan evaluasi tambahan yang belum dilakukan. Untuk deployment, kumpulkan data lapangan yang berizin dan buat test set terpisah berdasarkan lokasi/sesi pengambilan.
