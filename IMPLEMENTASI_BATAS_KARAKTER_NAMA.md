# Implementasi Pembatasan Karakter Nama Karyawan

## Overview
Fitur pembatasan nama karyawan menjadi maksimal 25 karakter dengan pendekatan "Hybrid Validation".

## Spesifikasi Implementasi

### 1. Aturan Validasi (Hybrid Validation)
- ✅ **Data existing panjang** → Tetap tersimpan, tidak ada masalah
- ✅ **Edit nama apapun** → Harus ≤ 25 karakter (tanpa kecuali)  
- ✅ **Input nama baru** → Harus ≤ 25 karakter

### 2. Handling Data Existing
- Data nama yang sudah > 25 karakter tetap aman di database
- Tidak ada migration yang mengubah data existing
- User bisa edit nama panjang, tapi hasil edit harus ≤ 25 karakter

### 3. Implementasi Level

#### Frontend (resources/views/generate-bulk.blade.php)
- ✅ **maxlength="25"** pada input field
- ✅ **Counter karakter** real-time: "15/25 karakter"
- ✅ **Validasi real-time** saat user mengetik
- ✅ **Visual feedback**:
  * Border hijau + counter hijau saat valid
  * Border merah + pesan error saat invalid
  * Disable tombol simpan saat invalid

#### Backend (app/Http/Controllers/BulkController.php)
- ✅ **storeKaryawan()**: `'nama' => 'required|string|max:25'`
- ✅ **updateKaryawan()**: `'nama' => 'required|string|max:25'`

### 4. User Experience Features

#### Counter Karakter
```
Format: "X/25 karakter"
- 0 karakter: Abu-abu
- 1-25 karakter: Hijau  
- >25 karakter: Merah
```

#### Visual Feedback
```
Valid State:
- Border hijau
- Counter hijau
- Tombol "Simpan" aktif

Invalid State:
- Border merah
- Pesan error merah
- Tombol "Simpan" disabled
```

#### Pesan Error
- `"Nama tidak boleh lebih dari 25 karakter"` (saat > 25 chars)
- `"Nama tidak boleh kosong"` (saat kosong)

## Contoh Skenario

### Tambah Karyawan Baru
```
Input: "Muhammad Abdullah" (17 karakter)
✅ Result: Berhasil tersimpan

Input: "Muhammad Abdullah Rizky Pratama" (32 karakter)
❌ Result: Error - "Nama tidak boleh lebih dari 25 karakter"
```

### Edit Karyawan Existing
```
Data existing: "Muhammad Abdullah Rizky Pratama" (32 karakter)

Edit ke: "Muhammad Abdullah" (17 karakter)
✅ Result: Berhasil diupdate

Edit ke: "Muhammad Abdullah Rizky Pratama Wijaya" (38 karakter)  
❌ Result: Error - "Nama tidak boleh lebih dari 25 karakter"
```

## File yang Dimodifikasi

1. **resources/views/generate-bulk.blade.php**
   - Modal form input nama dengan counter dan validasi
   - JavaScript untuk validasi real-time
   - CSS untuk visual feedback

2. **app/Http/Controllers/BulkController.php**
   - Validation rules di `storeKaryawan()` dan `updateKaryawan()`
   - Mengubah `max:255` menjadi `max:25` untuk field nama

## Testing Checklist

- [ ] Tambah karyawan dengan nama ≤ 25 karakter → Berhasil
- [ ] Tambah karyawan dengan nama > 25 karakter → Error
- [ ] Edit nama existing (panjang) ke ≤ 25 karakter → Berhasil  
- [ ] Edit nama existing ke > 25 karakter → Error
- [ ] Counter karakter berfungsi real-time
- [ ] Visual feedback (border hijau/merah) berfungsi
- [ ] Tombol simpan disabled saat invalid
- [ ] Data existing panjang tetap utuh di database

## Tanggal Implementasi
6 Agustus 2025
