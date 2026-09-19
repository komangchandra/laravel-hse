# HSE GPU

Aplikasi Laravel 12 untuk pengajuan Mine Permit dan Mine Permit + SIMPER, mulai dari data tenaga kerja, review HSE, ujian, persetujuan KTT berbukti QR, hingga penerbitan kartu ber-barcode.

## Kebutuhan

- PHP 8.2 atau lebih baru
- Composer
- SQLite untuk setup lokal ringan
- MySQL 8.x untuk pengujian kompatibilitas dan lingkungan produksi

Frontend saat ini menggunakan Bootstrap, Bootstrap Icons, CSS, font, dan JavaScript statis dari folder `public`. Setup aplikasi tidak memerlukan Node.js, npm, atau proses build Vite.

## Setup lokal

Jalankan setup otomatis:

```bash
composer run setup
```

Perintah tersebut memasang dependency PHP, membuat `.env` dan database SQLite bila belum ada, membuat application key, lalu menjalankan migration dan seeder.

Jika memakai MySQL/MariaDB, buat database dan ubah konfigurasi `DB_*` di `.env` sebelum menjalankan migration:

```bash
php artisan migrate --seed
```

Pastikan `APP_KEY`, kredensial database, kredensial mail, dan rahasia lain berasal dari environment atau secret store. Jangan menyimpan `.env` produksi di repository.

Seeder hanya membuat data master dan role. Seeder tidak membuat akun dengan password default.

Registrasi publik dinonaktifkan. Akun operasional dibuat oleh developer agar role dan organisasi selalu divalidasi.

Role bisnis yang disediakan adalah `developer`, `ktt`, `hse_owner`, dan `safety_mitra`. Seeder memigrasikan akun dengan role lama ke role target dan menghapus role generik lama.

## Role dan alur kerja

| Role | Tanggung jawab utama |
| --- | --- |
| Developer | Mengelola akun, organisasi, dan konfigurasi global |
| Safety Mitra | Mengelola tenaga kerja mitranya dan mengajukan permit |
| HSE Owner | Memverifikasi dokumen/pengajuan, menyiapkan ujian, dan menilai kelayakan |
| KTT | Memberi keputusan akhir; bukti persetujuan berupa QR terenkripsi, tanpa tanda tangan |

Alur Mine Permit: draft -> review HSE -> review KTT -> kartu terbit. Alur gabungan menambahkan sesi ujian dan hasil lulus sebelum review KTT. Penolakan mengembalikan pengajuan untuk diperbaiki tanpa menghapus histori. Passing grade ujian adalah 80%.

## Membuat akun developer pertama

Setelah seeder selesai, jalankan command berikut secara eksplisit:

```bash
php artisan app:bootstrap-developer developer@example.com --name="Nama Developer" --generate-password
```

Developer bersifat global dan tidak terikat organisasi. Opsi `--generate-password` membuat password secara otomatis dan menampilkannya satu kali tanpa membuka prompt tambahan. Simpan password tersebut, login, lalu ganti melalui halaman profil bila diperlukan. Menjalankan command dengan email yang sama akan memperbarui akun tersebut.

## Mengelola akun organisasi

Developer dapat membuka menu **Manajemen Akun** untuk membuat akun yang langsung dapat digunakan tanpa tautan aktivasi. Isi nama, email, role, organisasi, status aktif, dan password awal yang kuat. KTT/HSE Owner hanya dapat ditempatkan pada organisasi owner, Safety Mitra hanya pada organisasi mitra, sedangkan Developer tidak memakai organisasi.

Menonaktifkan akun langsung mencabut seluruh sesi login pengguna. Perubahan role, organisasi, aktivasi, dan deaktivasi dicatat dalam audit log. Akun organisasi tidak dapat menghapus dirinya sendiri; penghapusan dilakukan developer melalui Manajemen Akun agar role KTT/HSE terakhir tetap terlindungi.

## Data tenaga kerja dan dokumen

Safety Mitra mengelola profil tenaga kerja hanya untuk perusahaannya sendiri. Profil menyimpan NIK, identitas, jabatan, departemen, kontak, kontak darurat, foto formal rasio 3:4, dan status aktif. NIK unik dalam lingkup owner.

Dokumen tenaga kerja disimpan sebagai file privat dan record berversi. Format yang diterima hanya PDF maksimal 2 MB. Kebutuhan dokumen mengikuti `codex/DOKUMEN_PENGAJUAN.md`; tanggal kedaluwarsa dokumen tidak diminta. Versi lama dan data tenaga kerja yang diarsipkan tetap disimpan untuk kebutuhan histori. HSE Owner dapat memverifikasi atau menolak dokumen milik mitra di bawah owner-nya.

Pemeriksaan antivirus bersifat opsional. Isi `CLAMAV_BINARY` dengan lokasi executable `clamscan` jika ClamAV tersedia pada server. Jika tidak diisi, validasi MIME, ekstensi, ukuran, dan nama file tetap dijalankan.

## Menjalankan aplikasi

```bash
composer run dev
```

Atau:

```bash
php artisan serve
```

Untuk pekerjaan queue, jalankan terminal terpisah:

```bash
php artisan queue:work --tries=3 --backoff=5 --timeout=90
```

Pada production gunakan process manager (misalnya Supervisor atau systemd), bukan terminal interaktif. Setelah deployment jalankan `php artisan queue:restart` agar worker memuat kode terbaru. Saat ini notifikasi workflow disimpan langsung ke database; worker tetap disiapkan dan dipantau untuk pekerjaan asynchronous yang ditambahkan kemudian.

Scheduler harus berjalan setiap menit. Contoh cron Linux:

```cron
* * * * * cd /path/to/laravel-hse-gpu && php artisan schedule:run >> /dev/null 2>&1
```

Daftar jadwal dapat diperiksa dengan `php artisan schedule:list`. Command `permits:expire` berjalan setiap hari pukul 00:10 untuk memperbarui permit kedaluwarsa dan membuat notifikasi masa berlaku.

## Storage dan file privat

Jalankan `php artisan storage:link` untuk aset publik seperti logo. Dokumen tenaga kerja tetap berada pada disk privat dan hanya boleh diakses melalui endpoint yang menjalankan Policy; jangan memindahkannya ke `public/storage`.

## Pengujian

```bash
composer test
```

Konfigurasi PHPUnit menggunakan SQLite dalam memori sehingga tidak mengubah database lokal.

Sebelum rilis, suite yang sama wajib dijalankan pada database MySQL khusus pengujian. Jangan menunjuk perintah ini ke database development atau production:

```powershell
$env:DB_CONNECTION = "mysql"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_DATABASE = "laravel_hse_testing"
$env:DB_USERNAME = "root"
$env:DB_PASSWORD = ""
php artisan test
```

CI juga menjalankan suite pada MySQL melalui `.github/workflows/tests.yml`.

## Backup dan restore

Buat backup konsisten sebelum migration production. Contoh MySQL:

```bash
mysqldump --single-transaction --routines --triggers --host=127.0.0.1 --user=APP_USER --password APP_DATABASE > backup-before-release.sql
```

Uji restore terlebih dahulu ke database kosong yang terpisah, lalu bandingkan jumlah tabel dan migration. Jangan menguji restore langsung pada database aktif:

```bash
mysql --host=127.0.0.1 --user=APP_USER --password RESTORE_TEST_DATABASE < backup-before-release.sql
```

## Deployment

1. Ambil backup dan catat checksum, ukuran, serta lokasi penyimpanannya.
2. Aktifkan maintenance mode: `php artisan down --retry=60`.
3. Deploy kode dan dependency dengan `composer install --no-dev --optimize-autoloader`.
4. Jalankan `php artisan migrate --force`, `php artisan storage:link`, lalu `php artisan optimize`.
5. Jalankan smoke test login, dashboard, file privat, alur permit, QR validasi, dan PDF kartu.
6. Jalankan `php artisan queue:restart`, verifikasi worker, scheduler, log, storage, mail, dan notifikasi.
7. Buka aplikasi dengan `php artisan up` setelah verifikasi berhasil.

Checklist lengkap cutover, rollback, UAT, serta status kesiapan ada di folder `codex/release`. Rollback database hanya dilakukan berdasarkan migration yang telah ditinjau dan backup yang sudah terbukti dapat dipulihkan.

## Dokumentasi proyek

- Analisis aplikasi: `codex/HASIL_PEMBELAJARAN_PROJECT.md`
- Proses bisnis target: `codex/BUSSINES-PROSES.md`
- Backlog implementasi: `codex/tasks/README.md`
- Checklist UAT: `codex/release/UAT-CHECKLIST.md`
- Checklist cutover/rollback: `codex/release/CUTOVER-ROLLBACK-CHECKLIST.md`
- Laporan kesiapan rilis: `codex/release/TASK-016-RELEASE-READINESS.md`
