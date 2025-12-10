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

### 📐 **Project Editor & Individual Editing**
- **Project-based Workflow:** Generate certificate projects untuk editing individual
- **Smart Alignment Guides:** Visual snapping ke tepi, center, dan objek lain (disable dengan Ctrl)
- **Floating Toolbar:** Format teks dengan toolbar kontekstual yang mengikuti seleksi
- **Multi-selection Support:** Edit multiple objek sekaligus dengan common property detection
- **Live Canvas Preview:** Real-time preview setiap perubahan tanpa refresh
- **Bulk Edit Mode:** Terapkan perubahan ke semua/selected certificates sekaligus
- **Project Lifecycle:** Draft → Edit individual → Finalize → Download ZIP
- **Background PDF Generation:** Queue-based dengan progress tracking real-time

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
- **Canvas Editor:** Fabric.js v4.x dengan custom patches dan smart guides
- **PDF:** Spatie Browsershot (Chrome Headless) dengan canvas-to-PDF pipeline
- **Queue:** Laravel Queue dengan database driver dan progress tracking
- **File Processing:** Maatwebsite Excel untuk import Excel/CSV
- **State Management:** JSON-based canvas state dengan custom serialization

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

**Standard Bulk Generation (Direct to PDF):**
1. Fill event details (nama acara, tanggal, tempat)
2. Setup certificate numbering with custom start
3. Upload signatures (up to 3)
4. Select template
5. Choose data source
6. Generate & auto-download ZIP

**Project-based Editing (New!):**
1. Click "Generate as Project" instead of "Generate"
2. System creates certificate project with canvas states
3. Navigate to Project Editor
4. Edit individual certificates or use bulk edit mode
5. Use floating toolbar for text formatting (bold, font, size, alignment)
6. Smart guides auto-snap objects for perfect alignment
7. Save changes (auto-saved per certificate)
8. Click "Finalize Project" to generate all PDFs
9. Download ZIP when ready (progress tracking available)

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

#### 9. Project Editor - ZIP File Not Found
```
❌ Error: File ZIP tidak ditemukan

✅ Solution: 
// 1. Ensure queue worker is running
php artisan queue:work --timeout=300

// 2. Check project status is 'completed'
// Projects must be finalized before downloading

// 3. Verify zip_path in database (should be relative)
// Correct: public/certificates/project-1-name.zip
// Wrong: /full/path/to/storage/app/public/certificates/...

// 4. Check storage/app/public/certificates/ directory
ls storage/app/public/certificates/
```

#### 10. Smart Guides Not Showing
```
❌ Smart alignment guides not visible

✅ Solution:
// Guides only show during object movement
// 1. Click and drag object to activate
// 2. Check snap tolerance (default: 5px)
// 3. Press Ctrl to temporarily disable snapping
// 4. Release Ctrl to re-enable
```

#### 11. Floating Toolbar Not Updating
```
❌ Toolbar shows wrong values after selection change

✅ Solution:
// 1. Ensure canvas events are firing
console.log('selection:updated', e.target)

// 2. Check for mixed states (shows "Mixed" for different values)
// 3. Refresh browser if toolbar stuck
// 4. Clear browser cache (Ctrl+Shift+Del)
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
│   ├── certificates/    # Individual PDFs & Project ZIP files
│   ├── batches/        # Batch ZIP files
│   ├── templates/      # Saved templates
│   └── signatures/     # Signature images
├── temp/               # Temporary files
├── uploads/            # Excel/CSV uploads
└── projects/           # Project-specific data
    └── {project_id}/   # Canvas states & generated PDFs
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

# Project Editor
GET  /projects                    # List all certificate projects
GET  /projects/{id}/edit          # Open project editor
POST /projects/{id}/certificate/{certId} # Update individual certificate
POST /projects/{id}/finalize      # Generate PDFs for all certificates
GET  /projects/{id}/download       # Download project ZIP
GET  /projects/{id}/progress       # Get finalization progress
DELETE /projects/{id}             # Delete project
```

### Database Models

#### Certificate
```php
// Key fields
'recipient_name', 'certificate_number', 'event_name',
'event_date', 'certificate_batch_id', 'pdf_path',
// Project system fields
'project_id', 'canvas_state', 'is_edited', 'page_order'
```

#### CertificateProject
```php
// Key fields
'project_name', 'event_name', 'template_id',
'global_settings', 'status', 'total_certificates',
'edited_count', 'zip_path', 'finalized_at'
// Status: draft, finalizing, completed
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

### v4.0.0 - Project Editor & Individual Certificate Editing (Latest)
✅ **Project-based Workflow:** Generate certificate projects untuk editing individual  
✅ **Smart Alignment Guides:** Visual snapping dengan snap tolerance 5px  
✅ **Floating Toolbar:** Kontekstual formatting toolbar dengan popover controls  
✅ **Multi-selection Support:** Edit multiple objek dengan mixed state detection  
✅ **Canvas State Management:** JSON-based serialization untuk canvas persistence  
✅ **Background PDF Generation:** Queue jobs dengan progress tracking  
✅ **Bulk Edit Mode:** Terapkan perubahan ke multiple certificates sekaligus  
✅ **Project Lifecycle:** Draft → Edit → Finalize → Download ZIP  
✅ **Live Preview:** Real-time canvas updates tanpa refresh  
✅ **Keyboard Shortcuts:** Arrow keys untuk nudging, Ctrl untuk disable snapping  
✅ **ZIP Download Fix:** Relative path storage untuk reliable downloads  

### v3.1.1 - Canvas Export Stability & Validation
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
