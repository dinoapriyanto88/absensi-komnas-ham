# Absensi Digital Komnas HAM

Aplikasi absensi kegiatan berbasis web yang dikembangkan menggunakan Laravel untuk mendukung proses pencatatan kehadiran peserta dan pengelolaan data kegiatan secara digital.

## Fitur Utama

- **Absensi Digital:** peserta dapat memilih kegiatan dan mengisi kehadiran tanpa harus login.
- **Tanda Tangan Digital:** pencatatan tanda tangan peserta pada proses absensi.
- **Manajemen Pengguna:** pengelolaan akses berdasarkan peran pengguna.
- **Manajemen Kegiatan:** pengelolaan data kegiatan yang diselenggarakan.
- **Rekapitulasi Kehadiran:** pencatatan dan pengelolaan data kehadiran peserta.
- **Ekspor Laporan:** ekspor rekapitulasi dalam format PDF dan CSV.
- **Pengelolaan Akun:** fitur ganti password dan pemulihan password melalui email.
- **Database SQLite:** penyimpanan data menggunakan SQLite.

## Teknologi

- PHP
- Laravel
- SQLite
- Composer
- HTML, CSS, dan JavaScript

## Persyaratan Sistem

Sebelum menjalankan aplikasi, pastikan perangkat telah memiliki:

- PHP sesuai persyaratan pada `composer.json`
- Composer
- Git
- Ekstensi PHP yang diperlukan, termasuk `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `dom`, dan `xml`
- Ekstensi `gd` apabila diperlukan oleh fitur pemrosesan gambar dan PDF

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/dinoapriyanto88/absensi-komnas-ham.git
cd absensi-komnas-ham
```

### 2. Instal Dependensi

```bash
composer install
```

### 3. Siapkan File Environment

Salin `.env.example` menjadi `.env`.

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Jika `.env` sudah tersedia, jangan menimpanya. Sesuaikan konfigurasi yang diperlukan.

### 4. Siapkan Database

Aplikasi menggunakan SQLite dengan lokasi database bawaan:

`database/database.sqlite`

Repository menyediakan `database/database-demo.sqlite` untuk pengujian.

Jika database utama belum tersedia, salin database demo:

```powershell
Copy-Item database\database-demo.sqlite database\database.sqlite
```

**Penting:** jangan menjalankan perintah tersebut jika `database/database.sqlite` sudah berisi data yang ingin dipertahankan.

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Jalankan Aplikasi

```bash
php artisan serve
```

Buka aplikasi melalui browser:

`http://127.0.0.1:8000`

Halaman login:

`http://127.0.0.1:8000/login`

## Database Demo

Database demo disediakan untuk membantu proses pengujian aplikasi.

Untuk menggunakannya:

1. Pastikan database utama belum berisi data penting.
2. Salin `database/database-demo.sqlite` menjadi `database/database.sqlite`.
3. Jalankan aplikasi menggunakan `php artisan serve`.
4. Buka aplikasi dan uji fitur yang tersedia.

Kredensial akun pengujian harus diverifikasi dan diberikan secara terpisah. README ini tidak menyertakan password akun.

## Membuat Database Baru

Untuk memulai dengan database kosong:

1. Pastikan konfigurasi `.env` menggunakan SQLite.
2. Pastikan file database tersedia.
3. Jalankan migrasi:

```bash
php artisan migrate
```

Untuk membuat akun Superadmin melalui seeder, isi konfigurasi berikut di `.env`:

```dotenv
SUPERADMIN_USERNAME=superadmin
SUPERADMIN_NAME=Superadmin
SUPERADMIN_EMAIL=admin@example.com
SUPERADMIN_PASSWORD=ganti_dengan_password_awal_yang_kuat
```

Password awal harus memiliki panjang minimal 12 karakter. Jangan menggunakan contoh password secara langsung.

Kemudian jalankan:

```bash
php artisan db:seed
```

Seeder akan melewati pembuatan akun jika Superadmin sudah tersedia. Setelah akun dibuat, hapus nilai `SUPERADMIN_PASSWORD` dari `.env` sesuai petunjuk seeder.

## Konfigurasi Email

Fitur pemulihan password memerlukan konfigurasi SMTP yang valid pada `.env`.

Sesuaikan pengaturan email dengan penyedia SMTP yang digunakan. Jangan mengunggah kredensial email atau password ke repository.

## Keamanan

- Jangan mengunggah file `.env` atau kredensial ke GitHub.
- Gunakan password yang kuat dan berbeda untuk setiap akun.
- Gunakan database demo hanya untuk pengujian.
- Pastikan data demo tidak mengandung informasi pribadi yang tidak boleh dibagikan.
- Untuk penggunaan produksi, terapkan HTTPS, pencadangan rutin, dan pengaturan akses yang sesuai.
- Uji autentikasi, otorisasi, pengelolaan sesi, serta fitur pemulihan password sebelum aplikasi digunakan secara nyata.

## Pemecahan Masalah

### SQLite Driver Tidak Ditemukan

Pastikan ekstensi `pdo_sqlite` dan `sqlite3` aktif pada PHP yang digunakan.

```bash
php -m
```

### Database Tidak Ditemukan

Pastikan file `database/database.sqlite` tersedia dan `DB_CONNECTION=sqlite` telah dikonfigurasi.

### Dependensi Gagal Dipasang

Periksa versi PHP dan ekstensi yang dibutuhkan, kemudian jalankan kembali:

```bash
composer install
```

### Konfigurasi Tidak Terbaca

```bash
php artisan config:clear
```

## Lisensi

Ketentuan penggunaan dan distribusi mengikuti lisensi yang ditetapkan pada repository.
