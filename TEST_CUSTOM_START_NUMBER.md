# Test Certificate Numbering dengan Custom Start Number

## 🧪 Test Cases untuk Validasi Fitur

### Test Case 1: Custom Start Number Basic
**Input:** `CERT-{AUTO:100}-2025`
**Expected:** CERT-100-2025, CERT-101-2025, CERT-102-2025
**Description:** Basic custom start number dengan padding 3 digit

### Test Case 2: Custom Start Number dengan Large Number
**Input:** `PKL-{AUTO:1000}`
**Expected:** PKL-1000, PKL-1001, PKL-1002
**Description:** Large start number dengan smart padding (4 digit)

### Test Case 3: Custom Start Number di Awal
**Input:** `{AUTO:500}/TRAINING/2025`
**Expected:** 500/TRAINING/2025, 501/TRAINING/2025, 502/TRAINING/2025
**Description:** Auto-increment di awal format

### Test Case 4: Custom Start Number dengan Padding Preservation
**Input:** `BATCH-{AUTO:050}-FINAL`
**Expected:** BATCH-050-FINAL, BATCH-051-FINAL, BATCH-052-FINAL
**Description:** Preserve leading zeros dari start number

### Test Case 5: Default AUTO (Backward Compatibility)
**Input:** `CERT-{AUTO}-2025`
**Expected:** CERT-001-2025, CERT-002-2025, CERT-003-2025
**Description:** Default behavior tanpa custom start

### Test Case 6: Legacy Format (Backward Compatibility)
**Input:** `TRAINING-100`
**Expected:** TRAINING-100, TRAINING-101, TRAINING-102
**Description:** Legacy format masih berfungsi

### Test Case 7: Complex Format dengan Multiple Elements
**Input:** `2025-Q1-{AUTO:25}-WORKSHOP`
**Expected:** 2025-Q1-025-WORKSHOP, 2025-Q1-026-WORKSHOP
**Description:** Complex format dengan multiple separators

---

## 🔬 Manual Testing Steps

### Step 1: Test Preview Functionality
1. Buka halaman Generate Sertifikat
2. Input test cases di field "Nomor Sertifikat"
3. Verify preview update real-time
4. Check padding dan format sesuai expected

### Step 2: Test Actual Generation
1. Setup template simple
2. Upload file CSV dengan 3-5 participants
3. Test masing-masing format dari test cases
4. Verify PDF generated dengan numbering correct

### Step 3: Test Edge Cases
```
Empty Input: "" → Should show validation error
Invalid Format: "CERT-{AUTO:abc}" → Should fallback to default
Large Number: "{AUTO:999999}" → Should work with appropriate padding
Special Characters: "CERT_{AUTO:100}_2025" → Should work with underscore
```

### Step 4: Test JavaScript Preview
```javascript
// Test cases untuk JavaScript function
const testCases = [
    { input: "CERT-{AUTO:100}-2025", expected: "CERT-100-2025, CERT-101-2025, CERT-102-2025" },
    { input: "PKL-{AUTO:50}", expected: "PKL-050, PKL-051, PKL-052" },
    { input: "{AUTO:1000}", expected: "1000, 1001, 1002" },
    { input: "CERT-{AUTO}", expected: "CERT-001, CERT-002, CERT-003" }
];
```

---

## ✅ Expected Results

### Controller Logic Validation
```php
// Test prepareParticipantData method
$testData = [
    ['prefix' => 'CERT-{AUTO:100}-2025', 'counter' => 1, 'expected' => 'CERT-100-2025'],
    ['prefix' => 'CERT-{AUTO:100}-2025', 'counter' => 2, 'expected' => 'CERT-101-2025'],
    ['prefix' => 'PKL-{AUTO:50}', 'counter' => 1, 'expected' => 'PKL-050'],
    ['prefix' => '{AUTO:1000}', 'counter' => 1, 'expected' => '1000'],
    ['prefix' => 'CERT-{AUTO}', 'counter' => 1, 'expected' => 'CERT-001']
];
```

### Frontend Preview Validation
- ✅ Preview updates dengan 300ms debounce
- ✅ Detection pattern {AUTO:number} priority tertinggi
- ✅ Fallback ke {AUTO} jika tidak ada custom start
- ✅ Legacy format tetap supported
- ✅ Error handling untuk invalid input

---

## 🐛 Potential Issues & Solutions

### Issue 1: Regex Pattern Conflict
**Problem:** Multiple {AUTO} dalam satu string
**Solution:** Gunakan preg_match dengan priority order

### Issue 2: Large Number Performance
**Problem:** Start number sangat besar (>100000)
**Solution:** Add validation atau warning untuk large numbers

### Issue 3: Padding Inconsistency
**Problem:** Padding tidak konsisten antara preview dan actual
**Solution:** Ensure same logic di JavaScript dan PHP

### Issue 4: Special Characters in Format
**Problem:** Format dengan special characters tidak ter-handle
**Solution:** Add regex escaping untuk special characters

---

## 📊 Performance Benchmarks

### Expected Performance Metrics
```
Small Batch (1-50 certificates):
- Custom Start: <100ms processing time
- Default AUTO: <50ms processing time

Medium Batch (51-200 certificates):
- Custom Start: <500ms processing time
- Default AUTO: <300ms processing time

Large Batch (201-500 certificates):
- Custom Start: <2000ms processing time
- Default AUTO: <1500ms processing time
```

### Memory Usage Expectations
```
Start Number Range | Memory Impact | Recommendation
1-999             | Minimal       | No optimization needed
1000-9999         | Low          | Monitor for large batches
10000+            | Medium       | Consider chunking
```

---

## 🎯 Success Criteria

### ✅ Functionality Requirements
- [x] Custom start number dengan format {AUTO:number}
- [x] Smart padding berdasarkan start number length
- [x] Backward compatibility dengan format lama
- [x] Real-time preview dengan correct detection
- [x] Error handling dan fallback mechanism

### ✅ User Experience Requirements
- [x] Intuitive interface dengan clear examples
- [x] Live preview untuk immediate feedback
- [x] Helpful tooltip dan documentation
- [x] Consistent behavior across all formats

### ✅ Technical Requirements
- [x] No breaking changes untuk existing workflows
- [x] Performance optimization untuk large batches
- [x] Proper validation dan error handling
- [x] Code maintainability dan readability

---

## 🚀 Next Steps After Testing

1. **Production Deployment**
   - Deploy ke staging environment
   - User acceptance testing
   - Production release dengan feature flag

2. **Documentation Update**
   - Update user manual
   - Create video tutorial
   - Update API documentation

3. **Monitoring & Analytics**
   - Track usage patterns
   - Monitor performance metrics
   - Collect user feedback

4. **Future Enhancements**
   - Advanced formatting options
   - Bulk number reservation
   - Integration dengan external systems
