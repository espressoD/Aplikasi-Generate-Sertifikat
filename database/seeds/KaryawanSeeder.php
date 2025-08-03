<?php

use Illuminate\Database\Seeder;
use App\Karyawan;

class KaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $karyawan = [
            ['nama' => 'Ahmad Suryanto', 'npk_id' => 'KRY001', 'divisi' => 'IT'],
            ['nama' => 'Siti Nurhaliza', 'npk_id' => 'KRY002', 'divisi' => 'HR'],
            ['nama' => 'Budi Santoso', 'npk_id' => 'KRY003', 'divisi' => 'Finance'],
            ['nama' => 'Dewi Sartika', 'npk_id' => 'KRY004', 'divisi' => 'IT'],
            ['nama' => 'Eko Prasetyo', 'npk_id' => 'KRY005', 'divisi' => 'Marketing'],
            ['nama' => 'Fitri Handayani', 'npk_id' => 'KRY006', 'divisi' => 'HR'],
            ['nama' => 'Gunawan Wijaya', 'npk_id' => 'KRY007', 'divisi' => 'Operations'],
            ['nama' => 'Heni Kusuma', 'npk_id' => 'KRY008', 'divisi' => 'IT'],
            ['nama' => 'Indra Permana', 'npk_id' => 'KRY009', 'divisi' => 'Finance'],
            ['nama' => 'Joko Widodo', 'npk_id' => 'KRY010', 'divisi' => 'Marketing'],
            ['nama' => 'Kartika Sari', 'npk_id' => 'KRY011', 'divisi' => 'HR'],
            ['nama' => 'Lestari Wulandari', 'npk_id' => 'KRY012', 'divisi' => 'Operations'],
            ['nama' => 'Muhammad Rizki', 'npk_id' => 'KRY013', 'divisi' => 'IT'],
            ['nama' => 'Nina Zahara', 'npk_id' => 'KRY014', 'divisi' => 'Finance'],
            ['nama' => 'Oka Mahendra', 'npk_id' => 'KRY015', 'divisi' => 'Marketing'],
            ['nama' => 'Putri Maharani', 'npk_id' => 'KRY016', 'divisi' => 'HR'],
            ['nama' => 'Qori Hidayat', 'npk_id' => 'KRY017', 'divisi' => 'Operations'],
            ['nama' => 'Rina Saputri', 'npk_id' => 'KRY018', 'divisi' => 'IT'],
            ['nama' => 'Surya Pratama', 'npk_id' => 'KRY019', 'divisi' => 'Finance'],
            ['nama' => 'Tina Anggraini', 'npk_id' => 'KRY020', 'divisi' => 'Marketing'],
            ['nama' => 'Usman Hakim', 'npk_id' => 'KRY021', 'divisi' => 'HR'],
            ['nama' => 'Vera Safitri', 'npk_id' => 'KRY022', 'divisi' => 'Operations'],
            ['nama' => 'Wahyu Nugroho', 'npk_id' => 'KRY023', 'divisi' => 'IT'],
            ['nama' => 'Yenny Rahayu', 'npk_id' => 'KRY024', 'divisi' => 'Finance'],
            ['nama' => 'Zaki Ramadhan', 'npk_id' => 'KRY025', 'divisi' => 'Marketing'],
        ];

        foreach ($karyawan as $data) {
            Karyawan::create($data);
        }
    }
}
