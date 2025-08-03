# PLACEHOLDER NILAI BARU - DOKUMENTASI

## 4 Placeholder Nilai Baru yang Ditambahkan:

1. `@{{nilai_1}}` - Akan diisi dari kolom `nilai_1` di Excel/CSV
2. `@{{nilai_2}}` - Akan diisi dari kolom `nilai_2` di Excel/CSV  
3. `@{{nilai_3}}` - Akan diisi dari kolom `nilai_3` di Excel/CSV
4. `@{{nilai_4}}` - Akan diisi dari kolom `nilai_4` di Excel/CSV

## Struktur File Excel/CSV yang Diperbarui:

### Kolom Wajib:
- `nama` - Nama peserta
- `email` - Email peserta  
- `peran` - Peran peserta (Peserta/Panitia/dll)
- `id_peserta` - ID unik peserta
- `divisi` - Divisi peserta

### Kolom Opsional Nilai (BARU):
- `nilai_1` - Nilai pertama (misal: nilai tugas)
- `nilai_2` - Nilai kedua (misal: nilai kuis)
- `nilai_3` - Nilai ketiga (misal: nilai ujian)
- `nilai_4` - Nilai keempat (misal: nilai akhir)

## Contoh Struktur Excel/CSV:

```
nama,email,peran,id_peserta,divisi,nilai_1,nilai_2,nilai_3,nilai_4
John Doe,john@example.com,Peserta,ID001,Divisi A,85,90,78,92
Jane Smith,jane@example.com,Peserta,ID002,Divisi B,88,85,90,87
Bob Johnson,bob@example.com,Panitia,ID003,Divisi C,92,88,85,89
```

## Penanganan Data Kosong:
- Jika kolom nilai kosong atau tidak ada, akan ditampilkan "-" pada sertifikat
- Kolom nilai bersifat opsional, tidak akan menyebabkan error jika tidak ada

## Cara Menggunakan:
1. Tambahkan kolom `nilai_1`, `nilai_2`, `nilai_3`, `nilai_4` di file Excel/CSV Anda
2. Isi dengan nilai numerik atau teks sesuai kebutuhan
3. Di template editor, gunakan placeholder `@{{nilai_1}}` sampai `@{{nilai_4}}`
4. Generate sertifikat seperti biasa

## File Contoh:
Lihat file `contoh_data_dengan_nilai.csv` sebagai template.
