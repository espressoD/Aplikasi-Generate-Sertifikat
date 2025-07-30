# Aplikasi Generator Sertifikat Bulk dengan Laravel

Aplikasi web canggih yang dibangun menggunakan Laravel 7 untuk membuat sertifikat secara massal dari data file Excel/CSV. Aplikasi ini dilengkapi dengan dashboard admin, editor template dinamis, sistem queue untuk performa optimal, dan kemampuan untuk mengunduh semua sertifikat yang dihasilkan dalam satu file ZIP dengan unduhan otomatis.

<p align="center"><img src="https://i.imgur.com/ppyUYbu.png" width="1000"></p>

---

## Fitur Utama

### 🎨 **Editor Template Dinamis**
-   **Canvas Editor:** Editor visual menggunakan Fabric.js untuk mendesain template sertifikat secara real-time.
-   **Drag & Drop:** Tambah, edit, dan posisikan elemen teks dan gambar dengan mudah.
-   **Template Management:** Simpan, muat, edit nama, dan hapus template yang telah dibuat.
-   **Placeholder System:** Sistem placeholder yang fleksibel untuk data dinamis (@{{nama_penerima}}, @{{nomor_sertifikat}}, dll).

### 📊 **Dashboard Komprehensif**
-   **Statistik Real-time:** Total sertifikat, jumlah acara, dan grafik pembuatan sertifikat per bulan.
-   **Dual Table System:** Kelola batch sertifikat dan sertifikat individual dalam satu dashboard.
-   **Batch Management:** Lihat status batch, progress, dan download ZIP files.
-   **Individual Access:** Lihat dan download sertifikat individual dengan sistem preview.

### 🚀 **Sistem Queue & Performance**
-   **Background Processing:** Menggunakan Laravel Queue untuk pemrosesan background yang optimal.
-   **Progress Tracking:** Real-time progress bar dengan polling untuk monitoring batch generation.
-   **Auto Download:** ZIP file otomatis terdownload ketika batch selesai diproses.
-   **Batch Recording:** Setiap batch dan sertifikat individual tercatat di database.

### 📝 **Generate Sertifikat Advanced**
-   **Bulk Generation:** Upload file `.xlsx` atau `.csv` untuk membuat sertifikat secara massal.
-   **Custom Certificate Numbers:** Input manual prefix nomor sertifikat dengan auto-increment cerdas.
-   **High Quality PDF:** Menggunakan Browsershot untuk menghasilkan PDF berkualitas tinggi (2x resolution).
-   **Signature Integration:** Support hingga 3 penandatangan dengan upload gambar signature.

### 🎯 **Fitur Signature & Placeholder**
-   **Dynamic Signatures:** Pilih 1-3 penandatangan dengan nama, jabatan, dan gambar tanda tangan.
-   **Smart Positioning:** Sistem positioning signature yang akurat dengan image replacement.
-   **Flexible Data:** Support berbagai format data peserta (nama, email, peran, ID, divisi).
-   **Preview System:** Preview sertifikat sebelum generate dengan data sample.

## Teknologi yang Digunakan

-   **Backend:** PHP 7.4+, Laravel 7
-   **Frontend:** AdminLTE 3, Bootstrap 4, jQuery, Fabric.js
-   **Database:** MySQL (dapat disesuaikan)
-   **PDF Generation:** Spatie Browsershot (Chrome Headless)
-   **Queue System:** Laravel Queue dengan database driver
-   **File Processing:** Maatwebsite Excel untuk import data
-   **Dependensi Utama:**
    -   `spatie/browsershot`: Konversi HTML ke PDF berkualitas tinggi.
    -   `maatwebsite/excel`: Membaca data dari file Excel dan CSV.
    -   `intervention/image`: Manipulasi gambar untuk signature processing.
    -   `barryvdh/laravel-dompdf`: Fallback PDF generator untuk kompatibilitas.

---

## Panduan Instalasi Lokal

Untuk menjalankan proyek ini di lingkungan lokal (misalnya menggunakan XAMPP), ikuti langkah-langkah berikut:

### Prasyarat

-   PHP versi 7.4+ dengan ekstensi berikut:
    -   `zip` (untuk pembuatan file ZIP)
    -   `gd` atau `imagick` (untuk manipulasi gambar)
    -   `curl` (untuk Browsershot)
-   Composer
-   Node.js & NPM
-   Google Chrome atau Chromium (untuk Browsershot PDF generation)
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

7.  **Setup Queue Worker (Opsional untuk Development)**
    Untuk development, Anda bisa menjalankan queue worker di terminal terpisah:
    ```bash
    php artisan queue:work --timeout=300
    ```
    Atau menggunakan sync driver di `.env` untuk testing:
    ```env
    QUEUE_CONNECTION=sync
    ```

8.  **Install Dependensi JavaScript**
    ```bash
    npm install
    ```

9.  **Kompilasi Aset Frontend**
    ```bash
    npm run dev
    ```

10. **Jalankan Server**
    ```bash
    php artisan serve
    ```
    Aplikasi Anda sekarang berjalan di `http://127.0.0.1:8000`.

---

## Cara Penggunaan

### 🎨 **Mendesain Template**

1.  **Akses Editor:** Buka `http://127.0.0.1:8000/certificates/bulk`.
2.  **Upload Background:** Pilih gambar latar belakang untuk sertifikat.
3.  **Tambah Elemen:**
    -   Klik "Tambah Teks" untuk menambah teks biasa.
    -   Gunakan dropdown "Sisipkan Elemen" untuk placeholder dan signature blocks.
4.  **Posisikan Elemen:** Drag dan resize elemen sesuai keinginan.
5.  **Simpan Template:** Klik "Simpan Desain Saat Ini" dan beri nama template.

### 📊 **Mengisi Data & Generate**

1.  **Isi Form Data:**
    -   Pilih jenis sertifikat dan isi nama acara.
    -   **Input Nomor Sertifikat:** Masukkan prefix seperti `CERT-2025-001` (akan otomatis bertambah).
    -   Atur tanggal mulai, akhir, dan penandatanganan.
    -   Tambahkan deskripsi kustom jika diperlukan.

2.  **Setup Tanda Tangan:**
    -   Pilih jumlah penandatangan (1-3).
    -   Isi nama, jabatan, dan upload gambar tanda tangan (PNG).

3.  **Upload Data Peserta:**
    -   Upload file Excel/CSV dengan kolom: `nama`, `email`, `peran`, `id_peserta`, `divisi`.

4.  **Muat Template:** Pilih template yang sudah dibuat dari tabel template.

5.  **Preview:** Klik "Preview" untuk melihat contoh sertifikat.

6.  **Generate:** Klik "Generate & Download ZIP" dan tunggu progress bar.

### 📋 **Mengelola Hasil**

1.  **Dashboard:** Akses `http://127.0.0.1:8000/dashboard` untuk melihat:
    -   Statistik total sertifikat dan acara.
    -   Grafik pembuatan sertifikat per bulan.
    -   Tabel batch sertifikat dengan status dan download ZIP.
    -   Tabel sertifikat individual dengan opsi view/download.

2.  **Download Options:**
    -   **Batch ZIP:** Download seluruh batch dalam satu file ZIP.
    -   **Individual:** View atau download sertifikat satu per satu.

---

## Struktur File Data Peserta

File Excel/CSV harus memiliki struktur kolom sebagai berikut:

| Kolom A | Kolom B | Kolom C | Kolom D | Kolom E |
|---------|---------|---------|---------|---------|
| nama    | email   | peran   | id_peserta | divisi |
| John Doe | john@email.com | Peserta | ID001 | IT |
| Jane Smith | jane@email.com | Panitia | ID002 | Marketing |

## Placeholder yang Tersedia

Template mendukung placeholder berikut:

### Data Peserta
-   `@{{nama_penerima}}` - Nama dari kolom A
-   `@{{id_lengkap_peserta}}` - Kombinasi ID dan Divisi
-   `@{{peran_penerima}}` - Peran dari kolom C

### Data Acara
-   `@{{jenis_sertifikat}}` - Jenis sertifikat yang dipilih
-   `@{{nama_acara}}` - Nama acara dari form
-   `@{{tanggal_acara}}` - Tanggal acara (format otomatis)
-   `@{{tanggal_penandatanganan}}` - Tanggal penandatanganan

### Data Sertifikat
-   `@{{nomor_sertifikat}}` - Nomor sertifikat dengan auto-increment

### Deskripsi Kustom
-   `@{{deskripsi_1}}`, `@{{deskripsi_2}}`, `@{{deskripsi_3}}` - Deskripsi tambahan

### Tanda Tangan
-   `@{{nama_penandatangan_1}}`, `@{{nama_penandatangan_2}}`, `@{{nama_penandatangan_3}}`
-   `@{{jabatan_penandatangan_1}}`, `@{{jabatan_penandatangan_2}}`, `@{{jabatan_penandatangan_3}}`

## Troubleshooting

### 🔧 **Masalah Umum**

-   **Queue Jobs Tidak Berjalan:**
    -   Pastikan queue worker berjalan: `php artisan queue:work --timeout=300`
    -   Atau gunakan `QUEUE_CONNECTION=sync` di `.env` untuk development.
    -   Restart queue worker setelah perubahan kode: `php artisan queue:restart`

-   **PDF Generation Gagal:**
    -   Install Google Chrome atau Chromium di sistem Anda.
    -   Pastikan ekstensi `zip` aktif di PHP.
    -   Periksa log error di `storage/logs/laravel.log`.

-   **Unduhan ZIP Gagal:**
    -   Pastikan ekstensi `ZipArchive` aktif di PHP (`extension=zip` di `php.ini`).
    -   Restart Apache/Nginx setelah mengubah konfigurasi PHP.
    -   Gunakan browser berbeda (hindari Internet Download Manager).

-   **Gambar Signature Tidak Muncul:**
    -   Pastikan file gambar berformat PNG.
    -   Periksa ukuran file tidak terlalu besar (< 2MB).
    -   Pastikan ekstensi GD atau ImageMagick aktif di PHP.

-   **Error Template/Database:**
    -   Jalankan `php artisan migrate` untuk memastikan tabel terbaru.
    -   Jalankan `composer dump-autoload` jika ada error class not found.
    -   Periksa koneksi database di file `.env`.

### 📝 **Tips Performa**

-   **Development:** Gunakan `QUEUE_CONNECTION=sync` untuk testing cepat.
-   **Production:** Gunakan `QUEUE_CONNECTION=database` dengan queue worker daemon.
-   **Memory:** Tingkatkan `memory_limit` di PHP untuk batch besar.
-   **Timeout:** Sesuaikan `max_execution_time` untuk proses yang lama.

### 🚀 **Production Setup**

Untuk deployment production:

1.  **Queue Worker Daemon:**
    ```bash
    php artisan queue:work --timeout=600 --sleep=3 --tries=3
    ```

2.  **Supervisor Configuration:**
    ```ini
    [program:certificate-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /path/to/application/artisan queue:work --timeout=600
    autostart=true
    autorestart=true
    numprocs=2
    ```

3.  **Cron Job untuk Queue:**
    ```bash
    * * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
    ```

---

## Contributing

Kontribusi sangat diterima! Silakan:

1.  Fork repository ini
2.  Buat branch fitur baru (`git checkout -b feature/AmazingFeature`)
3.  Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4.  Push ke branch (`git push origin feature/AmazingFeature`)
5.  Buat Pull Request

## Changelog

### v2.0.0 (Latest)
-   ✅ Dynamic template editor dengan Fabric.js
-   ✅ Queue system untuk performa optimal
-   ✅ Auto-download ZIP files
-   ✅ Individual certificate management
-   ✅ Custom certificate numbering
-   ✅ High-quality PDF dengan Browsershot
-   ✅ Signature positioning system
-   ✅ Dual dashboard tables (batch + individual)

### v1.0.0
-   ✅ Basic bulk certificate generation
-   ✅ Static template system
-   ✅ Manual ZIP download

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
