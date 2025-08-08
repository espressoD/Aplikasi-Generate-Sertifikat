# Fix untuk Error "MySQL server has gone away"

## Masalah
Error `SQLSTATE[HY000]: General error: 2006 MySQL server has gone away` terjadi saat:
1. **Menjalankan queue jobs** yang memakan waktu lama
2. **Upload file CSV/Excel besar** (>1MB atau >100 baris)
3. **Memproses data participant** dalam jumlah besar

## Penyebab
1. **MySQL timeout** - Koneksi database terputus karena job berjalan terlalu lama
2. **Wait timeout** - MySQL server menutup koneksi idle yang terlalu lama
3. **Memory limit** - Job menggunakan terlalu banyak memory untuk file besar
4. **Connection pool** - Terlalu banyak koneksi aktif bersamaan
5. **File processing** - Excel::toCollection() memuat seluruh file ke memory sekaligus

## Solusi yang Telah Diimplementasikan

### 1. Database Configuration (`config/database.php`)
```php
'options' => [
    PDO::ATTR_TIMEOUT => 60, // Connection timeout
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION wait_timeout=28800, interactive_timeout=28800',
    PDO::ATTR_PERSISTENT => false, // Disable persistent connections for queue jobs
]
```

### 2. Job Configuration
- **Timeout**: 300 seconds (5 minutes) per job
- **Retries**: 3 attempts untuk GenerateCertificateJob, 2 untuk GenerateZipJob
- **Database Reconnection**: `\DB::reconnect()` sebelum operasi database

### 3. File Processing Optimization 🆕
- **Large File Detection**: File >1MB diproses dengan chunking
- **CSV Chunking**: Manual line-by-line processing untuk CSV besar
- **Excel Memory Boost**: Temporary memory limit increase untuk Excel files
- **Row Limit**: Maksimal 1000 baris per file untuk mencegah memory overflow
- **Batch Dispatch**: Jobs di-dispatch dalam batch 10 untuk mencegah memory spike

### 4. Retry Mechanism
Implementasi retry logic dengan reconnection untuk operasi database yang gagal:
```php
$retryCount = 0;
$maxRetries = 3;

while ($retryCount < $maxRetries) {
    try {
        // Database operation
        break;
    } catch (\Illuminate\Database\QueryException $e) {
        if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
            \DB::reconnect();
            $retryCount++;
            sleep(1);
        } else {
            throw $e;
        }
    }
}
```

## Konfigurasi XAMPP MySQL (Opsional)

### File: `C:\xampp\mysql\bin\my.ini`
Tambahkan atau ubah pengaturan berikut:

```ini
[mysqld]
# Timeout settings
wait_timeout = 28800
interactive_timeout = 28800
connect_timeout = 60
net_read_timeout = 600
net_write_timeout = 600

# Memory settings
max_allowed_packet = 64M
innodb_buffer_pool_size = 256M
key_buffer_size = 64M

# Connection settings
max_connections = 200
max_connect_errors = 100
```

### Restart XAMPP MySQL
Setelah mengubah `my.ini`, restart MySQL service di XAMPP Control Panel.

## Cara Test

### 1. Test dengan File Kecil (≤1MB, ≤100 baris) 🆕
```bash
# Upload file CSV/Excel kecil untuk memastikan basic functionality
# Expected: Processing normal tanpa chunking
```

### 2. Test dengan File Sedang (1-5MB, 100-500 baris) 🆕
```bash
# Upload file sedang, monitor log untuk chunking
tail -f storage/logs/laravel.log | grep "Processing large file"
# Expected: Chunked processing dengan memory boost
```

### 3. Test dengan File Besar (>5MB, >500 baris) 🆕
```bash
# Upload file besar, monitor untuk row limit dan truncation
tail -f storage/logs/laravel.log | grep "File truncated"
# Expected: Berhasil dengan pesan truncation jika >1000 baris
```

### 4. Test dengan Database Source
```bash
# Pilih banyak karyawan dari database
# Expected: Berhasil tanpa timeout
```

## Monitoring

### 1. Cek Log Laravel
```bash
# Windows
Get-Content storage\logs\laravel.log -Wait -Tail 10

# atau buka file secara manual
storage/logs/laravel-YYYY-MM-DD.log
```

### 2. Cek MySQL Process List
```sql
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Connections';
SHOW STATUS LIKE 'Max_used_connections';
```

### 3. Cek Queue Jobs
```bash
# Lihat failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Troubleshooting Lanjutan

### Jika Masih Ada Error:

1. **Kurangi Batch Size**
   - Ubah jumlah job yang dijalankan bersamaan
   - Implementasikan chunking untuk data besar

2. **Increase Memory Limit**
   ```php
   // Di job constructor atau handle()
   ini_set('memory_limit', '512M');
   ```

3. **Database Connection Pooling**
   - Gunakan Redis/Memcached untuk cache
   - Implementasikan connection pool external

4. **Queue Driver Alternative**
   - Ganti dari 'database' ke 'redis' di `config/queue.php`
   - Instal dan konfigurasi Redis

## Status Fix
- ✅ Database configuration updated
- ✅ Job timeout & retry implemented  
- ✅ Database reconnection added
- ✅ Retry mechanism with error handling
- ✅ Large file chunking for CSV/Excel 🆕
- ✅ Memory optimization for file processing 🆕
- ✅ Batch job dispatch to prevent memory overflow 🆕
- ⏳ Monitoring & testing needed

## Update Terakhir
5 Agustus 2025 - Implementasi fix untuk MySQL timeout error + Large file processing optimization
