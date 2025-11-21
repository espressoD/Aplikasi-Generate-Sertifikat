# 🏆 Aplikasi Generator Sertifikat
### Advanced Certificate Generator dengan Dashboard Management

Aplikasi web canggih berbasis Laravel 7 untuk membuat sertifikat secara massal dari data Excel/CSV dan database karyawan. Dilengkapi dashboard komprehensif, editor template dinamis, sistem queue untuk performa optimal, dan advanced certificate numbering system.

<p align="center"><img src="https://i.imgur.com/Y3Ej01a.png" width="1000"></p>

---

## ⭐ Fitur Utama

### 🎨 **Editor Template Visual**
- **Canvas Editor:** Desain template real-time dengan Fabric.js
- **Drag & Drop:** Posisikan teks dan gambar dengan mudah
- **Placeholder System:** Data dinamis (@{{nama_penerima}}, @{{nomor_sertifikat}}, dll)
- **Template Management:** Simpan, muat, edit, dan hapus template

### 📝 **Advanced Certificate Numbering**
- **Flexible Position:** `{AUTO}` dapat ditempatkan di mana saja
- **Custom Start Number:** `{AUTO:start_number}` untuk kontrol penuh
- **Smart Padding:** Auto-adjust padding berdasarkan start number
- **Live Preview:** Real-time preview dengan pattern detection
- **Format Examples:**
  - `CERT-{AUTO:100}-2025` → CERT-100-2025, CERT-101-2025
  - `PKL-{AUTO:50}` → PKL-050, PKL-051, PKL-052
  - `{AUTO:1000}` → 1000, 1001, 1002

### 📊 **Dashboard & Management**
- **Overview Cards:** Statistik total sertifikat dan acara
- **Interactive Chart:** Grafik 6 bulan terakhir dengan Chart.js
- **Batch Management:** Kelola batch sertifikat dengan status real-time
- **Individual Certificates:** Management sertifikat per individu
- **AJAX Search & Filter:** Real-time tanpa reload halaman

### 👥 **Dual Data Source**
- **File Upload:** Import data dari Excel/CSV
- **Database Karyawan:** CRUD lengkap dengan search/filter
- **Cross-page Selection:** State management di seluruh halaman
- **Bulk Operations:** Pilih semua data dengan satu klik

### ⚡ **Performance & Queue**
- **Background Processing:** Laravel Queue untuk batch besar
- **Progress Tracking:** Real-time monitoring dengan progress bar
- **Auto Download:** ZIP file otomatis setelah selesai
- **High Quality PDF:** Browsershot dengan 2x resolution

## 🛠 Tech Stack

- **Backend:** PHP 7.4+, Laravel 7, MySQL
- **Frontend:** AdminLTE 3, Bootstrap 4, jQuery, Chart.js 3.9.1
- **Editor:** Fabric.js untuk canvas manipulation
- **PDF:** Spatie Browsershot (Chrome Headless)
- **Queue:** Laravel Queue dengan database driver
- **File Processing:** Maatwebsite Excel

---

## 🚀 Quick Start

### 📋 System Requirements
- **PHP:** 7.4+ dengan ekstensi zip, gd/imagick, curl
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Server:** XAMPP, Laragon, atau server lain
- **Browser:** Chrome/Chromium (untuk PDF generation)
- **Tools:** Composer, Node.js & NPM

### ⚡ Installation Steps

```bash
# 1. Clone project
git clone [repository-url]
cd Aplikasi-Generate-Sertifikat

# 2. Install dependencies
composer install
npm install && npm run dev

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
DB_DATABASE=sertifikat_app
DB_USERNAME=root
DB_PASSWORD=

# 5. Run migrations
php artisan migrate
php artisan storage:link

# 6. Start queue worker
php artisan queue:work --timeout=300

# 7. Start server
php artisan serve
```

Aplikasi akan berjalan di `http://127.0.0.1:8000`

### 🔧 Chrome Configuration

Edit file `.env` untuk path Chrome:
```env
# Windows
BROWSERSHOT_CHROME_PATH="C:\Program Files\Google\Chrome\Application\chrome.exe"

# Linux  
BROWSERSHOT_CHROME_PATH=/usr/bin/google-chrome

# Mac
BROWSERSHOT_CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
```

---

## 📖 User Guide

### 🎨 1. Design Template

1. **Akses Editor:** Buka `/certificates/bulk`
2. **Upload Background:** Pilih gambar latar belakang
3. **Add Elements:**
   - Klik "Tambah Teks" untuk teks biasa
   - Gunakan dropdown "Sisipkan Elemen" untuk placeholder
   - Drag & resize elemen sesuai keinginan
4. **Save Template:** Klik "Simpan Desain" dan beri nama

### 📊 2. Generate Certificates

#### Certificate Numbering
```
Format Examples:
CERT-{AUTO:100}-2025 → CERT-100-2025, CERT-101-2025
PKL-{AUTO:50}        → PKL-050, PKL-051, PKL-052  
{AUTO:1000}          → 1000, 1001, 1002
2025-{AUTO}-TRAINING → 2025-001-TRAINING, 2025-002-TRAINING
```

#### Data Source Options

**Option A - Upload Excel/CSV:**
- Format: `.xlsx`, `.xls`, `.csv`
- Required columns: `nama`, `email`, `peran`, `id_peserta`, `divisi`
- Optional: `nilai_1`, `nilai_2`, `nilai_3`, `nilai_4`

**Option B - Database Karyawan:**
- Search by nama/NPK
- Filter by divisi
- Cross-page selection
- Bulk select all

#### Generation Process
1. Fill event details (nama acara, tanggal, tempat)
2. Setup certificate numbering with custom start
3. Upload signatures (up to 3)
4. Select template
5. Choose data source
6. Generate & auto-download ZIP

### 👥 3. Manage Employees

- **Add:** Tombol "Tambah Karyawan" 
- **Edit:** Klik ikon pensil
- **Delete:** Klik ikon trash
- **Search:** Real-time search nama/NPK
- **Filter:** Dropdown divisi
- **Bulk Select:** "Pilih Semua" untuk all data

### 📋 4. Dashboard Overview

**Navigation Cards:**
- **Total Sertifikat** → Individual certificates list
- **Jumlah Acara** → Batch management  
- **Akses Cepat** → Generate form

**Features:**
- Interactive 6-month chart
- Recent batches (5 latest)
- Real-time statistics
- AJAX search & filters

---

## 📝 Data Format Reference

### Excel/CSV Structure
| nama | email | peran | id_peserta | divisi | nilai_1 | nilai_2 |
|------|-------|-------|------------|--------|---------|---------|
| John Doe | john@email.com | Peserta | ID001 | IT | 85 | 90 |

### Available Placeholders
**Participant Data:**
- `@{{nama_penerima}}` - Nama peserta
- `@{{id_lengkap_peserta}}` - ID + Divisi
- `@{{peran_penerima}}` - Role peserta

**Event Data:**
- `@{{jenis_sertifikat}}` - Certificate type
- `@{{nama_acara}}` - Event name
- `@{{tanggal_acara}}` - Event date
- `@{{nomor_sertifikat}}` - Certificate number

**Signatures:**
- `@{{nama_penandatangan_1/2/3}}` - Signer name
- `@{{jabatan_penandatangan_1/2/3}}` - Signer position

**Optional Values:**
- `@{{nilai_1/2/3/4}}` - Score values
- `@{{deskripsi_1/2/3}}` - Custom descriptions

---

## 🔧 Troubleshooting

### ⚠️ Common Issues

#### 1. Queue Jobs Not Running
```bash
# Start queue worker
php artisan queue:work --timeout=300

# For development, use sync
QUEUE_CONNECTION=sync

# Restart after code changes
php artisan queue:restart
```

#### 2. PDF Generation Failed
```bash
# Verify Chrome path in .env
BROWSERSHOT_CHROME_PATH="C:\Program Files\Google\Chrome\Application\chrome.exe"

# Test manually
php artisan tinker
>>> \Spatie\Browsershot\Browsershot::html('<h1>Test</h1>')->pdf();
```

#### 3. Certificate Numbering Issues
```javascript
// Correct format
CERT-{AUTO:100}-2025 ✅
CERT-AUTO:100-2025   ❌

// Test preview in browser console
// Check for JavaScript errors
```

#### 4. File Upload Problems
```php
// php.ini settings
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 512M

// Check file permissions
chmod 755 storage/app/uploads
```

#### 5. AJAX Search Not Working
```html
<!-- Verify CSRF token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Check jQuery loading -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
```

#### 6. Duplicate Certificate Number Error
```
❌ Error: SQLSTATE[23000] Duplicate entry for certificate_number

✅ Solution: Use unique prefixes per batch
CERT-2025-001, CERT-2025-002 (OK)
CERT-2025-001 twice (ERROR)
```

#### 7. Canvas Export / Generate Errors
```javascript
// ❌ Error: Cannot read properties of null (reading 'clearRect')
// ❌ Warning: alphabetical is not a valid textBaseline
// ❌ Warning: willReadFrequently performance warning

✅ Solution: Patches applied automatically
// Fabric.js patches fix these issues at runtime
// Refresh browser (Ctrl+F5) to apply patches
// Check console for: ✅ Fabric.js patches applied
```

#### 8. Validation Failed - Missing Required Fields
```
❌ Error: Server returned HTML instead of JSON

✅ Solution: Fill all placeholder data before generating
// Required placeholders:
// - @{{nama_acara}} - Event name
// - @{{jenis_sertifikat}} - Certificate type  
// - @{{tanggal_acara}} - Event date range
// - @{{tanggal_penandatanganan}} - Signing date & place
// - @{{nomor_sertifikat}} - Certificate number format

// Click each placeholder on canvas to fill data
```

### 🔍 Debug Commands
```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear

# Database check
php artisan migrate:status

# Queue monitoring
php artisan queue:failed
php artisan queue:monitor
```

---

## 📚 Advanced Configuration

### 🔧 Production Setup

#### Environment Configuration
```env
# Production settings
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

# Performance
PHP_MEMORY_LIMIT=512M
MAX_EXECUTION_TIME=300
BROWSERSHOT_TIMEOUT=60
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/Aplikasi-Generate-Sertifikat/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        include fastcgi_params;
    }
    
    client_max_body_size 10M;
}
```

#### Queue Supervisor
```bash
# Supervisor configuration
[program:certificate-worker]
command=php /path/to/application/artisan queue:work --timeout=600
autostart=true
autorestart=true
numprocs=2
```

#### Deployment Steps
```bash
# 1. Clone & install
git clone [repo-url]
composer install --optimize-autoloader --no-dev
npm ci && npm run production

# 2. Configuration
cp .env.example .env
php artisan key:generate
php artisan migrate --force

# 3. Optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 📊 Performance Optimization

#### Database Indexing
```sql
-- Add performance indexes
ALTER TABLE certificates ADD INDEX idx_event_name (event_name);
ALTER TABLE certificate_batches ADD INDEX idx_status (status);
ALTER TABLE karyawans ADD INDEX idx_nama (nama);
```

#### Storage Structure
```
storage/app/
├── public/
│   ├── certificates/    # Individual PDFs
│   ├── batches/        # Batch ZIP files
│   ├── templates/      # Saved templates
│   └── signatures/     # Signature images
├── temp/               # Temporary files
└── uploads/            # Excel/CSV uploads
```

#### Backup Strategy
```bash
# Database backup
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# File backup
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

---

## 🚀 API Reference

### Main Endpoints

```bash
GET  /dashboard                    # Dashboard overview
GET  /certificates/list           # Individual certificates
GET  /batches/list               # Batch management
POST /certificates/bulk          # Generate certificates
GET  /certificates/{id}/download # Download individual
GET  /batches/{id}/download      # Download ZIP
```

### Database Models

#### Certificate
```php
// Key fields
'recipient_name', 'certificate_number', 'event_name',
'event_date', 'certificate_batch_id', 'pdf_path'
```

#### CertificateBatch  
```php
// Key fields
'batch_name', 'event_name', 'total_certificates',
'status', 'zip_path'
```

#### Karyawan
```php
// Key fields
'nama', 'npk_id', 'divisi'
```

---

## 📋 Version History

### v3.1.1 - Canvas Export Stability & Validation (Latest)
✅ **Canvas Export Fixes:** 3-tier fallback system untuk export canvas ke PNG  
✅ **Fabric.js Patches:** Auto-fix textBaseline dan willReadFrequently warnings  
✅ **Frontend Validation:** Validasi field wajib sebelum submit  
✅ **Better Error Handling:** Informative messages untuk troubleshooting  
✅ **DivisiList Fix:** Handle array/object response dari server  
✅ **Performance Optimization:** willReadFrequently untuk operasi canvas cepat  

### v3.1.0 - Advanced Certificate Numbering
✅ **Flexible Auto-Increment:** `{AUTO}` dapat ditempatkan di mana saja  
✅ **Custom Start Number:** `{AUTO:start_number}` untuk kontrol penuh  
✅ **Smart Padding:** Auto-adjust padding berdasarkan start number  
✅ **Live Preview:** Real-time preview dengan pattern detection  
✅ **Multiple Format Support:** Berbagai pattern dengan priority detection  
✅ **Backward Compatibility:** Format lama tetap berfungsi  

**Examples:**
```
CERT-{AUTO:100}-2025 → CERT-100-2025, CERT-101-2025
PKL-{AUTO:50} → PKL-050, PKL-051, PKL-052
{AUTO:1000} → 1000, 1001, 1002
```

### v3.0.0 - Database Integration & AJAX  
✅ **Dual Data Source:** File upload DAN database karyawan  
✅ **CRUD Management:** Database karyawan dengan validasi  
✅ **AJAX Interface:** Tanpa reload halaman  
✅ **Smart Search:** Real-time search nama/NPK  
✅ **Cross-page Selection:** State management di seluruh halaman  
✅ **Bulk Operations:** Pilih semua data dengan satu klik  

### v2.0.0 - Dynamic Template & Queue  
✅ Dynamic template editor dengan Fabric.js  
✅ Queue system untuk performa optimal  
✅ Auto-download ZIP files  
✅ Individual certificate management  
✅ High-quality PDF dengan Browsershot  
✅ Signature positioning system  

### v1.0.0 - Basic Generation  
✅ Basic bulk certificate generation  
✅ Static template system  
✅ Manual ZIP download  

---

## 🤝 Contributing

### Development Workflow
```bash
# Setup development
git clone [repo-url]
cd Aplikasi-Generate-Sertifikat
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate && php artisan storage:link

# Start development
npm run watch
php artisan serve
```

### Code Standards
- **PSR-12** PHP coding standards
- **Laravel Best Practices** follow conventions  
- **JavaScript ES6+** modern syntax
- **Responsive Design** mobile-first approach

### Pull Request Guidelines
1. Fork repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request with detailed description

---

## 📄 License & Resources

### License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

### Open Source Dependencies
- **Laravel 7:** PHP web application framework
- **AdminLTE 3:** Bootstrap-based admin template  
- **Chart.js:** Interactive chart library
- **Fabric.js:** Canvas manipulation library
- **Spatie Browsershot:** PDF generation library
- **Maatwebsite Excel:** Excel file processing

### Documentation Links
- [Laravel 7 Documentation](https://laravel.com/docs/7.x)
- [AdminLTE 3 Documentation](https://adminlte.io/docs/3.0/)
- [Chart.js Documentation](https://www.chartjs.org/docs/)
- [Advanced Certificate Numbering](ADVANCED_CERTIFICATE_NUMBERING.md)

### Support & Community
- 💬 Issues: [GitHub Issues](https://github.com/espressoD/Aplikasi-Generate-Sertifikat/issues)
- 📖 Wiki: [Project Wiki](https://github.com/espressoD/Aplikasi-Generate-Sertifikat/wiki)
- 📊 **Project Stats:** 15,000+ lines of code, 150+ files, 25+ major features

---

<div align="center">

**⭐ Star this repository if it helped you! ⭐**

**Advanced Certificate Generator with Flexible Numbering System**

[🚀 Quick Start](#-quick-start) | [📖 User Guide](#-user-guide) | [🔢 Certificate Numbering](ADVANCED_CERTIFICATE_NUMBERING.md) | [🐛 Report Issues](https://github.com/espressoD/Aplikasi-Generate-Sertifikat/issues)

Made with ❤️ for Laravel Community

</div>
