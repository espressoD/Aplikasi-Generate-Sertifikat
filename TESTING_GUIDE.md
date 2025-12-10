# Testing Guide - Certificate Project System

## Prerequisites

### 1. Queue Worker Setup
**CRITICAL**: Queue worker harus running untuk process jobs!

```bash
# Terminal 1 - Start Queue Worker
cd c:\xampp\htdocs\Aplikasi-Generate-Sertifikat
php artisan queue:work --tries=3 --timeout=300

# Atau gunakan batch file yang sudah ada:
start-queue-worker.bat
```

**Verifikasi queue worker running:**
- Lihat terminal output: "Processing jobs..."
- Check `jobs` table di database (should be empty when idle)
- Check `failed_jobs` table (should be empty)

### 2. Database Verification
```sql
-- Verify new tables exist
SHOW TABLES LIKE 'certificate_projects';
SHOW TABLES LIKE 'jobs';

-- Check table structure
DESCRIBE certificate_projects;
DESCRIBE certificates; -- Should have: project_id, canvas_state, pdf_generated_at

-- Sample data count
SELECT COUNT(*) FROM certificate_projects;
SELECT COUNT(*) FROM certificates WHERE project_id IS NOT NULL;
```

### 3. File Permissions
```bash
# Ensure storage directories writable
chmod -R 775 storage/app/public/certificates
chmod -R 775 storage/logs
```

---

## Test Scenarios

### 🧪 Test 1: Basic Workflow (Happy Path)

**Objective**: Test complete flow from generation to download

**Steps**:
1. **Generate Certificates**
   - Go to `/generate-bulk`
   - Load template
   - Upload participant file (Excel/CSV) OR select from database
   - Click "Generate Sertifikat Bulk"
   - ✅ **Expected**: Redirect to `/projects/{id}/edit`

2. **Verify Project Creation**
   - Check project editor loads
   - Verify all certificates appear in thumbnail sidebar
   - Check canvas renders first certificate correctly
   - ✅ **Expected**: All participant data visible on canvas

3. **Edit Certificates**
   - Click on certificate #1
   - Edit text (change font size, color, position)
   - Wait for "Saved ✓" indicator (2 seconds)
   - Navigate to certificate #2 using arrow key
   - ✅ **Expected**: Auto-save works, navigation smooth

4. **Verify Persistence**
   - Reload page (F5)
   - Check if edits from step 3 are preserved
   - ✅ **Expected**: Canvas state restored correctly

5. **Finalize Project**
   - Click "Finalize & Generate PDFs" button
   - Confirm dialog
   - ✅ **Expected**: Progress modal appears

6. **Monitor Progress**
   - Watch progress bar update every 2 seconds
   - Check percentage increases (0% → 100%)
   - ✅ **Expected**: "X / Y certificates processed" updates
   - ✅ **Expected**: Auto-redirect to projects list when completed

7. **Download ZIP**
   - Click "Download" button on completed project
   - Extract ZIP file
   - Open random PDFs
   - ✅ **Expected**: All PDFs present, edits reflected

**Success Criteria**:
- ✅ All steps complete without errors
- ✅ PDFs contain edited content (not original)
- ✅ ZIP contains all certificates (count matches)

---

### 🧪 Test 2: Large Project Performance

**Objective**: Test queue handling with 100+ certificates

**Steps**:
1. Upload Excel with 100 participants
2. Create project (should be fast, no PDFs yet)
3. Start finalization
4. Monitor queue worker terminal output
5. Check database queries:
   ```sql
   SELECT COUNT(*) FROM jobs; -- Should process sequentially
   SELECT * FROM certificate_projects WHERE id = X; -- Status should be 'finalizing'
   ```
6. Wait for completion (~5-10 minutes for 100 certs)

**Success Criteria**:
- ✅ No memory errors
- ✅ No timeout errors
- ✅ All 100 PDFs generated
- ✅ Progress tracking accurate

**Performance Benchmarks**:
- 50 certificates: ~2-3 minutes
- 100 certificates: ~5-7 minutes
- 500 certificates: ~25-30 minutes

---

### 🧪 Test 3: Edit History Tracking

**Objective**: Verify edit tracking and audit trail

**Steps**:
1. Create project with 5 certificates
2. Edit certificate #1 (change nama font size)
3. Edit certificate #2 (change warna text)
4. Check database:
   ```sql
   SELECT id, recipient_name, is_edited, edited_by, edited_at 
   FROM certificates WHERE project_id = X;
   
   SELECT edit_history FROM certificates WHERE is_edited = 1;
   ```

**Success Criteria**:
- ✅ `is_edited = 1` for edited certificates
- ✅ `edited_by` contains username
- ✅ `edited_at` timestamp accurate
- ✅ `edit_history` JSON contains change log

---

### 🧪 Test 4: Navigation & UI

**Objective**: Test all navigation paths

**Steps**:
1. **Sidebar Navigation**
   - Click "Certificate Projects" in sidebar
   - ✅ **Expected**: Redirects to `/projects`
   - ✅ **Expected**: Menu item highlighted

2. **Dashboard Widgets**
   - Go to `/dashboard`
   - Check purple "Certificate Projects" widget
   - ✅ **Expected**: Shows correct count
   - Check projects table (latest 5)
   - ✅ **Expected**: Shows project names, status, actions

3. **Project List**
   - Go to `/projects`
   - Check pagination (if > 15 projects)
   - Test status badges (Draft/Finalizing/Completed)
   - Click "Edit" on draft project
   - ✅ **Expected**: Opens editor

4. **Delete Project**
   - Click delete icon on draft project
   - Confirm deletion
   - ✅ **Expected**: AJAX delete with animation
   - ✅ **Expected**: Row removed from table
   - Check file system: PDFs and ZIP deleted

---

### 🧪 Test 5: Edge Cases

**Objective**: Test error handling and validations

**Test 5a: Edit During Finalization**
1. Start finalization on project
2. Try to edit certificate in another tab
3. ✅ **Expected**: Error "Project is being finalized"

**Test 5b: Duplicate Finalization**
1. Start finalization
2. Reload page and click finalize again
3. ✅ **Expected**: Error "Project already finalizing"

**Test 5c: Queue Worker Down**
1. Stop queue worker (Ctrl+C)
2. Start finalization
3. ✅ **Expected**: Jobs queued in database
4. Start queue worker
5. ✅ **Expected**: Jobs process automatically

**Test 5d: Browser Refresh During Finalization**
1. Start finalization
2. Refresh browser during progress
3. ✅ **Expected**: Can manually check progress via projects list
4. Status badge should show "Finalizing"

**Test 5e: Invalid Canvas State**
1. Manually corrupt canvas_state JSON in database
2. Try to finalize
3. ✅ **Expected**: Job fails gracefully, logged in `failed_jobs`

---

### 🧪 Test 6: Canvas State Rendering

**Objective**: Verify canvas → PDF accuracy

**Steps**:
1. Create project with complex template:
   - Multiple text layers
   - Images (signature blocks)
   - Custom fonts
   - Colored backgrounds

2. Edit in canvas:
   - Change text alignment (left/center/right)
   - Rotate text
   - Change opacity
   - Add/remove objects

3. Finalize and download PDF

4. Compare canvas screenshot vs PDF output

**Success Criteria**:
- ✅ Text positioning matches
- ✅ Fonts render correctly
- ✅ Colors accurate (no RGB shift)
- ✅ Images embedded properly
- ✅ Layout matches A4 landscape (1123x794px)

---

### 🧪 Test 7: Database Integrity

**Objective**: Test cascade deletes and relationships

**Steps**:
1. Create project with 10 certificates
2. Note project_id and certificate IDs
3. Delete project via UI
4. Check database:
   ```sql
   -- Certificates should be deleted (cascade)
   SELECT * FROM certificates WHERE project_id = X;
   -- Should return 0 rows
   
   -- Project should be deleted
   SELECT * FROM certificate_projects WHERE id = X;
   -- Should return 0 rows
   ```
5. Check file system:
   ```bash
   # PDFs and ZIP should be deleted
   ls storage/app/public/certificates/project_X/
   ```

**Success Criteria**:
- ✅ Certificates deleted (foreign key cascade)
- ✅ PDF files removed
- ✅ ZIP file removed
- ✅ No orphaned records

---

### 🧪 Test 8: Progress Tracking Accuracy

**Objective**: Verify progress updates in real-time

**Steps**:
1. Create project with 20 certificates
2. Start finalization
3. Open browser DevTools → Network tab
4. Monitor AJAX requests to `/projects/{id}/progress`
5. Watch cache updates:
   ```php
   // In Tinker
   php artisan tinker
   Cache::get('project_X_progress');
   ```

**Success Criteria**:
- ✅ Progress polling every 2 seconds
- ✅ Completed count increments correctly
- ✅ Percentage calculation accurate
- ✅ Current step updates ("Generating PDFs X/Y...")
- ✅ Status changes: processing → completed
- ✅ Polling stops when completed

---

## Common Issues & Fixes

### Issue 1: PDFs Not Generating
**Symptoms**: Progress stuck at 0%, no PDFs in storage

**Debug**:
```bash
# Check queue worker running
ps aux | grep "queue:work"

# Check jobs table
SELECT * FROM jobs ORDER BY id DESC LIMIT 10;

# Check failed_jobs
SELECT * FROM failed_jobs ORDER BY id DESC;

# Check logs
tail -f storage/logs/laravel.log
```

**Fix**:
1. Ensure queue worker running: `php artisan queue:work`
2. Check Browsershot installed: `composer require spatie/browsershot`
3. Verify Node.js and Puppeteer installed

---

### Issue 2: Progress Not Updating
**Symptoms**: Modal shows 0% forever

**Debug**:
```javascript
// Browser console
fetch('/projects/{id}/progress')
  .then(r => r.json())
  .then(console.log);
```

**Fix**:
1. Check route exists: `php artisan route:list | grep progress`
2. Verify cache driver: `.env` → `CACHE_DRIVER=file`
3. Clear cache: `php artisan cache:clear`

---

### Issue 3: "MySQL server has gone away"
**Symptoms**: Jobs fail with database errors

**Fix**:
1. Increase MySQL timeout in `my.ini`:
   ```
   wait_timeout = 600
   max_allowed_packet = 64M
   ```
2. Restart MySQL
3. Jobs auto-reconnect with `\DB::reconnect()`

---

### Issue 4: Canvas State Not Saving
**Symptoms**: Edits disappear after reload

**Debug**:
```sql
SELECT id, recipient_name, is_edited, 
       LENGTH(canvas_state) as canvas_size 
FROM certificates WHERE project_id = X;
```

**Fix**:
1. Check CSRF token in AJAX request
2. Verify `updateCertificate()` route exists
3. Check browser console for errors
4. Verify auto-save debounce (2 seconds)

---

## Test Checklist

Before marking PHASE 4 complete, verify:

- [ ] ✅ Queue worker runs without errors
- [ ] ✅ Project creation works (redirect to editor)
- [ ] ✅ Canvas renders all certificates correctly
- [ ] ✅ Auto-save works (2s delay)
- [ ] ✅ Navigation works (prev/next, thumbnails, arrow keys)
- [ ] ✅ Finalize button triggers jobs
- [ ] ✅ Progress modal appears and updates
- [ ] ✅ All PDFs generated correctly
- [ ] ✅ ZIP file created and downloadable
- [ ] ✅ Canvas edits reflected in final PDFs
- [ ] ✅ Edit history tracked in database
- [ ] ✅ Delete project removes files
- [ ] ✅ Dashboard widgets work
- [ ] ✅ Sidebar navigation works
- [ ] ✅ Status badges accurate
- [ ] ✅ Large projects (100+) complete successfully
- [ ] ✅ No memory leaks or timeouts
- [ ] ✅ Error handling graceful (failed jobs logged)

---

## Next Steps (PHASE 5)

After testing complete:
1. Document findings in `tasks/todo.md`
2. Fix any bugs discovered
3. Optimize performance bottlenecks
4. Update README.md with new workflow
5. Consider refactoring CSS/JS (see Refactoring Plan in todo.md)

---

**Testing Priority**: Start with Test 1 (Happy Path) to verify basic functionality, then move to edge cases.
