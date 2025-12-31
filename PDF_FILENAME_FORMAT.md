# PDF Filename Format Change

## 📋 Requirement

**Old Format:** `{id}_{slug-name}.pdf`
- Example: `123_john-doe.pdf`, `124_jane-smith.pdf`

**New Format:** `{counter}_{lowercase_name}.pdf`
- Example: `1_john_doe.pdf`, `69_indah_kusuma.pdf`

**Why:**
- Counter provides sequential numbering matching page_order
- Lowercase with underscore is cleaner than slug
- Easier to sort and match with CSV participant list

---

## ✅ Implementation

### Modified File
**File:** `app/Jobs/GeneratePDFFromCanvasJob.php`

### Multi-Page Path (Line 167)

**Before:**
```php
$safeName = \Str::slug($certificate->recipient_name);
$finalPdfPath = $outputDir . '/' . $certificate->id . '_' . $safeName . '.pdf';
```

**After:**
```php
// Use page_order field as counter, fallback to id if not set
$pageOrder = $certificate->page_order ?? $certificate->id;

// Lowercase name with spaces and dots replaced by underscores
$safeName = strtolower(str_replace([' ', '.'], ['_', ''], $certificate->recipient_name));

$finalPdfPath = $outputDir . '/' . $pageOrder . '_' . $safeName . '.pdf';
```

---

### Single-Page Path (Line 243)

**Before:**
```php
$safeName = \Str::slug($certificate->recipient_name);
$pdfPath = $outputDir . '/' . $certificate->id . '_' . $safeName . '.pdf';
```

**After:**
```php
// Use page_order field as counter, fallback to id if not set
$pageOrder = $certificate->page_order ?? $certificate->id;

// Lowercase name with spaces and dots replaced by underscores
$safeName = strtolower(str_replace([' ', '.'], ['_', ''], $certificate->recipient_name));

$pdfPath = $outputDir . '/' . $pageOrder . '_' . $safeName . '.pdf';
```

---

## 🔍 Technical Details

### Counter Source: `page_order` Field
- **Migration:** `2025_07_30_042701_add_batch_fields_to_certificates_table.php`
- **Field:** `page_order` (integer, nullable)
- **Population:** Set during bulk certificate generation in BulkController (line 257)
- **Purpose:** Sequential numbering for certificates in a project

**Code Reference (BulkController.php:257):**
```php
$counter = 1; // Initialize counter
foreach ($records as $record) {
    // ...
    $certificateData = [
        // ...
        'page_order' => $counter,
        // ...
    ];
    $counter++;
}
```

---

### Name Processing

**Method:** Direct string manipulation (no Str::slug())

**Why Not Slug?**
- `Str::slug()` converts to kebab-case: "John Doe" → "john-doe"
- Requirement is snake_case: "John Doe" → "john_doe"

**Implementation:**
```php
$safeName = strtolower(str_replace([' ', '.'], ['_', ''], $certificate->recipient_name));
```

**Examples:**
| Input Name | Slug (Old) | New Format |
|------------|------------|------------|
| John Doe | john-doe | john_doe |
| Jane Smith | jane-smith | jane_smith |
| Dr. Ahmad | dr-ahmad | dr_ahmad |
| Indah Kusuma | indah-kusuma | indah_kusuma |
| Achmad S.Pd | achmad-spd | achmad_spd |

**Special Characters Handled:**
- Spaces → `_`
- Dots → `_` (e.g., "S.Pd" → "s_pd")
- Already lowercase

---

## 🧪 Testing

### Test Cases

**Test 1: Basic Names**
```
Input: "John Doe"
page_order: 1
Expected: 1_john_doe.pdf
```

**Test 2: Name with Title**
```
Input: "Dr. Ahmad"
page_order: 25
Expected: 25_dr_ahmad.pdf
```

**Test 3: Name with Degree**
```
Input: "Achmad S.Pd"
page_order: 69
Expected: 69_achmad_spd.pdf
```

**Test 4: Multi-word Names**
```
Input: "Indah Kusuma Dewi"
page_order: 100
Expected: 100_indah_kusuma_dewi.pdf
```

---

### Verification Steps

1. **Generate Project:**
   - Upload CSV with 100 participants
   - Create project
   - Wait for generation to complete

2. **Check Generated ZIP:**
   - Download ZIP from project page
   - Extract and verify filenames:
     ```
     1_john_doe.pdf
     2_jane_smith.pdf
     3_dr_ahmad.pdf
     ...
     100_indah_kusuma_dewi.pdf
     ```

3. **Verify Sequential Order:**
   ```bash
   # PowerShell
   Get-ChildItem *.pdf | Sort-Object Name | Select-Object -First 10 Name
   
   # Expected:
   # 1_john_doe.pdf
   # 2_jane_smith.pdf
   # 3_dr_ahmad.pdf
   # ...
   # 10_participant_name.pdf
   ```

4. **Check CSV Match:**
   - Open original CSV
   - Verify participant at row 69 matches `69_participant_name.pdf`

---

## 📊 Impact Analysis

### Files Modified
✅ `app/Jobs/GeneratePDFFromCanvasJob.php` (2 locations)

### Database Schema
✅ No changes required (`page_order` field already exists)

### Frontend/UI
✅ No changes required (filename only visible in ZIP download)

### Backward Compatibility
⚠️ **Note:** Old projects with existing PDFs will keep old filename format. New generations will use new format.

**Migration Option (if needed):**
```php
// In BulkController or new command
$certificates = Certificate::whereNotNull('page_order')->get();
foreach ($certificates as $cert) {
    $oldPath = storage_path("app/certificates/{$cert->id}_" . \Str::slug($cert->recipient_name) . ".pdf");
    $newPath = storage_path("app/certificates/{$cert->page_order}_" . strtolower(str_replace([' ', '.'], ['_', ''], $cert->recipient_name)) . ".pdf");
    
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
}
```

---

## 🎯 Benefits

1. **Sequential Numbering:** Files sort naturally (1, 2, 3... vs 1, 10, 100, 2, 20...)
2. **CSV Alignment:** Filename counter matches CSV row order
3. **Cleaner Names:** snake_case is more readable than kebab-case for filenames
4. **No Ambiguity:** Direct mapping between page_order and filename

---

## 🔄 Rollback (If Needed)

**Revert to Old Format:**
```php
// GeneratePDFFromCanvasJob.php (both locations)
$safeName = \Str::slug($certificate->recipient_name);
$finalPdfPath = $outputDir . '/' . $certificate->id . '_' . $safeName . '.pdf'; // Line 167
$pdfPath = $outputDir . '/' . $certificate->id . '_' . $safeName . '.pdf'; // Line 243
```

**Git Command:**
```bash
git diff app/Jobs/GeneratePDFFromCanvasJob.php
git checkout HEAD -- app/Jobs/GeneratePDFFromCanvasJob.php
```

---

## 📝 Related Documentation

- **Counter Implementation:** [BulkController.php](app/Http/Controllers/BulkController.php) (line 257)
- **Migration:** [2025_07_30_042701_add_batch_fields_to_certificates_table.php](database/migrations/2025_07_30_042701_add_batch_fields_to_certificates_table.php)
- **Job Class:** [GeneratePDFFromCanvasJob.php](app/Jobs/GeneratePDFFromCanvasJob.php)

---

**Last Updated:** 2025-12-14  
**Status:** ✅ Implementation Complete  
**Priority:** MEDIUM - Enhancement, not a bug fix
