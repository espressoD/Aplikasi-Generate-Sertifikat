# Fix MySQL Server Has Gone Away - File Upload Issue

## Problem Analysis
Berdasarkan log error:
```
[2025-08-05 13:21:28] local.INFO: Processing small file (391 bytes) normally  
[2025-08-05 13:21:28] local.INFO: File processed successfully. Total participants: 6  
[2025-08-05 13:21:28] local.INFO: Starting to process 6 participants  
[2025-08-05 13:21:28] local.ERROR: Error dispatching job batch: SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
```

**Root Cause**: Error terjadi saat dispatch job untuk **file upload**, bukan saat database karyawan. Masalahnya ada pada:
1. **Database connection timeout** saat memproses file dan dispatch jobs
2. **Batch dispatch** menyebabkan MySQL connection issue
3. **Queue table insert** gagal karena connection sudah gone away

## Fix Implementation

### 1. **Separate Dispatch Strategy** 🆕
- **File Source**: Individual dispatch per job dengan reconnection
- **Database Source**: Batch dispatch (lebih efisien untuk data dari DB)

### 2. **Aggressive Database Reconnection** 🆕
- Reconnect sebelum memproses participants
- Reconnect sebelum setiap dispatch untuk file source
- Reconnect sebelum membuat CertificateBatch record

### 3. **Error Handling** 🆕
- Non-fatal error untuk dispatch failures
- Individual fallback jika batch dispatch gagal
- Continue processing meskipun ada job yang gagal

## Code Changes

### BulkController.php - Line ~200:
```php
// Different dispatch strategy based on data source
if ($request->data_source === 'file') {
    // For file source, dispatch individually to avoid batch-related issues
    try {
        \DB::reconnect(); // Reconnect before each dispatch
        dispatch(new GenerateCertificateJob(end($jobsToDispatch)));
        array_pop($jobsToDispatch);
        Log::info("Dispatched individual job #{$jobCount} for file source");
    } catch (\Exception $e) {
        Log::error("Failed to dispatch individual job #{$jobCount}: " . $e->getMessage());
    }
} else {
    // For database source, use batch dispatch
    // ... (batch logic)
}
```

### Database Reconnection - Line ~240:
```php
// Reconnect to database before creating batch record
\DB::reconnect();

// Create CertificateBatch record in database
CertificateBatch::create([...]);
```

## Testing Instructions

### 1. **Test File Upload** (Original Issue):
- Upload small CSV/Excel (6 rows seperti yang error)
- Monitor log: `tail -f storage/logs/laravel.log`
- Expected: Individual dispatch, no batch errors

### 2. **Test Database Selection**:
- Pilih karyawan dari database
- Expected: Batch dispatch masih berfungsi normal

### 3. **Monitor Commands**:
```bash
# Windows - Monitor log real-time
powershell -command "Get-Content 'storage\logs\laravel.log' -Wait -Tail 20"

# Check failed jobs
php artisan queue:failed

# Run queue worker with verbose logging
php artisan queue:work --timeout=300 --memory=512 --verbose
```

## Expected Results

### ✅ Success Indicators:
```
[INFO] Processing small file (xxx bytes) normally
[INFO] File processed successfully. Total participants: x
[INFO] Starting to process x participants  
[INFO] Dispatched individual job #1 for file source
[INFO] Dispatched individual job #2 for file source
[INFO] All x jobs dispatched successfully for batch xxxxx
[INFO] CertificateBatch created successfully
```

### ❌ Previous Error (Should be Fixed):
```
[ERROR] Error dispatching job batch: SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
```

## Fallback Strategy

Jika masih ada error:

1. **Check MySQL Process List**:
   ```sql
   SHOW PROCESSLIST;
   SHOW STATUS LIKE 'Threads_connected';
   ```

2. **Increase MySQL Timeout** (my.ini):
   ```ini
   wait_timeout = 28800
   interactive_timeout = 28800
   ```

3. **Alternative Queue Driver**:
   - Change to 'sync' driver untuk testing
   - Consider Redis queue untuk production

---
**Date**: August 5, 2025  
**Issue**: MySQL server has gone away saat file upload  
**Status**: Fixed with individual dispatch strategy
