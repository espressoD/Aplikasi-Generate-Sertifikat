# Advanced Certificate Numbering System dengan Custom Start Number

## 🎯 Overview

Sistem penomoran sertifikat yang sangat fleksibel memungkinkan Anda:
1. Menentukan posisi auto-increment di mana saja menggunakan `{AUTO}`
2. Menentukan starting number dengan format `{AUTO:start_number}`
3. Kontrol penuh atas format dan sequence numbering

---

## 🔢 Format Numbering

### 1. **Format dengan Custom Start Number (RECOMMENDED)**

Gunakan `{AUTO:start_number}` untuk kontrol penuh:

| **Input Format** | **Hasil Generate** | **Keterangan** |
|------------------|-------------------|----------------|
| `CERT-{AUTO:100}-2025` | CERT-100-2025, CERT-101-2025, CERT-102-2025 | Mulai dari 100 |
| `2025-{AUTO:50}-TRAINING` | 2025-050-TRAINING, 2025-051-TRAINING | Mulai dari 50 dengan padding 3 digit |
| `PKL-{AUTO:1000}` | PKL-1000, PKL-1001, PKL-1002 | Mulai dari 1000 (padding 4 digit) |
| `{AUTO:5}/2025/CERT` | 005/2025/CERT, 006/2025/CERT | Mulai dari 5 di awal |
| `BATCH-{AUTO:999}-FINAL` | BATCH-999-FINAL, BATCH-1000-FINAL | Smart padding adjustment |

### 2. **Format dengan Default Start (Mulai dari 1)**

Gunakan `{AUTO}` untuk mulai dari 001:

| **Input Format** | **Hasil Generate** | **Keterangan** |
|------------------|-------------------|----------------|
| `CERT-{AUTO}-2025` | CERT-001-2025, CERT-002-2025, CERT-003-2025 | Auto-increment di tengah |
| `2025-{AUTO}-TRAINING` | 2025-001-TRAINING, 2025-002-TRAINING | Auto-increment di tengah dengan suffix |
| `PKL-{AUTO}` | PKL-001, PKL-002, PKL-003 | Auto-increment di akhir |
| `{AUTO}/2025/CERT` | 001/2025/CERT, 002/2025/CERT | Auto-increment di awal |

### 3. **Format Legacy (Masih Didukung)**

Sistem lama masih berfungsi untuk backward compatibility:

| **Input Format** | **Hasil Generate** | **Keterangan** |
|------------------|-------------------|----------------|
| `CERT-2025-001` | CERT-2025-001, CERT-2025-002, CERT-2025-003 | Deteksi angka di akhir |
| `PKL-01` | PKL-01, PKL-02, PKL-03 | Padding otomatis sesuai digit |
| `TRAINING-100` | TRAINING-100, TRAINING-101, TRAINING-102 | Start dari angka yang ditentukan |

---

## 🎓 Smart Padding System

### Automatic Padding Logic

Sistem secara otomatis menentukan padding berdasarkan:

1. **Custom Start Number:** Minimal sesuai digit start number atau 3 digit
   - `{AUTO:50}` → 050, 051, 052 (3 digit padding)
   - `{AUTO:100}` → 100, 101, 102 (3 digit padding)
   - `{AUTO:1000}` → 1000, 1001, 1002 (4 digit padding)

2. **Default AUTO:** Selalu 3 digit padding
   - `{AUTO}` → 001, 002, 003

3. **Legacy Format:** Sesuai format existing
   - `CERT-001` → CERT-001, CERT-002 (3 digit)
   - `PKL-01` → PKL-01, PKL-02 (2 digit)

---

## 💡 Live Preview Enhancement

Sistem memberikan preview real-time dengan detection pattern:

```
Input: CERT-{AUTO:100}-2025
Preview: CERT-100-2025, CERT-101-2025, CERT-102-2025, ...

Input: PKL-{AUTO:50}
Preview: PKL-050, PKL-051, PKL-052, ...

Input: {AUTO:1000}
Preview: 1000, 1001, 1002, ...

Input: 2025-{AUTO}
Preview: 2025-001, 2025-002, 2025-003, ...
```

---

## 🔧 Technical Implementation

### Enhanced Controller Logic (BulkController.php)

```php
// Generate certificate number with flexible auto-increment position and custom start number
$certificateNumber = '';
if ($certificateCounter !== null && $request->has('certificate_number_prefix')) {
    $prefix = $request->certificate_number_prefix;
    
    // Check if prefix contains {AUTO:start_number} placeholder for custom start number
    if (preg_match('/\{AUTO:(\d+)\}/', $prefix, $matches)) {
        $startNumber = intval($matches[1]);
        $currentNumber = $startNumber + ($certificateCounter - 1);
        
        // Determine padding based on start number length or minimum 3 digits
        $padding = max(3, strlen($matches[1]));
        $autoNumber = str_pad($currentNumber, $padding, '0', STR_PAD_LEFT);
        
        $certificateNumber = str_replace($matches[0], $autoNumber, $prefix);
    }
    // Check if prefix contains {AUTO} placeholder for flexible positioning (default start from 1)
    else if (strpos($prefix, '{AUTO}') !== false) {
        // Replace {AUTO} with auto-incremented number starting from 1
        $autoNumber = str_pad($certificateCounter, 3, '0', STR_PAD_LEFT);
        $certificateNumber = str_replace('{AUTO}', $autoNumber, $prefix);
    } 
    // Legacy support: Extract base number from prefix if it contains numbers at the end
    else if (preg_match('/^(.+?)(\d+)$/', $prefix, $matches)) {
        $basePrefix = $matches[1];
        $startNumber = intval($matches[2]);
        $newNumber = $startNumber + ($certificateCounter - 1);
        $certificateNumber = $basePrefix . str_pad($newNumber, strlen($matches[2]), '0', STR_PAD_LEFT);
    } else {
        // If no number pattern, just append counter
        $certificateNumber = $prefix . '-' . str_pad($certificateCounter, 3, '0', STR_PAD_LEFT);
    }
}
```

### Enhanced Frontend Preview (JavaScript)

```javascript
function updatePreview() {
    const prefix = prefixInput.value.trim();
    if (!prefix) return;
    
    let preview = '';
    
    // Check for {AUTO:start_number} format
    const customStartMatch = prefix.match(/\{AUTO:(\d+)\}/);
    if (customStartMatch) {
        const startNum = parseInt(customStartMatch[1]);
        const padding = Math.max(3, customStartMatch[1].length);
        
        const example1 = prefix.replace(customStartMatch[0], String(startNum).padStart(padding, '0'));
        const example2 = prefix.replace(customStartMatch[0], String(startNum + 1).padStart(padding, '0'));
        const example3 = prefix.replace(customStartMatch[0], String(startNum + 2).padStart(padding, '0'));
        preview = `Preview: ${example1}, ${example2}, ${example3}, ...`;
    }
    // ... other logic for {AUTO} and legacy formats
}
```

---

## 📋 Use Cases & Examples

### 1. **Sequential Departmental Training**
```
Format: IT-{AUTO:100}-2025
Hasil: IT-100-2025, IT-101-2025, IT-102-2025
Use Case: IT Department dengan numbering terpisah mulai dari 100
```

### 2. **Quarterly Training Batches**
```
Format: Q1-{AUTO:50}-TRAINING
Hasil: Q1-050-TRAINING, Q1-051-TRAINING
Use Case: Quarter-based dengan sequence number mulai dari 50
```

### 3. **High Volume Certificate Series**
```
Format: CERT-{AUTO:1000}
Hasil: CERT-1000, CERT-1001, CERT-1002
Use Case: Large organization dengan high volume numbering
```

### 4. **Year-Based Professional Certification**
```
Format: {AUTO:2025001}/PROF
Hasil: 2025001/PROF, 2025002/PROF
Use Case: Professional certification dengan year prefix
```

### 5. **Multi-Division PKL Series**
```
Format: PKL-{AUTO:500}-DIV
Hasil: PKL-500-DIV, PKL-501-DIV
Use Case: PKL dengan dedicated range per division
```

### 6. **Workshop Continuation Series**
```
Format: WS-JS-{AUTO:25}
Hasil: WS-JS-025, WS-JS-026
Use Case: Melanjutkan workshop series dari batch sebelumnya
```

---

## ⚡ Advanced Features

### ✅ **Custom Start Number Control**
- Format: `{AUTO:start_number}` untuk menentukan starting point
- Smart padding berdasarkan digit start number
- Fleksibilitas penuh dalam sequence management

### ✅ **Flexible Positioning**
- `{AUTO}` atau `{AUTO:start}` dapat ditempatkan di awal, tengah, atau akhir
- Mendukung multiple separators (dash, slash, underscore, etc.)
- Compatible dengan format perusahaan existing

### ✅ **Smart Padding System**
- Auto-detect padding requirements
- Minimal 3 digit untuk consistency
- Dynamic adjustment berdasarkan start number

### ✅ **Real-time Preview dengan Enhanced Detection**
- Preview update saat mengetik dengan 300ms debounce
- Detection untuk semua format (custom start, default, legacy)
- Menampilkan 3 contoh pertama dengan actual formatting

### ✅ **Backward Compatibility**
- Format lama tetap didukung penuh
- Migration seamless dari sistem existing
- No breaking changes untuk workflow lama

### ✅ **Error Handling & Validation**
- Fallback ke format default jika pattern tidak valid
- Graceful degradation untuk edge cases
- Input validation untuk start number

---

## 🚀 Migration Guide dari Format Lama

### Upgrade Path untuk Existing Users

| **Format Lama** | **Format Baru Recommended** | **Benefit** |
|------------------|------------------------------|-------------|
| `CERT-2025-001` | `CERT-2025-{AUTO:1}` | Kontrol eksplisit start number |
| `PKL-01` | `PKL-{AUTO:1}` | Konsisten 3-digit padding |
| `TRAINING-100` | `TRAINING-{AUTO:100}` | Custom start dengan flexibility |
| `WS-050` | `WS-{AUTO:50}` | Continue dari batch sebelumnya |

### Scenario Migration Examples

#### **Scenario 1: Continuing from Previous Batch**
```
Previous batch ended at: CERT-2025-047
New batch format: CERT-2025-{AUTO:48}
Result: CERT-2025-048, CERT-2025-049, CERT-2025-050, ...
```

#### **Scenario 2: Department-Specific Ranges**
```
IT Department: IT-{AUTO:1000} → IT-1000, IT-1001, IT-1002
HR Department: HR-{AUTO:2000} → HR-2000, HR-2001, HR-2002
Finance: FIN-{AUTO:3000} → FIN-3000, FIN-3001, FIN-3002
```

#### **Scenario 3: Quarterly Batches**
```
Q1: Q1-{AUTO:100} → Q1-100, Q1-101, Q1-102
Q2: Q2-{AUTO:200} → Q2-200, Q2-201, Q2-202  
Q3: Q3-{AUTO:300} → Q3-300, Q3-301, Q3-302
Q4: Q4-{AUTO:400} → Q4-400, Q4-401, Q4-402
```

---

## 🔍 Advanced Troubleshooting

### Common Issues & Solutions

#### Issue: Preview tidak update dengan custom start number
**Solution:** 
```javascript
// Pastikan regex pattern correct untuk detection
const customStartMatch = prefix.match(/\{AUTO:(\d+)\}/);
// Check browser console untuk error JavaScript
```

#### Issue: Padding tidak sesuai expected
**Solution:** 
```php
// Controller logic menggunakan max() untuk determine padding
$padding = max(3, strlen($matches[1]));
// Start number 50 = padding 3, start number 1000 = padding 4
```

#### Issue: Start number tidak ter-apply
**Solution:** 
```php
// Verify regex pattern detection priority:
// 1. {AUTO:number} (highest priority)
// 2. {AUTO} (default)
// 3. Legacy format (lowest priority)
```

#### Issue: Large start numbers causing performance
**Solution:** 
```
Recommended ranges:
- Small batches: 1-999
- Medium batches: 1000-9999  
- Large organizations: 10000-99999
- Enterprise: Custom solution
```

### Performance Considerations

#### **Batch Size Recommendations**
```
Start Number Range | Recommended Batch Size | Performance Impact
1-999             | Up to 500 certificates | Minimal
1000-9999         | Up to 1000 certificates| Low  
10000-99999       | Up to 2000 certificates| Medium
100000+           | Custom chunking needed | High - needs optimization
```

#### **Memory Optimization**
```php
// For large start numbers, consider chunking
if ($startNumber > 10000 && $jobCount > 1000) {
    // Implement batch chunking
    $this->dispatchJobBatch($jobsToDispatch);
}
```

---

## � Format Comparison Matrix

| **Feature** | **{AUTO:start}** | **{AUTO}** | **Legacy** | **Fallback** |
|-------------|------------------|------------|------------|--------------|
| Custom Start Number | ✅ Yes | ❌ No (starts from 1) | ✅ Yes | ❌ No |
| Flexible Position | ✅ Anywhere | ✅ Anywhere | ❌ End only | ❌ End only |
| Smart Padding | ✅ Dynamic | ✅ 3-digit | ✅ Original | ✅ 3-digit |
| Live Preview | ✅ Full | ✅ Full | ✅ Basic | ✅ Basic |
| Future-Proof | ✅ Yes | ✅ Yes | ⚠️ Limited | ⚠️ Limited |
| Recommended | ✅ **BEST** | ✅ Good | ⚠️ Legacy only | ❌ Last resort |

---

## 💡 Pro Tips & Best Practices

### 🎯 **Planning Certificate Number Schemes**

1. **Department Separation**
   ```
   IT: IT-{AUTO:1000}    → IT-1000, IT-1001, IT-1002
   HR: HR-{AUTO:2000}    → HR-2000, HR-2001, HR-2002
   FIN: FIN-{AUTO:3000}  → FIN-3000, FIN-3001, FIN-3002
   ```

2. **Year-based Organization**
   ```
   2025: {AUTO:2025001}  → 2025001, 2025002, 2025003
   2026: {AUTO:2026001}  → 2026001, 2026002, 2026003
   ```

3. **Training Type Classification**
   ```
   Internal: INT-{AUTO:100}     → INT-100, INT-101
   External: EXT-{AUTO:200}     → EXT-200, EXT-201  
   Online: ONL-{AUTO:300}       → ONL-300, ONL-301
   ```

### 🔧 **Implementation Strategy**

1. **Start Small**: Test dengan format sederhana dulu
2. **Document Standards**: Buat guideline format untuk tim
3. **Plan Ranges**: Reserve number ranges untuk different purposes
4. **Monitor Usage**: Track numbering untuk avoid conflicts
5. **Backup Strategy**: Keep record of last used numbers

### 📈 **Scalability Planning**

```
Organization Size | Recommended Format | Example
Small (1-100)     | CERT-{AUTO:1}     | CERT-001 to CERT-100
Medium (100-1K)   | CERT-{AUTO:100}   | CERT-100 to CERT-1100  
Large (1K-10K)    | CERT-{AUTO:1000}  | CERT-1000 to CERT-11000
Enterprise (10K+) | {AUTO:100000}     | 100000 to 999999
```

---

## 🔗 Integration Examples

### API Usage Example
```php
// POST request untuk generate certificates
{
    "certificate_number_prefix": "TRAINING-{AUTO:500}",
    "event_name": "Advanced Laravel Training",
    "participants": [...],
    // Other parameters...
}

// Response akan generate:
// TRAINING-500, TRAINING-501, TRAINING-502, etc.
```

### Database Integration
```sql
-- Query untuk track last used numbers per format
SELECT 
    certificate_number_prefix,
    MAX(CAST(SUBSTRING(certificate_number, -3) AS UNSIGNED)) as last_number
FROM certificates 
WHERE certificate_number_prefix LIKE 'TRAINING-{AUTO:%}'
GROUP BY certificate_number_prefix;
```

---

## 📚 Additional Resources

- [Main Documentation](README.md)
- [Template System Guide](TEMPLATE_GUIDE.md)
- [API Reference](API_REFERENCE.md)

---

**💡 Pro Tips:**
- Gunakan format yang konsisten across organisasi
- Test preview sebelum generate batch besar
- Dokumentasikan format numbering untuk tim
- Consider future scalability saat design format
