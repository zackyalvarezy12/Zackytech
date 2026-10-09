# Dynamic Personal Portfolio — Zacky Alvarezy

Portfolio PHP Native dengan MySQL/PDO, Bootstrap 5, dashboard admin, upload file, dan formulir kontak yang menyimpan pesan ke database.

## Persyaratan

- XAMPP dengan Apache, PHP 8.1+ (PDO MySQL, Fileinfo, dan upload file aktif), serta MySQL/MariaDB.
- Browser modern dan koneksi internet untuk memuat Bootstrap 5 dan Google Fonts dari CDN.

## Instalasi di XAMPP

1. Salin folder proyek ke `C:\xampp\htdocs\portofolio`.
2. Buka XAMPP Control Panel, lalu jalankan **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih `C:\xampp\htdocs\portofolio\database.sql`, lalu klik **Import**. SQL akan membuat database `portfolio_native` beserta tabel dan data profil awal.
4. Periksa koneksi database di `config/config.php`. Konfigurasi awal XAMPP adalah host `127.0.0.1`, database `portfolio_native`, user `root`, dan password kosong. Ubah kredensial sesuai instalasi lokal/server.
5. Pastikan folder `uploads/images` dan `uploads/documents` dapat ditulisi oleh Apache. Folder beserta `.htaccess` pengaman sudah disediakan.
6. Buka website melalui `http://localhost/portofolio/`. Jangan membuka file PHP langsung dari file system.
7. Masuk dashboard melalui `http://localhost/portofolio/admin/login.php`.

### Akun instalasi awal

- Email: `admin@zacky.test`
- Password: `admin123`

Dashboard mewajibkan perubahan password awal sebelum menu admin lainnya dapat digunakan. Password baru harus unik dan minimal 12 karakter. Jangan menggunakan kredensial awal pada server publik.

## Dashboard admin

Dashboard memisahkan profil/SEO, skills, pengalaman, pendidikan, proyek, sertifikat, organisasi, artikel blog, navigasi, sosial media, kontak publik, dan pesan masuk. Pada menu **Navigasi**, tombol **Nonaktifkan/Aktifkan** mengatur visibilitas bagian terkait di halaman publik; nonaktifkan Blog agar menu dan bagian Blog sama-sama tersembunyi. Nomor judul bagian publik otomatis berurutan ulang ketika halaman dinonaktifkan. Form CRUD mendukung pencarian, pengurutan, konfirmasi penghapusan, serta upload foto/gambar JPG, PNG, WEBP (maks. 2 MB) dan dokumen PDF (maks. 5 MB). Form publik menyimpan pesan ke menu **Pesan Masuk**.

Proyek mendukung satu gambar sampul dan galeri hingga 30 gambar. Saat mengedit proyek, pilih banyak file sekaligus pada **Foto galeri proyek** (maksimal 12 foto setiap pengiriman); foto tersimpan sesuai urutan upload, dan admin bisa memilih foto galeri yang akan dihapus. Pengunjung dapat membuka kartu proyek untuk menelusuri galeri.

Website menyediakan tombol **Tampilan terang/gelap** di navigasi. Pilihan tema disimpan pada browser dan tetap berlaku saat halaman dibuka kembali.

Semua isian konten dikeluarkan dengan escaping HTML. Pengubahan data memakai PDO prepared statements, session admin, password hashing, validasi backend, serta token CSRF. File upload diberi nama acak dan direktori upload menolak file executable.

## Struktur utama

```text
admin/                 Dashboard admin dan CRUD
assets/css/            Gaya dashboard dan website publik
assets/js/             JavaScript antarmuka
config/                Pengaturan dan koneksi PDO
database.sql           Skema dan data awal
uploads/images/        Foto dan gambar
uploads/documents/     CV dan sertifikat PDF
index.php              Halaman publik dinamis
```

Untuk hosting publik, gunakan HTTPS, kredensial database terpisah dengan hak akses minimum, password admin yang kuat, dan backup database/file secara berkala.
