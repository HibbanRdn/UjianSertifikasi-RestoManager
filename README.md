# RestoManager v0.6

RestoManager adalah aplikasi web restoran **walk-in/dine-in** untuk demonstrasi sertifikasi Programmer BNSP. Aplikasi ini memakai PHP Native berorientasi objek dan MariaDB. Customer memilih meja sebelum menambahkan item pertama, dapat membuat beberapa pesanan dalam satu kunjungan, lalu Petugas menyajikan pesanan dan mengosongkan meja melalui aksi yang terpisah.

> Pembayaran QRIS dan tunai pada aplikasi ini sepenuhnya **simulasi**. QRIS yang ditampilkan adalah aset dummy lokal, tidak terhubung ke bank atau payment gateway, dan tidak memverifikasi dana nyata.

## Fitur

- **Customer:** registrasi, login, katalog publik, pemilihan meja, keranjang, checkout simulasi, receipt, Orders, Riwayat, dan profil.
- **Petugas:** dashboard operasional, aksi **Sajikan** per pesanan, serta **Kosongkan Meja** setelah seluruh pesanan kunjungan disajikan.
- **Admin:** seluruh hak operasional Petugas ditambah pengelolaan kategori, menu, meja, akun Petugas, dan laporan simulasi.
- **Keamanan dan integritas:** password hashing, session regeneration, CSRF, role/ownership guard server-side, PDO prepared statement, transaksi InnoDB, idempotency checkout, dan foreign key.

## Teknologi

- PHP Native 8.2
- MariaDB (dikembangkan dan diuji dengan MariaDB 10.4)
- XAMPP untuk Apache, PHP, dan MariaDB lokal
- Composer dan PHPUnit
- Bootstrap lokal, CSS, serta JavaScript

Arsitektur aplikasi mengikuti pemisahan **Controller → Service → Repository → PDO**. Controller menangani HTTP, Service menyimpan aturan bisnis, Repository menangani query terparameter, dan View hanya menyajikan antarmuka.

## Struktur proyek

```text
app/                 Controller, Core, Exception, Repository, dan Service
bootstrap/           Penyusunan dependency aplikasi
config/              Konfigurasi aplikasi, database, dan route
database/            Schema, seed, contoh akun DB, serta migration
public/              Web root: index.php, CSS, JavaScript, Bootstrap, dan aset
tests/               Unit, Integration, dan Support
views/               Template halaman public, customer, staff, admin, checkout
.env.example         Contoh konfigurasi lokal
```

Web root harus mengarah ke folder `public/`.

## Persyaratan

- PHP 8.2 beserta ekstensi `PDO`, `pdo_mysql`, dan `mbstring`
- MariaDB atau MySQL yang mendukung InnoDB dan foreign key
- Composer 2
- Apache XAMPP atau PHP built-in server

## Instalasi lokal

1. Jalankan Apache dan MariaDB dari XAMPP.
2. Salin konfigurasi contoh lalu isi nilai lokal Anda:

   ```bash
   cp .env.example .env
   ```

3. Instal dependency Composer:

   ```bash
   /Applications/XAMPP/xamppfiles/bin/php /opt/homebrew/bin/composer install
   ```

4. Buat schema dan data demo menggunakan akun database yang berhak membuat database:

   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -P 3306 -u root < database/schema.sql
   /Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -P 3306 -u root < database/seed.sql
   ```

   Opsional: gunakan `database/create_app_user.sql.example` setelah mengganti placeholder password untuk membuat akun aplikasi terbatas. Sesuaikan `.env` dengan akun tersebut. Untuk database lama yang belum berisi Es Campur dan Tempe Mendoan, jalankan migration berikut sekali:

   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -P 3306 -u root < database/migrations/20261013_add_es_campur_tempe_mendoan.sql
   ```

5. Jalankan aplikasi tanpa mengubah konfigurasi Apache global:

   ```bash
   /Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8088 -t public
   ```

   Buka `http://127.0.0.1:8088`. Bila memakai VirtualHost XAMPP, set `DocumentRoot` ke direktori `public/` saja.

## Akun demo seed

Kredensial berikut hanya dibuat oleh `database/seed.sql` untuk lingkungan lokal/demonstrasi:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@restomanager.local` | `Admin123!` |
| Petugas | `staff@restomanager.local` | `Staff123!` |

Customer dibuat melalui `/register`; role `customer` selalu ditetapkan oleh server.

## Pengujian

Setelah `composer install` dan konfigurasi database selesai, jalankan:

```bash
/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit
/Applications/XAMPP/xamppfiles/bin/php tests/Support/foundation_check.php
```

Untuk pemeriksaan konfigurasi Composer:

```bash
/opt/homebrew/bin/composer validate --strict
```

## Catatan penggunaan

- Guest dapat menjelajah Beranda, Kategori, dan Menu, tetapi perlu login sebelum item benar-benar masuk keranjang.
- Meja berubah menjadi `occupied` hanya setelah checkout pertama berhasil disimpan.
- Status pembayaran yang dicatat adalah `paid_simulated`, bukan bukti penerimaan dana.
- Order berstatus `served` masuk ke Riwayat; visit dan meja hanya ditutup melalui aksi **Kosongkan Meja** setelah seluruh order telah disajikan.

## Lisensi

Proyek dibuat untuk demonstrasi akademik dan sertifikasi Programmer BNSP.
