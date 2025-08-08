# Test Large File Processing

## File Test yang Disarankan

### 1. **File Kecil (Normal Processing)**
- **Size**: <1MB, <100 baris
- **Format**: CSV atau Excel
- **Expected Log**: 
  ```
  Processing small file (xxxxx bytes) normally
  File processed successfully. Total participants: xx
  ```

### 2. **File Sedang (Chunked Processing)**
- **Size**: 1-5MB, 100-500 baris
- **Format**: Excel (akan menggunakan memory boost)
- **Expected Log**:
  ```
  Processing large file (xxxxx bytes) with chunking
  Processing Excel file with increased limits
  File processed successfully. Total participants: xxx
  ```

### 3. **File Besar CSV (Line-by-line)**
- **Size**: >5MB atau >500 baris
- **Format**: CSV (akan menggunakan fgetcsv)
- **Expected Log**:
  ```
  Processing large file (xxxxx bytes) with chunking
  Processed 100 rows from CSV
  Processed 200 rows from CSV
  CSV processing completed. Total rows: xxx
  ```

### 4. **File Sangat Besar (Truncated)**
- **Size**: >1000 baris
- **Expected Log**:
  ```
  File truncated to 1000 rows due to size limit
  ```

## Sample Data untuk Test

### CSV Format (`test_participants.csv`):
```csv
nama,email,peran,id_peserta,divisi,nilai_1,nilai_2,nilai_3,nilai_4
John Doe,john@example.com,Peserta,P001,IT,85,90,78,92
Jane Smith,jane@example.com,Peserta,P002,HR,88,85,90,87
...
```

### Cara Generate File Test Besar:
```php
// Script PHP untuk generate CSV test dengan banyak baris
<?php
$file = fopen('test_large.csv', 'w');
fputcsv($file, ['nama', 'email', 'peran', 'id_peserta', 'divisi', 'nilai_1', 'nilai_2', 'nilai_3', 'nilai_4']);

for ($i = 1; $i <= 1500; $i++) {
    fputcsv($file, [
        "Peserta Test $i",
        "test$i@example.com", 
        "Peserta",
        "P" . str_pad($i, 4, '0', STR_PAD_LEFT),
        "Divisi " . (($i % 5) + 1),
        rand(70, 100),
        rand(70, 100), 
        rand(70, 100),
        rand(70, 100)
    ]);
}

fclose($file);
echo "Generated test_large.csv with 1500 rows\n";
?>
```

## Monitoring Commands

### 1. Monitor Log Real-time:
```bash
# Windows
Get-Content storage\logs\laravel.log -Wait -Tail 10

# PowerShell alternative
powershell -command "Get-Content 'storage\logs\laravel.log' -Wait -Tail 10"
```

### 2. Monitor Memory Usage:
```bash
# Jalankan queue worker dengan memory monitoring
php artisan queue:work --timeout=300 --memory=512 --verbose
```

### 3. Monitor Database Connections:
```sql
-- Jalankan di MySQL
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Threads_connected';
```

## Expected Results

### ✅ Success Indicators:
- No "MySQL server has gone away" errors
- Log menunjukkan chunking untuk file besar
- Memory usage stabil (<512MB)
- All jobs completed successfully
- ZIP file generated

### ❌ Warning Signs:
- Memory limit exceeded
- Too many database connections
- Jobs stuck in queue
- Failed job entries

## Test Checklist

- [ ] Test file <1MB → Normal processing
- [ ] Test file 1-5MB → Chunked processing  
- [ ] Test CSV >5MB → Line-by-line processing
- [ ] Test file >1000 rows → Truncation warning
- [ ] Monitor queue worker stability
- [ ] Check no failed jobs
- [ ] Verify ZIP generation
- [ ] Test with database source untuk perbandingan

---
**Created**: August 5, 2025  
**Purpose**: Verify large file processing fixes for MySQL timeout error
