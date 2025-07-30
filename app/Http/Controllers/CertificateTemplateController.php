<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CertificateTemplate;

class CertificateTemplateController extends Controller
{
    /**
     * Menampilkan daftar semua template yang tersimpan.
     */
    public function index()
    {
        $templates = CertificateTemplate::orderBy('created_at', 'desc')->paginate(10);
        return view('templates.index', compact('templates'));
    }

    /**
     * Menyimpan template baru ke database.
     */
    public function store(Request $request)
    {
        // Validasi data yang dikirim dari JavaScript
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:certificate_templates,name',
            'template_data' => 'required|json',
        ]);

        $template = CertificateTemplate::create($validatedData);

        // Kirim respons JSON kembali ke JavaScript
        return response()->json([
            'success' => true,
            'message' => 'Template "' . $template->name . '" berhasil disimpan!',
            'template' => $template // Kirim kembali data template yang baru dibuat
        ]);
    }

    /**
     * Mengubah nama template yang sudah ada via AJAX.
     */
    public function update(Request $request, CertificateTemplate $template)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:certificate_templates,name,' . $template->id,
        ]);

        $template->update($validatedData);

        return response()->json([
            'success' => true, 
            'message' => 'Nama template berhasil diperbarui.'
        ]);
    }
    
    /**
     * Menghapus template dari database.
     */
    public function destroy(CertificateTemplate $template)
    {
        $template->delete();

        // Kembali ke halaman form dengan pesan sukses
        return redirect()->route('certificates.bulk.form')
                         ->with('success', 'Template berhasil dihapus.');
    }
}
