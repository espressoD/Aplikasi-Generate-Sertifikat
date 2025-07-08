# Aplikasi Generator Sertifikat Bulk dengan Laravel

Aplikasi web sederhana yang dibangun menggunakan Laravel 7 untuk membuat sertifikat secara massal dari data file Excel/CSV. Aplikasi ini dilengkapi dengan dashboard admin, fitur preview, dan kemampuan untuk mengunduh semua sertifikat yang dihasilkan dalam satu file ZIP.

<p align="center"><img src="https://i.imgur.com/ppyUYbu.png" width="1000"></p>

---

## Fitur Utama

-   **Dashboard Admin:** Halaman utama yang menampilkan statistik ringkas seperti total sertifikat, jumlah acara, dan grafik pembuatan sertifikat per bulan.
-   **Generate Sertifikat Bulk:** Mengunggah file `.xlsx` atau `.csv` yang berisi daftar nama peserta untuk dibuatkan sertifikat secara otomatis.
-   **Unduh dalam Format ZIP:** Semua sertifikat yang berhasil dibuat akan dibungkus dalam satu file `.zip` untuk kemudahan distribusi.
-   **Penamaan File Otomatis:** Setiap file PDF di dalam ZIP akan diberi nama secara otomatis berdasarkan urutan dan nama peserta (contoh: `1_budi-sanjaya.pdf`).
-   **Tanda Tangan Dinamis:** Kemampuan untuk memilih jumlah penandatangan (1, 2, atau 3) dan mengunggah nama, jabatan, serta gambar tanda tangan untuk masing-masing.
-   **Preview Sertifikat:** Sebelum men-generate secara massal, pengguna bisa melihat pratinjau desain sertifikat dengan data yang diinput.
-   **Format Tanggal Cerdas:** Secara otomatis memformat tanggal acara menjadi satu tanggal (misal: `7 Juli 2025`) atau rentang tanggal (misal: `7 - 9 Juli 2025`) tergantung input.

## Teknologi yang Digunakan

-   **Backend:** PHP 7.4, Laravel 7
-   **Frontend:** AdminLTE 3, Bootstrap 4, jQuery
-   **Database:** MySQL (dapat disesuaikan)
-   **Dependensi Utama:**
    -   `barryvdh/laravel-dompdf`: Untuk konversi HTML ke PDF.
    -   `maatwebsite/excel`: Untuk membaca data dari file Excel dan CSV.

---

## Panduan Instalasi Lokal

Untuk menjalankan proyek ini di lingkungan lokal (misalnya menggunakan XAMPP), ikuti langkah-langkah berikut:

### Prasyarat

-   PHP versi 7.4
-   Composer
-   Node.js & NPM
-   Server lokal seperti XAMPP atau Laragon

### Langkah-langkah Instalasi

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/NAMA_PENGGUNA_ANDA/NAMA_REPOSITORI_ANDA.git](https://github.com/NAMA_PENGGUNA_ANDA/NAMA_REPOSITORI_ANDA.git)
    cd NAMA_REPOSITORI_ANDA
    ```

2.  **Install Dependensi PHP**
    ```bash
    composer install
    ```

3.  **Buat File `.env`**
    Salin file `.env.example` menjadi `.env`.
    ```bash
    copy .env.example .env
    ```
    (Untuk pengguna Linux/Mac, gunakan `cp .env.example .env`)

4.  **Generate Kunci Aplikasi**
    ```bash
    php artisan key:generate
    ```

5.  **Konfigurasi Database**
    -   Buka phpMyAdmin atau tool database Anda dan buat database baru (misalnya `db_sertifikat`).
    -   Buka file `.env` dan sesuaikan pengaturan database Anda:
        ```env
        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=db_sertifikat
        DB_USERNAME=root
        DB_PASSWORD=
        ```

6.  **Jalankan Migrasi**
    Perintah ini akan membuat semua tabel yang dibutuhkan di database Anda.
    ```bash
    php artisan migrate
    ```

7.  **Install Dependensi JavaScript**
    ```bash
    npm install
    ```

8.  **Kompilasi Aset Frontend**
    ```bash
    npm run dev
    ```

9.  **Jalankan Server**
    ```bash
    php artisan serve
    ```
    Aplikasi Anda sekarang berjalan di `http://127.0.0.1:8000`.

---

## Cara Penggunaan

1.  Buka browser dan akses halaman dashboard: `http://127.0.0.1:8000/dashboard`.
2.  Navigasikan ke menu "Generate Bulk".
3.  Isi semua informasi acara (nama, tanggal mulai, tanggal akhir).
4.  Pilih jumlah penandatangan yang diinginkan.
5.  Isi nama, jabatan, dan unggah gambar tanda tangan untuk setiap penandatangan.
6.  Unggah file Excel/CSV yang berisi daftar nama peserta di kolom pertama.
7.  Klik tombol **"Generate & Download ZIP"**.
8.  Tunggu beberapa saat hingga proses selesai dan file ZIP akan terunduh secara otomatis.

## Troubleshooting

-   **Unduhan Gagal dengan "Server Problem" atau `download.htm`:**
    -   Masalah ini seringkali disebabkan oleh ekstensi `ZipArchive` yang tidak aktif di PHP. Pastikan baris `extension=zip` tidak memiliki tanda titik koma (`;`) di depannya pada file `php.ini` Anda, lalu restart Apache.
    -   Coba gunakan browser lain seperti Firefox. Aplikasi pihak ketiga seperti Internet Download Manager (IDM) atau fitur bawaan browser tertentu (seperti Opera GX) terkadang gagal menangani unduhan file yang dibuat secara dinamis.

-   **Error `Class '...' not found`:**
    Jalankan perintah `composer dump-autoload` untuk menyegarkan daftar class aplikasi Anda.

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
