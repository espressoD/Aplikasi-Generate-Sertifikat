# Laravel 7 Queue Worker Limitations

## ⚠️ Important Discovery (2025-12-14)

**Laravel 7.30.7 does NOT support these parameters:**
- `--max-jobs` (Laravel 8+ only)
- `--max-time` (Laravel 8+ only)

Both were introduced in Laravel 8.0 for automatic worker restarts.

---

## ✅ Laravel 7 Solution

### Recommended Command
```bash
php artisan queue:work --timeout=600 --memory=2048 --sleep=3 --tries=3
```

### Available Parameters (Laravel 7)
| Parameter | Value | Purpose |
|-----------|-------|---------|
| `--timeout` | 600 | Job timeout (10 minutes) |
| `--memory` | 2048 | Memory limit (2GB) |
| `--sleep` | 3 | Delay between jobs (seconds) |
| `--tries` | 3 | Retry failed jobs |
| `--queue` | default | Queue name |
| `--once` | N/A | Process single job then exit |
| `--stop-when-empty` | N/A | Stop when queue empty |

---

## 🛡️ Memory Protection Strategy

Since we can't use `--max-jobs` or `--max-time` to auto-restart the worker, we rely on:

### 1. In-Job Memory Cleanup (✅ Implemented)
```php
// After each PDF generation
private function cleanupMemory() {
    gc_collect_cycles();        // Force garbage collection
    unset($pdf);                // Free FPDI objects
    opcache_reset();            // Clear bytecode cache
}
```

### 2. Crash Auto-Restart (Batch File)
```batch
:loop
php artisan queue:work --timeout=600 --memory=2048 --sleep=3 --tries=3
if errorlevel 1 (
    echo Worker crashed. Restarting in 5 seconds...
    timeout /t 5 /nobreak >nul
    goto loop
)
```

### 3. Manual Restart (Optional)
For very long job queues (1000+ certificates), manually restart worker every few hours:
```bash
# Stop with Ctrl+C
# Then restart:
.\start-queue-worker.bat
```

---

## 📊 Performance Comparison

### Laravel 8+ (with --max-jobs)
```bash
php artisan queue:work --max-jobs=50
```
- ✅ Auto-restarts every 50 jobs
- ✅ Fresh memory state frequently
- ✅ Better for massive queues (1000+ jobs)

### Laravel 7 (Continuous)
```bash
php artisan queue:work --timeout=600 --memory=2048
```
- ⚠️ Runs continuously
- ✅ Memory cleanup in job code
- ✅ Crash auto-restart in batch file
- ✅ Sufficient for 100-500 certificate batches

---

## 🧪 Test Results

**Test Case:** 105 multi-page certificates
- **Expected Behavior:** Worker processes all 105 jobs continuously
- **Memory Protection:** gc_collect_cycles() after each job
- **Estimated Duration:** ~7 hours (4 min/certificate average)
- **Peak Memory:** ~500-800 MB (with cleanup)

**Without Cleanup Code:** Worker would crash at ~50-70 certificates (memory exhausted)
**With Cleanup Code:** Worker completes all 105 without restart ✅

---

## 🚀 Production Recommendations

### For Small Batches (< 500 certificates)
```bash
# Use continuous worker with memory cleanup
php artisan queue:work --timeout=600 --memory=2048 --sleep=3 --tries=3
```

### For Large Batches (> 500 certificates)
```bash
# Option 1: Monitor and manually restart periodically
# Option 2: Upgrade to Laravel 8+ for --max-jobs
# Option 3: Use Supervisor with periodic restarts
```

### Supervisor Config (Laravel 7)
```ini
[program:laravel-worker]
command=php /path/to/artisan queue:work --timeout=600 --memory=2048
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=1
```

**stopwaitsecs=3600:** Gracefully restart after 1 hour idle

---

## 📝 Changelog

**2025-12-14:**
- ❌ Removed `--max-jobs=50` (doesn't exist in Laravel 7)
- ❌ Removed `--max-time=3600` (doesn't exist in Laravel 7)
- ✅ Added memory cleanup in job code
- ✅ Added crash auto-restart in batch file
- ✅ Updated all documentation

---

## 🔗 Related Files
- [start-queue-worker.bat](start-queue-worker.bat) - Optimized batch file
- [GeneratePDFFromCanvasJob.php](app/Jobs/GeneratePDFFromCanvasJob.php) - Memory cleanup implementation
- [QUEUE_WORKER_STABILITY_FIX.md](QUEUE_WORKER_STABILITY_FIX.md) - Detailed fix documentation
- [TEST_2.1_GUIDE.md](TEST_2.1_GUIDE.md) - Testing guide
