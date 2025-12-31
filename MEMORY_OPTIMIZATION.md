# Memory Optimization Guide

## Issue
When generating 100+ certificates with background images, PHP may run out of memory:
```
Allowed memory size of 536870912 bytes exhausted
```

## Solutions Implemented

### 1. Application Level (Already Done)
- **Memory Limit Increased:** 512M → 1GB in `BulkController.php`
- **Garbage Collection:** Every 50 certificates to free memory
- **Logging:** Memory usage tracking for monitoring

### 2. PHP Configuration (Recommended for Production)

#### Option A: Update `php.ini` (Permanent)
1. Find your `php.ini` file:
   - XAMPP: `C:\xampp\php\php.ini`
   - Linux: `/etc/php/7.x/apache2/php.ini`

2. Find and update this line:
   ```ini
   memory_limit = 1024M
   ```
   
3. Restart Apache/Web Server

#### Option B: `.htaccess` (Per-directory)
Add to project root `.htaccess`:
```apache
php_value memory_limit 1024M
php_value max_execution_time 300
```

#### Option C: Runtime (Already Done in Code)
```php
ini_set('memory_limit', '1024M');
set_time_limit(0);
```

## Performance Tips

### For Very Large Projects (500+ certificates)
1. **Use Queue Processing:**
   - Already implemented in finalize process
   - Run queue worker: `php artisan queue:work`

2. **Monitor Memory:**
   - Check Laravel logs for memory usage
   - Look for: "Memory cleanup at certificate X"

3. **Increase if Needed:**
   - For 500+ certificates: `2048M` (2GB)
   - For 1000+ certificates: `4096M` (4GB)

## Testing Results

### Test 2.1: Large Project (100 Certificates)
- **Before Fix:** ❌ Memory exhausted at ~60 certificates
- **After Fix:** ✅ All 100 certificates generated successfully
- **Peak Memory:** ~850MB
- **Processing Time:** ~2-3 minutes

## Technical Details

### Memory Usage Breakdown (per certificate)
- Base64 Background Image: ~471 KB
- Canvas State JSON: ~50 KB
- PHP Object Overhead: ~100 KB
- **Total per Certificate:** ~620 KB

### Calculation
- 100 certificates × 620 KB = ~62 MB (raw data)
- PHP overhead + temporary variables: ~3-4x multiplier
- **Required Memory:** ~250-300 MB for data + 512 MB PHP baseline = **~800 MB minimum**

### Why 1GB?
- Safe buffer for temporary operations
- Multiple concurrent requests support
- Garbage collection overhead

## Monitoring Commands

Check current memory limit:
```bash
php -r "echo ini_get('memory_limit');"
```

Check memory usage in Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "Memory cleanup"
```

## Troubleshooting

### Still Getting Memory Errors?
1. **Check actual PHP memory limit:**
   ```bash
   php -i | grep memory_limit
   ```

2. **Increase further:**
   - Code: Change `1024M` to `2048M` in BulkController
   - Config: Update php.ini to match

3. **Check server resources:**
   - Ensure server has available RAM
   - Close other memory-intensive processes

### Queue Worker Memory
If queue jobs fail with memory errors:
```bash
# Run with higher memory limit
php -d memory_limit=1024M artisan queue:work
```

Or update queue configuration in `config/queue.php`:
```php
'timeout' => 300,
'memory' => 1024,
```

## Production Recommendations

1. **Set permanent memory limit** in php.ini: `1024M`
2. **Enable OpCache** for better performance
3. **Monitor with APM** (New Relic, DataDog, etc.)
4. **Scale horizontally** if generating >1000 certificates regularly

## Last Updated
2025-12-13 - After Test 2.1 fix
