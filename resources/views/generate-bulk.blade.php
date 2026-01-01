@extends('layouts.app')

@section('title', 'Generate Sertifikat')
@section('content-title', 'Generate Sertifikat & Editor Template')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Generate & Edit</li>
@endsection

@section('content')
{{-- Bagian Form Input Data --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Langkah 1: Pilih Sumber Data & Generate</h3>
            </div>
            <form id="main-form" action="{{ route('certificates.bulk.generate') }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Hidden fields for required data --}}
                <input type="hidden" name="event_name" id="event_name">
                <input type="hidden" name="certificate_type" id="certificate_type">
                <input type="hidden" name="certificate_number_prefix" id="certificate_number_prefix">
                <input type="hidden" name="start_date" id="start_date">
                <input type="hidden" name="end_date" id="end_date">
                <input type="hidden" name="signing_place" id="signing_place">
                <input type="hidden" name="signing_date" id="signing_date">
                <input type="hidden" name="descriptions[0]" id="hidden_description_1">
                <input type="hidden" name="descriptions[1]" id="hidden_description_2">
                <input type="hidden" name="descriptions[2]" id="hidden_description_3">
                <input type="hidden" name="signature_count" id="hidden_signature_count">
                <input type="hidden" name="template_json" id="template_json">
                <input type="hidden" name="template_id" id="template_id">
                @for ($i = 0; $i < 3; $i++)
                    <input type="hidden" name="signatures[{{ $i }}][title]" id="hidden_signatures_{{ $i }}_title">
                    <input type="hidden" name="signatures[{{ $i }}][name]" id="hidden_signatures_{{ $i }}_name">
                    <input type="hidden" name="signatures[{{ $i }}][image]" id="hidden_signatures_{{ $i }}_image">
                @endfor
                
                <div class="card-body">
                    <h4>Sumber Data Peserta</h4>
                    
                    {{-- Data Source Selection --}}
                    <div class="form-group">
                        <label>Pilih Sumber Data</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-control custom-radio">
                                    <input class="custom-control-input" type="radio" id="data_source_file" name="data_source" value="file" checked>
                                    <label class="custom-control-label" for="data_source_file">
                                        <i class="fas fa-file-excel text-success"></i> Upload File Excel/CSV
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-radio">
                                    <input class="custom-control-input" type="radio" id="data_source_database" name="data_source" value="database">
                                    <label class="custom-control-label" for="data_source_database">
                                        <i class="fas fa-database text-primary"></i> Pilih dari Database
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Database Section --}}
                    <div id="database-section" style="display: none;">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Data Karyawan</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-success btn-sm" id="add-karyawan-btn">
                                        <i class="fas fa-plus"></i> Tambah Karyawan
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                {{-- Search and Filter --}}
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="search-karyawan" placeholder="Cari nama/NPK..." value="{{ $search }}">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control" id="filter-divisi">
                                            <option value="">Semua Divisi</option>
                                            @foreach($divisiList as $divisi)
                                                <option value="{{ $divisi }}" {{ $divisiFilter == $divisi ? 'selected' : '' }}>{{ $divisi }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-primary" id="search-btn">
                                            <i class="fas fa-search"></i> Cari
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset-search">
                                            <i class="fas fa-refresh"></i> Reset
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="btn-group w-100">
                                            <button type="button" class="btn btn-info btn-sm" id="select-all-karyawan">
                                                <i class="fas fa-check-square"></i> Pilih Semua
                                            </button>
                                            <button type="button" class="btn btn-warning btn-sm" id="select-none-karyawan">
                                                <i class="fas fa-square"></i> Batal Semua
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Karyawan Table --}}
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th width="40px">
                                                    <input type="checkbox" id="check-all-karyawan">
                                                </th>
                                                <th>Nama</th>
                                                <th>NPK/ID</th>
                                                <th>Divisi</th>
                                                <th width="100px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="karyawan-table-body">
                                            @foreach($karyawan as $k)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="selected_karyawan[]" value="{{ $k->id }}" class="karyawan-checkbox">
                                                    </td>
                                                    <td>{{ $k->nama }}</td>
                                                    <td>{{ $k->npk_id }}</td>
                                                    <td>{{ $k->divisi }}</td>
                                                    <td>
                                                        <button type="button" class="btn btn-xs btn-warning edit-karyawan-btn" 
                                                                data-id="{{ $k->id }}" 
                                                                data-nama="{{ $k->nama }}" 
                                                                data-npk="{{ $k->npk_id }}" 
                                                                data-divisi="{{ $k->divisi }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-danger delete-karyawan-btn" data-id="{{ $k->id }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination --}}
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <small class="text-muted" id="karyawan-stats">
                                            Menampilkan {{ $karyawan->firstItem() ?? 0 }} - {{ $karyawan->lastItem() ?? 0 }} 
                                            dari {{ $karyawan->total() }} data
                                        </small>
                                    </div>
                                    <div>
                                        <nav>
                                            <ul class="pagination pagination-sm karyawan-pagination" id="karyawan-pagination">
                                                {{ $karyawan->links() }}
                                            </ul>
                                        </nav>
                                    </div>
                                </div>

                                {{-- Selected Count --}}
                                <div class="mt-2">
                                    <div class="alert alert-info" id="selected-info" style="display: none;">
                                        <i class="fas fa-info-circle"></i> 
                                        <span id="selected-count">0</span> karyawan dipilih untuk generate sertifikat
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- File Upload Section --}}
                    <div id="file-section">
                        <div class="form-group">
                            <label for="participant_file">File Data Peserta (.csv, .xlsx)</label>
                            <div class="input-group"><div class="custom-file"><input type="file" class="custom-file-input" id="participant_file" name="participant_file"><label class="custom-file-label" for="participant_file">Pilih file</label></div></div>
                            <small class="form-text text-muted">
                                Struktur kolom wajib: `nama`, `email`, `peran`, `id_peserta`, `divisi`.<br>
                                Kolom opsional untuk nilai: `nilai_1`, `nilai_2`, `nilai_3`, `nilai_4`.
                            </small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Langkah 2: Kelola Template --}}
<div class="card card-info">
    <div class="card-header"><h3 class="card-title">Langkah 2: Kelola & Pilih Template</h3></div>
    <div class="card-body">
        <table id="templates-table" class="table table-hover table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama Template</th>
                    <th>Tanggal Dibuat</th>
                    <th style="width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="template-table-body">
                @forelse ($templates as $template)
                    <tr>
                        <td id="template-name-{{ $template->id }}">{{ $template->name }}</td>
                        <td>{{ $template->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-success load-template-btn" data-template-id="{{ $template->id }}">
                                    <i class="fas fa-check"></i> Muat
                                </button>
                                <button type="button" class="btn btn-sm btn-warning edit-template-btn" data-template-id="{{ $template->id }}" data-template-name="{{ $template->name }}">
                                    <i class="fas fa-edit"></i> Ubah Nama
                                </button>
                                <form action="{{ route('templates.destroy', $template->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">Belum ada template yang disimpan. Buat desain di bawah dan klik "Simpan Desain".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
    {{-- Langkah 3: Editor Kanvas --}}
<div class="card card-secondary">
    <div class="card-header">
        <h3 class="card-title">Langkah 3: Desain Template Sertifikat</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="form-group">
                <label for="bg-upload">Unggah Gambar Latar</label>
                <div class="custom-file"><input type="file" class="custom-file-input" id="bg-upload" accept="image/*"><label class="custom-file-label" for="bg-upload">Pilih file</label></div>
            </div>

        <!-- Canvas dan Sidebar Layout -->
        <div class="row">
            <!-- Area Canvas (8 kolom) -->
            <div class="col-lg-8">
                <div class="mb-3">
                    <button id="add-text" class="btn btn-default"><i class="fas fa-font"></i> Tambah Teks</button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="fas fa-puzzle-piece"></i> Sisipkan Elemen</button>
                        <div class="dropdown-menu" id="insert-menu">
                            <h6 class="dropdown-header">Placeholder Teks</h6>
                            <div id="text-placeholders"></div>
                            <div class="dropdown-divider"></div>
                            <h6 class="dropdown-header">Blok Tanda Tangan</h6>
                            <a class="dropdown-item signature-block-item" href="#" data-index="0">Blok Penandatangan #1</a>
                            <a class="dropdown-item signature-block-item" href="#" data-index="1">Blok Penandatangan #2</a>
                            <a class="dropdown-item signature-block-item" href="#" data-index="2">Blok Penandatangan #3</a>
                        </div>
                    </div>

                    <!-- Compact Undo/Redo (visual only) placed to the right of the "Sisipkan Elemen" dropdown -->
                    <div id="compact-undo-redo" class="ml-2 d-inline-flex align-items-center" role="group" aria-label="Undo Redo">
                        <button id="toolbar-undo" type="button" class="btn btn-light btn-sm compact-tool" title="Undo (Ctrl+Z)" aria-label="Undo" disabled>
                            <i class="fas fa-undo" aria-hidden="true"></i>
                        </button>
                        <button id="toolbar-redo" type="button" class="btn btn-light btn-sm compact-tool ml-1" title="Redo (Ctrl+Y)" aria-label="Redo" disabled>
                            <i class="fas fa-redo" aria-hidden="true"></i>
                        </button>
                    </div>
                    <button id="remove-element" class="btn btn-danger float-right"><i class="fas fa-trash"></i> Hapus Elemen</button>
                </div>

                <!-- Multi-Page Navigation -->
                <div class="card mb-2" style="max-width: 1123px; margin: auto;">
                    <div class="card-header d-flex justify-content-between align-items-center" style="padding: 10px 20px;">
                        <!-- Left: Page Tabs -->
                        <ul class="nav nav-pills mb-0" id="page-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="page-1-tab" data-page="0" href="#" onclick="switchToPage(0); return false;">
                                    Hal 1
                                </a>
                            </li>
                        </ul>
                        
                        <!-- Right: Page Controls -->
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="addNewPage()" title="Tambah Halaman">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="deletePage()" title="Hapus Halaman Ini">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <div style="border: 1px solid #ccc; width: 100%; max-width: 1123px; height: 794px; margin: auto; position: relative;">
                    <!-- Floating Toolbar (Option A Hybrid) -->
                    <div id="floating-toolbar">
                        <div class="floating-pill">
                            <div class="pill-row pill-formatting">
                                <div id="floating-formatting-group" class="floating-formatting">
                                    <div class="pill-item pill-select dropdown-host" id="floating-font-family">
                                        <span class="value">Arial</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <span class="pill-divider"></span>
                                    <div class="pill-item pill-select dropdown-host" id="floating-font-size">
                                        <span class="value">24</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <span class="pill-divider"></span>
                                    <div class="pill-item dropdown-host" id="floating-line-height">
                                        <i class="fas fa-arrows-alt-v"></i>
                                        <span class="value">1.20</span>
                                    </div>
                                    <span class="pill-divider"></span>
                                    <div class="pill-item pill-color dropdown-host" id="floating-font-color">
                                        <button type="button" class="pill-button color-trigger" id="floating-color-trigger" title="Lihat format warna">
                                            <span class="color-dot"></span>
                                            <i class="fas fa-palette"></i>
                                        </button>
                                        <span class="value sr-only">#000000</span>
                                        <div class="color-dropdown" id="floating-color-dropdown" role="menu">
                                            <div class="color-picker-section">
                                                <label class="color-picker-label" for="floating-color-input">Pilih Warna</label>
                                                <input type="color" id="floating-color-input" class="color-native-input" value="#000000">
                                            </div>
                                            <div class="color-swatches-section">
                                                <span class="swatch-title">Warna Umum</span>
                                                <div class="swatch-grid" id="floating-color-swatches"></div>
                                            </div>
                                            <button type="button" class="color-advanced-toggle" id="floating-color-advanced-toggle">Format Lanjutan</button>
                                            <div class="color-advanced" id="floating-color-advanced">
                                                <div class="color-dropdown-tabs" role="tablist">
                                                    <button type="button" class="color-tab active" data-format="hex">HEX</button>
                                                    <button type="button" class="color-tab" data-format="rgb">RGB</button>
                                                    <button type="button" class="color-tab" data-format="hsl">HSL</button>
                                                </div>
                                                <div class="color-dropdown-body">
                                                    <label class="color-format-label" id="floating-color-label">HEX</label>
                                                    <input type="text" class="color-display" id="floating-color-display" value="#000000">
                                                    <small class="color-helper" id="floating-color-helper">Gunakan format sesuai tab aktif lalu tekan Enter.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="pill-divider"></span>
                                    <div class="pill-button-group" id="floating-style-buttons">
                                        <button type="button" class="pill-button" id="floating-bold" title="Bold">
                                            <i class="fas fa-bold"></i>
                                        </button>
                                        <button type="button" class="pill-button" id="floating-italic" title="Italic">
                                            <i class="fas fa-italic"></i>
                                        </button>
                                        <button type="button" class="pill-button" id="floating-underline" title="Underline">
                                            <i class="fas fa-underline"></i>
                                        </button>
                                    </div>
                                    <span class="pill-divider"></span>
                                    <div class="pill-button-group align-group" id="floating-align-horizontal">
                                        <button type="button" class="pill-button floating-align-button" id="floating-align-left" data-align="left" title="Rata kiri">
                                            <i class="fas fa-align-left"></i>
                                        </button>
                                        <button type="button" class="pill-button floating-align-button" id="floating-align-center" data-align="center" title="Rata tengah">
                                            <i class="fas fa-align-center"></i>
                                        </button>
                                        <button type="button" class="pill-button floating-align-button" id="floating-align-right" data-align="right" title="Rata kanan">
                                            <i class="fas fa-align-right"></i>
                                        </button>
                                    </div>
                                    <span class="pill-divider align-vertical-divider is-hidden"></span>
                                    <div class="pill-button-group align-group align-vertical is-hidden" id="floating-align-vertical">
                                        <button type="button" class="pill-button floating-align-button" id="floating-align-top" data-align="top" title="Sejajarkan ke atas">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                        <button type="button" class="pill-button floating-align-button" id="floating-align-middle" data-align="middle" title="Sejajarkan ke tengah vertikal">
                                            <i class="fas fa-arrows-alt-v"></i>
                                        </button>
                                        <button type="button" class="pill-button floating-align-button" id="floating-align-bottom" data-align="bottom" title="Sejajarkan ke bawah">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <span class="pill-info" id="floating-toolbar-info">—</span>
                            </div>
                            <div class="pill-row-divider" id="floating-row-divider"></div>
                            <div class="pill-row pill-groups" id="floating-group-row">
                                <div id="floating-group-actions" class="floating-group-actions">
                                    <button id="float-group" class="pill-button" title="Group Selection (Ctrl+G)">
                                        <i class="fas fa-object-group"></i>
                                        <span>Group</span>
                                    </button>
                                    <button id="float-ungroup" class="pill-button" title="Ungroup (Ctrl+Shift+G)">
                                        <i class="fas fa-object-ungroup"></i>
                                        <span>Ungroup</span>
                                    </button>
                                    <label id="align-mode-toggle" class="pill-button mb-0">
                                        <input type="checkbox" id="align-within-group-checkbox">
                                        <span class="toggle-label">Align Within</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="certificate-canvas"></canvas>
                </div>
            </div>
            
            <!-- Sidebar Properti Kontekstual (4 kolom) -->
            <div class="col-lg-4">
                <div id="contextual-sidebar" class="card">
                    <div id="sidebar-header-full" class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div id="sidebar-header-gear" style="cursor:pointer;display:inline-block;">
                            <h5 class="mb-0"><i class="fas fa-cog mr-2"></i>Panel Properti</h5>
                        </div>
                        <div>
                            <small id="sidebar-subtitle">Pilih elemen untuk mengedit properti</small>
                        </div>
                    </div>
                    <!-- Minimized panel properti -->
                    <div id="sidebar-header-minimized" class="card-header bg-primary text-white d-flex align-items-center justify-content-center" style="display:none; cursor:pointer; min-height:56px;">
                        <button id="sidebar-minimized-btn" type="button" class="btn btn-info btn-sm" style="font-weight:600;">
                            <i class="fas fa-cog"></i> Panel Properti
                        </button>
                    </div>
                    <div class="card-body" id="sidebar-content">
        <!-- Default Content -->
        <div id="sidebar-default" class="text-center text-muted py-4">
            <i class="fas fa-mouse-pointer fa-3x mb-3"></i>
            <h6>Tidak ada yang dipilih</h6>
            <p class="mb-3">Klik elemen pada canvas atau placeholder untuk mengedit propertinya</p>
            
            <div class="alert alert-info text-left">
                <strong><i class="fas fa-lightbulb mr-1"></i>Tips:</strong>
                <ul class="mb-0 mt-2">
                    <li>Klik placeholder untuk mengisi data sertifikat</li>
                    <li>Klik teks untuk mengubah posisi & konten</li>  
                    <li>Klik signature block untuk mengatur tanda tangan</li>
                    <li>Gunakan toolbar di atas untuk format teks</li>
                </ul>
            </div>
        </div>                        <!-- Placeholder Properties -->
                        <div id="sidebar-placeholder" style="display: none;">
                            <!-- Konten akan diisi dinamis berdasarkan placeholder yang dipilih -->
                        </div>
                        
                        <!-- Text Properties -->
                        <div id="sidebar-text" style="display: none;">
                            <h6><i class="fas fa-font mr-2"></i>Properti Teks</h6>
                            <div class="form-group">
                                <label>Isi Teks:</label>
                                <textarea id="text-content" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label>Posisi X:</label>
                                    <input type="number" id="text-pos-x" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label>Posisi Y:</label>
                                    <input type="number" id="text-pos-y" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Signature Block Properties -->
                        <div id="sidebar-signature" style="display: none;">
                            <!-- Konten akan diisi dinamis berdasarkan signature block -->
                        </div>
                        
                        <!-- Canvas Properties -->
                        <div id="sidebar-canvas" style="display: none;">
                            <h6><i class="fas fa-image mr-2"></i>Properti Canvas</h6>
                            <p class="text-muted">Atur background dan ukuran canvas</p>
                            <div class="form-group">
                                <label>Warna Background:</label>
                                <input type="color" id="canvas-bg-color" class="form-control" value="#ffffff">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" id="save-template" class="btn btn-success"><i class="fas fa-save"></i> Simpan Desain Saat Ini</button>
        <button type="button" id="preview-btn" class="btn btn-info"><i class="fas fa-eye"></i> Preview</button>
        <button type="button" id="generate-btn-final" class="btn btn-primary float-right"><i class="fas fa-download"></i> Generate Project</button>
    </div>
    <div class="progress mt-4" id="progress-bar-wrapper" style="display:none;">
        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
    </div>
</div>

{{-- Modal Tambah/Edit Karyawan --}}
<div class="modal fade" id="karyawan-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="karyawan-modal-title">Tambah Karyawan</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="karyawan-form">
                <div class="modal-body">
                    <input type="hidden" id="karyawan-id">
                    <div class="form-group">
                        <label for="karyawan-nama">Nama Lengkap</label>
                        <input type="text" class="form-control" id="karyawan-nama" maxlength="25" required>
                        <div class="d-flex justify-content-between mt-1">
                            <small id="nama-counter" class="text-muted">0/25 karakter</small>
                            <small id="nama-error" class="text-danger" style="display: none;"></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="karyawan-npk">NPK/ID</label>
                        <input type="text" class="form-control" id="karyawan-npk" required>
                    </div>
                    <div class="form-group">
                        <label for="karyawan-divisi">Divisi</label>
                        <input type="text" class="form-control" id="karyawan-divisi" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="save-karyawan-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection


@push('scripts')
<script src="{{ asset('js/fabric.min.js') }}"></script>
<script>
// 🔧 COMPREHENSIVE PATCHES for Fabric.js
(function() {
    // Wait for fabric to be fully loaded
    if (typeof fabric === 'undefined') {
        console.warn('Fabric.js not loaded yet');
        return;
    }

    // PATCH 1: Fix textBaseline 'alphabetical' warning
    const descriptor = Object.getOwnPropertyDescriptor(CanvasRenderingContext2D.prototype, 'textBaseline');
    if (descriptor && descriptor.set) {
        const originalSetter = descriptor.set;
        Object.defineProperty(CanvasRenderingContext2D.prototype, 'textBaseline', {
            set: function(value) {
                if (value === 'alphabetical') {
                    value = 'alphabetic';
                }
                originalSetter.call(this, value);
            },
            get: descriptor.get,
            enumerable: descriptor.enumerable,
            configurable: descriptor.configurable
        });
    }

    // PATCH 2: Fix getImageData performance warning
    // Patch HTMLCanvasElement.getContext to add willReadFrequently by default
    const originalGetContext = HTMLCanvasElement.prototype.getContext;
    HTMLCanvasElement.prototype.getContext = function(contextType, contextAttributes) {
        if (contextType === '2d' || contextType === 'bitmaprenderer') {
            // Merge with default attributes
            contextAttributes = contextAttributes || {};
            if (typeof contextAttributes === 'object' && !('willReadFrequently' in contextAttributes)) {
                contextAttributes.willReadFrequently = true;
            }
        }
        return originalGetContext.call(this, contextType, contextAttributes);
    };

    console.log('✅ Fabric.js patches applied (textBaseline + willReadFrequently)');
})();
</script>
<style>
    .karyawan-pagination .page-link {
        cursor: pointer;
    }
    
    .karyawan-pagination .disabled .page-link {
        cursor: not-allowed;
    }
    
    #karyawan-table-body tr td {
        vertical-align: middle;
    }
    
    .fa-spinner.fa-spin {
        animation: fa-spin 2s infinite linear;
    }
    
    @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(359deg); }
    }

    /* Character counter styling */
    #nama-counter {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    #nama-error {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* Enhanced form validation styling */
    .form-control.is-valid {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    /* Contextual Sidebar Styling */
    #contextual-sidebar {
        max-height: 80vh;
        overflow-y: auto;
    }
    
    /* 🎨 FORMATTING TOOLBAR STATES */
    #formatting-toolbar .btn:disabled,
    #formatting-toolbar select:disabled,
    #formatting-toolbar input:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    
    #formatting-toolbar .btn.active {
        background-color: #007bff !important;
        color: white !important;
        border-color: #007bff !important;
    }
    
    #formatting-toolbar .text-muted {
        color: #6c757d !important;
    }
    
    /* Visual feedback for enabled state */
    #formatting-toolbar:not(.disabled) .btn:not(:disabled) {
        transition: all 0.2s ease;
    }
    
    #formatting-toolbar:not(.disabled) .btn:not(:disabled):hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .sidebar-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    /* Canvas container responsive */
    @media (max-width: 1200px) {
        #certificate-canvas {
            max-width: 100%;
            height: auto;
        }
    }
    
    /* Utility */
    .is-hidden {
        display: none !important;
    }

    /* 🎨 FLOATING TOOLBAR STYLES */
    #floating-toolbar {
        position: absolute;
        display: none;
        z-index: 10000;
        transition: all 0.2s ease;
        user-select: none;
        background: #ffffff;
        border: 1px solid #dfe3e8;
        border-radius: 32px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
        padding: 16px 20px;
        min-width: 0;
        width: auto;
        max-width: min(880px, calc(100% - 48px));
    }

    #floating-toolbar .floating-pill {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
        width: auto;
    }

    .pill-row {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        width: auto;
    }

    .pill-row.pill-formatting {
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .pill-row.pill-formatting #floating-formatting-group {
        flex: 0 1 auto;
    }

    #floating-formatting-group {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        min-width: 0;
        flex: 0 1 auto;
    }

    #floating-formatting-group.is-hidden {
        display: none;
    }

    #floating-group-row {
        display: none;
        justify-content: flex-start;
        width: 100%;
    }

    #floating-group-row.active {
        display: flex;
    }

    #floating-group-actions {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .pill-divider {
        width: 1px;
        height: 32px;
        background: #e1e5eb;
    }

    .pill-row-divider {
        width: 100%;
        height: 1px;
        background: #e1e5eb;
        display: none;
    }

    .pill-row-divider.active {
        display: block;
    }

    .pill-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        color: #1f2937;
    }

    .pill-item .value {
        font-weight: 600;
    }

    .pill-item.pill-select .value {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pill-item.mixed .value {
        color: #6c7280;
        font-style: italic;
    }

    .pill-item .color-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 1px solid #cbd5e1;
        background: var(--preview-color, #000000);
    }

    .pill-item.mixed .color-dot {
        background: linear-gradient(135deg, #111827 0%, #facc15 100%);
    }

    .pill-item.pill-select i {
        font-size: 0.65rem;
        color: #94a3b8;
    }

    #floating-line-height i {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    #floating-font-size .value::after {
        content: ' px';
        font-weight: 500;
        color: #94a3b8;
        margin-left: 2px;
    }

    #floating-font-size.mixed .value::after {
        content: '';
    }

    .pill-button-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pill-button-group.align-group {
        flex-wrap: nowrap;
    }

    .pill-button {
        border: none;
        background: transparent;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1f2937;
        transition: all 0.2s ease;
    }

    .pill-button.color-trigger {
        width: 42px;
        height: 42px;
        border: 1px solid #d1d9e6;
        background: #f8fafc;
        gap: 6px;
    }

    .pill-button.color-trigger:hover {
        background: #e2e8f0;
    }

    .pill-button.color-trigger .color-dot {
        margin-right: 0;
    }

    .pill-button i {
        font-size: 0.8rem;
    }

    .pill-button:hover {
        background: #f1f5f9;
    }

    .pill-button.active {
        background: #111827;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.25);
    }

    .pill-button.mixed {
        border: 1px dashed #cbd5e1;
        color: #1f2937;
        opacity: 0.85;
    }

    .floating-group-actions .pill-button {
        pointer-events: auto;
        border: 1px solid #d1d9e6;
        background: #f8fafc;
        color: #0f172a;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 2px 50px;
        gap: 10px;
        min-height: 38px;
        border-radius: 14px;
    }

    .floating-group-actions .pill-button:hover {
        background: #e2e8f0;
    }

    #floating-align-vertical {
        flex-wrap: nowrap;
    }

    .floating-popover {
        position: absolute;
        min-width: 220px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18);
        padding: 14px 16px;
        z-index: 4000;
        display: none;
        top: calc(100% + 12px);
        left: 50%;
        transform: translateX(-50%);
    }

    .floating-popover.active {
        display: block;
    }

    .floating-popover .popover-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #1f2937;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 10px;
    }

    .floating-popover .popover-section + .popover-section {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }

    .floating-popover .recent-heading,
    .floating-popover .all-heading {
        font-size: 0.7rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 6px;
    }

    .floating-popover .search-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 10px;
    }

    .floating-popover .search-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    .floating-popover .recent-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 8px;
    }

    .floating-popover .option-grid.font-list {
        max-height: 220px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .floating-popover .option-grid.font-list::-webkit-scrollbar {
        width: 6px;
    }

    .floating-popover .option-grid.font-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
    }

    .floating-popover .option-button.font-option {
        justify-content: flex-start;
        gap: 10px;
        display: flex;
        width: 100%;
    }

    .floating-popover .option-button.font-option .sample {
        font-size: 0.78rem;
    }

    .floating-popover .option-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .floating-popover .option-button {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 10px;
        background: #f8fafc;
        font-size: 0.78rem;
        font-weight: 600;
        color: #1f2937;
        transition: all 0.2s ease;
    }

    .floating-popover .option-button:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
    }

    .floating-popover .option-button.active {
        background: #111827;
        border-color: #111827;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.25);
    }

    .floating-popover .option-button.mixed {
        font-style: italic;
        color: #6c7280;
        border-style: dashed;
    }

    .floating-popover .input-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }

    .floating-popover .input-row input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 8px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1f2937;
    }

    .floating-popover .input-row input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    /* Compact spin control styling (light outline) */
    /* Segmented compact spin control (no gaps) */
    .floating-popover .input-row .input-group {
        display: flex;
        align-items: center;
        gap: 0;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid #E6E9EE;
        background: #FFFFFF;
    }

    .floating-popover .input-group .spin-button {
        width: 36px !important;
        height: 36px !important;
        padding: 0 !important;
        margin: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #FFFFFF !important;
        background-color: #FFFFFF !important;
        border: none !important;
        border-radius: 0 !important;
        color: #0F172A !important;
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        position: relative;
        box-shadow: none !important;
    }

    .floating-popover .input-group .spin-button:hover {
        background: #111827 !important;
        background-color: #111827 !important;
        color: #FFFFFF !important;
        border-color: transparent !important;
    }

    .floating-popover .input-group .spin-button:active {
        background: #0b1320 !important;
        background-color: #0b1320 !important;
        color: #FFFFFF !important;
        transform: translateY(1px) !important;
        border-color: transparent !important;
    }

    .floating-popover .input-group .spin-button:focus {
        outline: none !important;
        box-shadow: 0 0 0 4px rgba(17, 24, 39, 0.12) !important;
        background: #FFFFFF !important;
        color: #0F172A !important;
    }

    .floating-popover .input-group .spin-button:focus:hover {
        background: #111827 !important;
        color: #FFFFFF !important;
        box-shadow: 0 0 0 4px rgba(17, 24, 39, 0.12) !important;
    }

    .floating-popover .input-group .spin-button:first-child {
        border-right: 1px solid #E6E9EE !important;
        border-top-left-radius: 7px !important;
        border-bottom-left-radius: 7px !important;
    }

    .floating-popover .input-group .spin-button:last-child {
        border-left: 1px solid #E6E9EE !important;
        border-top-right-radius: 7px !important;
        border-bottom-right-radius: 7px !important;
    }

    .floating-popover .input-row .form-control {
        width: 48px;
        padding: 6px 4px;
        border: none;
        background: #FFFFFF;
        text-align: center;
        font-weight: 700;
        color: #0F172A;
        font-size: 0.875rem;
    }

    .floating-popover .input-row .form-control:focus {
        outline: none;
        box-shadow: 0 0 0 4px rgba(17, 24, 39, 0.12);
        position: relative;
        z-index: 1;
    }

    /* Remove native number input arrows across browsers */
    #floating-font-size-input,
    #floating-line-height-input {
        -moz-appearance: textfield;
        appearance: none;
        -webkit-appearance: none;
        -webkit-touch-callout: none;
    }

    /* Webkit browsers */
    #floating-font-size-input::-webkit-outer-spin-button,
    #floating-font-size-input::-webkit-inner-spin-button,
    #floating-line-height-input::-webkit-outer-spin-button,
    #floating-line-height-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .floating-popover .input-row button {
        border: none;
        border-radius: 8px;
        padding: 6px 12px;
        background: #2563eb;
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .floating-popover .input-row button:hover {
        background: #1d4ed8;
    }

    .floating-popover .popover-note {
        font-size: 0.7rem;
        color: #6c7280;
        margin-bottom: 8px;
    }

    #align-mode-toggle {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 2px 45px;
        border-radius: 14px;
    }

    #align-within-group-checkbox {
        cursor: pointer;
        margin: 0;
        width: 16px;
        height: 16px;
    }

    #align-mode-toggle.active {
        background-color: #17a2b8;
        color: white;
        border-color: #17a2b8;
    }

    #align-mode-toggle .toggle-label {
        font-size: 0.78rem;
        font-weight: 600;
    }

    .pill-info {
        font-size: 0.75rem;
        color: #64748b;
        white-space: nowrap;
        margin-left: auto;
    }

    .pill-groups {
        align-items: center;
        flex-wrap: wrap;
        row-gap: 10px;
    }

    .dropdown-host {
        position: relative;
        z-index: 1;
    }

    .dropdown-host.open {
        z-index: 4001;
    }

    .dropdown-host.open .color-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        pointer-events: auto;
    }

    .color-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 220px;
        background: #ffffff;
        border: 1px solid #dfe3e8;
        border-radius: 16px;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18);
        padding: 14px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-6px);
        transition: opacity 0.18s ease, transform 0.18s ease;
        pointer-events: none;
    }

    .color-picker-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }

    .color-picker-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .color-native-input {
        width: 100%;
        height: 44px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 0;
        background: transparent;
        cursor: pointer;
    }

    .color-swatches-section {
        margin-bottom: 12px;
    }

    .swatch-title {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }

    .swatch-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 6px;
    }

    .color-swatch {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #d1d9e6;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .color-swatch:hover,
    .color-swatch:focus {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.18);
        outline: none;
    }

    .color-swatch.active {
        border: 2px solid #111827;
    }

    .color-advanced-toggle {
        width: 100%;
        border: 1px solid #d1d9e6;
        background: #f8fafc;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
        color: #0f172a;
        padding: 8px 12px;
        transition: background 0.15s ease;
    }

    .color-advanced-toggle:hover {
        background: #e2e8f0;
    }

    .color-advanced {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #e1e5eb;
        display: none;
        gap: 12px;
        flex-direction: column;
    }

    .color-advanced.open {
        display: flex;
    }

    .color-dropdown::before {
        content: '';
        position: absolute;
        top: -8px;
        right: 24px;
        width: 14px;
        height: 14px;
        background: #ffffff;
        border: 1px solid #dfe3e8;
        border-bottom: none;
        border-right: none;
        transform: rotate(45deg);
        box-shadow: -3px -3px 5px rgba(15, 23, 42, 0.06);
    }

    .dropdown-host.open .color-dropdown::before {
        pointer-events: none;
    }

    .color-dropdown-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }

    .color-tab {
        flex: 1;
        border: 1px solid #d1d9e6;
        background: #f8fafc;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #475569;
        padding: 6px 0;
        transition: all 0.15s ease;
    }

    .color-tab:hover {
        background: #e2e8f0;
    }

    .color-tab.active {
        background: #111827;
        color: #ffffff;
        border-color: #111827;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
    }

    .color-tab.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    .color-dropdown-body {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .color-format-label {
        font-size: 0.68rem;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.08em;
    }

    .color-display {
        border: 1px solid #d1d9e6;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1f2937;
        background: #f8fafc;
        width: 100%;
    }

    .color-display:disabled {
        color: #94a3b8;
        background: #f1f5f9;
    }

    .color-display.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15);
        color: #b91c1c;
    }

    .color-helper {
        font-size: 0.65rem;
        color: #94a3b8;
    }
    
    #align-mode-toggle.active {
        background-color: #17a2b8 !important;
        color: white !important;
        border-color: #17a2b8 !important;
    }
    
    #align-within-group-checkbox {
        cursor: pointer;
    }
    
    /* 🆕 MULTI-SELECTION FORMATTING STYLES */
    /* Placeholder styling for "Mixed" values */
    #formatting-toolbar input::placeholder,
    #formatting-toolbar select option[value=""]:disabled {
        color: #999;
        font-style: italic;
    }
    
    /* Compact Undo/Redo styles (for the new compact buttons next to "Sisipkan Elemen") */
    #compact-undo-redo {
        display: inline-flex !important;
        vertical-align: middle;
    }

    #compact-undo-redo .compact-tool {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        box-shadow: none;
        line-height: 1;
        transition: background-color 0.12s ease, transform 0.06s ease;
        border: 1px solid #e6e9ef;
        background: #ffffff;
    }
    #compact-undo-redo .compact-tool i {
        font-size: 14px;
        color: #111827;
    }
    #compact-undo-redo .compact-tool:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    #compact-undo-redo .compact-tool:not(:disabled):hover {
        background: #f1f5f9;
        transform: translateY(-1px);
    }
    .toolbar-inline { gap: 6px; }
    @media (max-width: 576px) {
        #compact-undo-redo .compact-tool { width: 40px; height: 40px; }
    }

</style>
<script>
    // ========================================
    // CONTEXTUAL SIDEBAR MANAGEMENT
    // ========================================
    
    class ContextualSidebar {
        constructor() {
            this.currentSelection = null;
            this.placeholderMappings = {
                '@{{nama_acara}}': {
                    title: 'Nama Acara/Pelatihan',
                    type: 'text',
                    field: 'event_name',
                    placeholder: 'Masukkan nama acara atau pelatihan'
                },
                '@{{jenis_sertifikat}}': {
                    title: 'Jenis Sertifikat',
                    type: 'text',
                    field: 'certificate_type',
                    placeholder: 'Contoh: Partisipasi',
                    help: 'Isi label jenis sertifikat sesuai kebutuhan (mis. Partisipasi, Penghargaan).'
                },
                '@{{nomor_sertifikat}}': {
                    title: 'Format Nomor Sertifikat',
                    type: 'text',
                    field: 'certificate_number_prefix',
                    placeholder: 'Contoh: CERT-{AUTO:100}-2025',
                    help: 'Gunakan {AUTO} atau {AUTO:start_number} untuk penomoran otomatis'
                },
                '@{{tanggal_acara}}': {
                    title: 'Tanggal Acara (Komposit)',
                    type: 'composite',
                    fields: [
                        {label: 'Tanggal Mulai', field: 'start_date', type: 'date'},
                        {label: 'Tanggal Akhir', field: 'end_date', type: 'date'}
                    ]
                },
                '@{{tanggal_penandatanganan}}': {
                    title: 'Tanggal & Tempat Penandatanganan (Komposit)',
                    type: 'composite',
                    fields: [
                        {label: 'Tempat', field: 'signing_place', type: 'text', placeholder: 'Contoh: Bandung'},
                        {label: 'Tanggal', field: 'signing_date', type: 'date'}
                    ]
                },
                '@{{deskripsi_1}}': {
                    title: 'Deskripsi Kustom 1',
                    type: 'text',
                    field: 'descriptions[0]',
                    placeholder: 'Deskripsi kustom untuk placeholder 1'
                },
                '@{{deskripsi_2}}': {
                    title: 'Deskripsi Kustom 2',
                    type: 'text', 
                    field: 'descriptions[1]',
                    placeholder: 'Deskripsi kustom untuk placeholder 2'
                },
                '@{{deskripsi_3}}': {
                    title: 'Deskripsi Kustom 3',
                    type: 'text',
                    field: 'descriptions[2]', 
                    placeholder: 'Deskripsi kustom untuk placeholder 3'
                }
            };

            this.ensureMappingHiddenFields();
        }

        sanitizeFieldKey(field) {
            if (!field) return '';
            return field
                .replace(/\]/g, '')
                .replace(/\[/g, '_')
                .replace(/_{2,}/g, '_')
                .replace(/_$/g, '');
        }

        ensureHiddenField(field) {
            if (!field) return $();
            
            // First, try to find by name attribute
            let hidden = $(`input[name="${field}"]`).first();
            if (hidden.length) {
                return hidden;
            }
            
            // Second, try by ID (for simple fields like event_name, certificate_type, etc.)
            hidden = $(`#${field}`);
            if (hidden.length) {
                return hidden;
            }
            
            // Third, try sanitized key
            const key = this.sanitizeFieldKey(field);
            hidden = $(`#hidden_${key}`);
            if (hidden.length) {
                return hidden;
            }
            
            // If not found, create new hidden field
            const form = $('#main-form');
            if (!form.length) {
                console.warn('Form #main-form not found');
                return $();
            }
            
            hidden = $('<input>', {
                type: 'hidden',
                id: field.includes('[') ? `hidden_${key}` : field,
                name: field,
                value: ''
            });
            form.append(hidden);
            
            return hidden;
        }

        ensureMappingHiddenFields() {
            Object.values(this.placeholderMappings).forEach(mapping => {
                if (!mapping) return;
                if (mapping.type === 'composite' && Array.isArray(mapping.fields)) {
                    mapping.fields.forEach(fieldConfig => this.ensureHiddenField(fieldConfig.field));
                } else if (mapping.field) {
                    this.ensureHiddenField(mapping.field);
                }
            });
        }
        
        showDefault() {
            this.hideAll();
            $('#sidebar-default').show();
            $('#sidebar-subtitle').text('Pilih elemen untuk mengedit properti');
            this.currentSelection = null;
        }
        
        showPlaceholder(placeholderText) {
            this.hideAll();
            
            const mapping = this.placeholderMappings[placeholderText];
            if (!mapping) {
                this.showDefault();
                return;
            }

            if (mapping.type === 'composite' && Array.isArray(mapping.fields)) {
                mapping.fields.forEach(fieldConfig => this.ensureHiddenField(fieldConfig.field));
            } else if (mapping.field) {
                this.ensureHiddenField(mapping.field);
            }

            this.currentSelection = window.canvas ? window.canvas.getActiveObject() : null;
            
            let html = `<h6><i class="fas fa-tag mr-2"></i>${mapping.title}</h6>`;
            html += `<p class="text-muted small">Placeholder: <code>${placeholderText}</code></p>`;
            
            if (mapping.type === 'text') {
                html += `
                    <div class="form-group">
                        <label>${mapping.title}:</label>
                        <input type="text" class="form-control sidebar-input" 
                               data-field="${mapping.field}" 
                               placeholder="${mapping.placeholder || ''}"
                               value="${this.getFieldValue(mapping.field)}">
                        ${mapping.help ? `<small class="text-muted">${mapping.help}</small>` : ''}
                    </div>`;
            } else if (mapping.type === 'select') {
                html += `
                    <div class="form-group">
                        <label>${mapping.title}:</label>
                        <select class="form-control sidebar-input" data-field="${mapping.field}">`;
                
                mapping.options.forEach(opt => {
                    const selected = this.getFieldValue(mapping.field) === opt.value ? 'selected' : '';
                    html += `<option value="${opt.value}" ${selected}>${opt.text}</option>`;
                });
                
                html += `</select></div>`;
            } else if (mapping.type === 'composite') {
                mapping.fields.forEach(field => {
                    html += `
                        <div class="form-group">
                            <label>${field.label}:</label>
                            <input type="${field.type}" class="form-control sidebar-input" 
                                   data-field="${field.field}"
                                   placeholder="${field.placeholder || ''}"
                                   value="${this.getFieldValue(field.field)}">
                        </div>`;
                });
            }
            
            $('#sidebar-placeholder').html(html).show();
            $('#sidebar-subtitle').text(`Properti: ${mapping.title}`);
            
            // Bind change events
            $('.sidebar-input').on('input change', (e) => {
                const field = $(e.target).data('field');
                const value = $(e.target).val();
                
                this.updateHiddenField(field, value);
                
                // 🔥 LIVE UPDATE: Update canvas text dengan format yang benar
                this.updateCanvasPlaceholder(placeholderText, mapping);
            });
        }
        
        // 🆕 FUNGSI BARU: Update teks placeholder di canvas dengan format yang sudah dirender
        updateCanvasPlaceholder(placeholderText, mapping) {
            // ✅ PERBAIKAN: Pastikan canvas ada
            if (!window.canvas) return;

            let activeObject = window.canvas.getActiveObject();
            if (!activeObject || !activeObject.isPlaceholder) {
                if (this.currentSelection && this.currentSelection.isPlaceholder) {
                    activeObject = this.currentSelection;
                } else {
                    return;
                }
            }

            let formattedText = '';
            
            if (mapping.type === 'composite') {
                // Format composite berdasarkan jenis placeholder
                if (placeholderText === '@{{tanggal_penandatanganan}}') {
                    const place = this.getFieldValue('signing_place');
                    const date = this.getFieldValue('signing_date');
                    
                    if (place || date) {
                        const formattedDate = date ? this.formatIndonesianDate(date) : '';
                        formattedText = [place, formattedDate].filter(x => x).join(', ');
                    } else {
                        formattedText = placeholderText; // Keep placeholder if empty
                    }
                } else if (placeholderText === '@{{tanggal_acara}}') {
                    const startDate = this.getFieldValue('start_date');
                    const endDate = this.getFieldValue('end_date');
                    
                    if (startDate || endDate) {
                        formattedText = this.formatDateRange(startDate, endDate);
                    } else {
                        formattedText = placeholderText; // Keep placeholder if empty
                    }
                }
            } else {
                // For simple text/select fields
                const value = this.getFieldValue(mapping.field);
                formattedText = value || placeholderText;
            }
            
            // Update canvas text on CURRENT page
            activeObject.set('text', formattedText);
            if (typeof activeObject.setCoords === 'function') {
                activeObject.setCoords();
            }
            if (typeof window.canvas.requestRenderAll === 'function') {
                window.canvas.requestRenderAll();
            } else {
                window.canvas.renderAll();
            }

            // 🆕 MULTI-PAGE: Update same placeholder on ALL other pages
            this.updatePlaceholderOnAllPages(placeholderText, formattedText);
        }

        // 🆕 Update same placeholder across all pages
        updatePlaceholderOnAllPages(placeholderText, formattedText) {
            if (!window.canvasPages) return;

            // Save current page first
            if (typeof saveCurrentPageState === 'function') {
                saveCurrentPageState();
            }

            // Update placeholder in all pages' saved states
            window.canvasPages.forEach((page, index) => {
                if (index === window.currentPageIndex) return; // Skip current page (already updated)
                if (!page.state || !page.state.objects) return;

                // Find and update placeholder in page state
                page.state.objects.forEach(obj => {
                    if (obj.type === 'text' && obj.isPlaceholder && obj.placeholderType === placeholderText) {
                        obj.text = formattedText;
                    } else if (obj.type === 'group' && obj.objects) {
                        // Update placeholders inside groups (signature blocks)
                        obj.objects.forEach(groupObj => {
                            if (groupObj.type === 'text' && groupObj.isPlaceholder && groupObj.placeholderType === placeholderText) {
                                groupObj.text = formattedText;
                            }
                        });
                    }
                });
            });
        }
        
        // 🆕 HELPER: Format tanggal ke bahasa Indonesia
        formatIndonesianDate(dateString) {
            if (!dateString) return '';
            
            // Parse tanggal manual untuk menghindari timezone issues
            // Format input: YYYY-MM-DD atau MM/DD/YYYY
            let year, month, day;
            
            if (dateString.includes('-')) {
                // Format: YYYY-MM-DD
                [year, month, day] = dateString.split('-').map(num => parseInt(num));
            } else if (dateString.includes('/')) {
                // Format: MM/DD/YYYY
                const parts = dateString.split('/').map(num => parseInt(num));
                month = parts[0];
                day = parts[1];
                year = parts[2];
            } else {
                return dateString; // Return as-is jika format tidak dikenali
            }
            
            const months = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            
            const monthName = months[month - 1]; // Array index dimulai dari 0
            
            return `${day} ${monthName} ${year}`;
        }
        
        // 🆕 SMART HELPER: Format date range dengan logika intelligent
        formatDateRange(startDateStr, endDateStr) {
            // Jika hanya salah satu yang diisi
            if (!startDateStr && endDateStr) return this.formatIndonesianDate(endDateStr);
            if (startDateStr && !endDateStr) return this.formatIndonesianDate(startDateStr);
            if (!startDateStr && !endDateStr) return '';
            
            // Parse kedua tanggal
            const start = this.parseDateString(startDateStr);
            const end = this.parseDateString(endDateStr);
            
            const months = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            
            // 1️⃣ Same date (single day event)
            if (start.year === end.year && start.month === end.month && start.day === end.day) {
                return `${start.day} ${months[start.month - 1]} ${start.year}`;
            }
            
            // 2️⃣ Same month & year
            if (start.year === end.year && start.month === end.month) {
                return `${start.day} - ${end.day} ${months[start.month - 1]} ${start.year}`;
            }
            
            // 3️⃣ Different months, same year
            if (start.year === end.year) {
                return `${start.day} ${months[start.month - 1]} - ${end.day} ${months[end.month - 1]} ${start.year}`;
            }
            
            // 4️⃣ Different years
            return `${start.day} ${months[start.month - 1]} ${start.year} - ${end.day} ${months[end.month - 1]} ${end.year}`;
        }
        
        // 🆕 HELPER: Parse date string ke object {year, month, day}
        parseDateString(dateString) {
            let year, month, day;
            
            if (dateString.includes('-')) {
                // Format: YYYY-MM-DD
                [year, month, day] = dateString.split('-').map(num => parseInt(num));
            } else if (dateString.includes('/')) {
                // Format: MM/DD/YYYY
                const parts = dateString.split('/').map(num => parseInt(num));
                month = parts[0];
                day = parts[1];
                year = parts[2];
            }
            
            return { year, month, day };
        }
        
        showSignatureBlock(index) {
            this.hideAll();
            
            let html = `<h6><i class="fas fa-signature mr-2"></i>Blok Tanda Tangan #${index + 1}</h6>`;
            html += `
                <div class="form-group">
                    <label>Nama Lengkap:</label>
                    <input type="text" class="form-control sidebar-input" 
                           data-field="signatures[${index}][name]"
                           value="${this.getFieldValue(`signatures[${index}][name]`)}">
                </div>
                <div class="form-group">
                    <label>Jabatan:</label>
                    <input type="text" class="form-control sidebar-input" 
                           data-field="signatures[${index}][title]"
                           value="${this.getFieldValue(`signatures[${index}][title]`)}">
                </div>
                <div class="form-group">
                    <label>Gambar Tanda Tangan:</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="signature_image_${index}" accept="image/png">
                        <label class="custom-file-label" for="signature_image_${index}">Pilih gambar</label>
                    </div>
                    <img id="signature_preview_${index}" src="" alt="Preview Tanda Tangan" style="display:none; max-width:100%; margin-top:8px;" />
                    <small class="text-muted d-block mt-1">Format: PNG dengan background transparan</small>
                </div>`;
            
            $('#sidebar-signature').html(html).show();
            $('#sidebar-subtitle').text(`Properti: Tanda Tangan #${index + 1}`);
            this.currentSelection = null;
            
            // Bind change events
            $('.sidebar-input').on('input change', (e) => {
                const field = $(e.target).data('field');
                const value = $(e.target).val();
                this.updateHiddenField(field, value);

                // Live update to canvas: update name/title text inside the signature block
                const group = window.canvas.getObjects().find(obj => obj.type === 'group' && obj.isSignatureBlock && obj.signatureIndex === index);
                if (group) {
                    const nameVal = this.getFieldValue(`signatures[${index}][name]`);
                    const titleVal = this.getFieldValue(`signatures[${index}][title]`);
                    const namePlaceholder = `@{{nama_penandatangan_${index + 1}}}`;
                    const titlePlaceholder = `@{{jabatan_penandatangan_${index + 1}}}`;
                    const objs = group._objects || group.getObjects();
                    let nameText = objs.find(o => (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') && (o.isSignatureName === true));
                    let titleText = objs.find(o => (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') && (o.isSignatureTitle === true));
                    // Fallback to order if flags missing
                    const textObjs = objs.filter(o => o.type === 'i-text' || o.type === 'text' || o.type === 'textbox');
                    if (!nameText) nameText = textObjs[0];
                    if (!titleText) titleText = textObjs[1];
                    if (nameText) nameText.set('text', nameVal || namePlaceholder);
                    if (titleText) titleText.set('text', titleVal || titlePlaceholder);
                    if (window.canvas.requestRenderAll) window.canvas.requestRenderAll();
                }
            });
        }
        
        hideAll() {
            $('#sidebar-default, #sidebar-placeholder, #sidebar-text, #sidebar-signature, #sidebar-canvas').hide();
            this.currentSelection = null;
        }
        
        getFieldValue(field) {
            if (!field) return '';
            const hiddenField = this.ensureHiddenField(field);
            if (hiddenField.length) {
                const val = hiddenField.val();
                if (val !== undefined && val !== null && val !== '') {
                    return val;
                }
            }
            // Try to get from hidden field first
            // Fallback to direct field
            const directField = $(`[name="${field}"]`);
            return directField.length ? directField.val() || '' : '';
        }
        
        updateHiddenField(field, value) {
            if (!field) return;
            const hiddenField = this.ensureHiddenField(field);
            if (hiddenField.length) {
                hiddenField.val(value);
            }
            // Update hidden field
            // Also update original field if exists
            $(`[name="${field}"]`).val(value);
        }
    }
    
    // Global sidebar instance
    window.contextualSidebar = new ContextualSidebar();
    
    // ========================================
    // UNDO/REDO MANAGER
    // ========================================
    class UndoRedoManager {
        constructor(canvas, maxHistorySize = 50) {
            this.canvas = canvas;
            this.undoStack = [];
            this.redoStack = [];
            this.maxHistorySize = maxHistorySize;
            this.isUndoRedoAction = false; // Flag to prevent history push during undo/redo
            this.customProperties = ['isPlaceholder', 'placeholderType', 'isSignatureBlock', 'signatureIndex', 
                                     'areaKey', 'isSignatureName', 'isSignatureTitle', 'isCustomGroup'];
            
            this.initEventListeners();
        }
        
        initEventListeners() {
            // Track canvas modifications
            this.canvas.on('object:added', () => this.saveState());
            this.canvas.on('object:modified', () => this.saveState());
            this.canvas.on('object:removed', () => this.saveState());
            
            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                // Ctrl+Z or Cmd+Z for Undo
                if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.undo();
                }
                // Ctrl+Y or Cmd+Y or Ctrl+Shift+Z for Redo
                else if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.shiftKey && e.key === 'z'))) {
                    e.preventDefault();
                    this.redo();
                }
            });
        }
        
        saveState() {
            // Don't save state if we're currently undoing/redoing
            if (this.isUndoRedoAction) return;
            
            // Serialize canvas state with custom properties
            const state = JSON.stringify(this.canvas.toJSON(this.customProperties));
            
            // Don't save if state is identical to last state
            if (this.undoStack.length > 0) {
                const lastState = this.undoStack[this.undoStack.length - 1];
                if (lastState === state) return;
            }
            
            // Add to undo stack
            this.undoStack.push(state);
            
            // Clear redo stack on new action
            this.redoStack = [];
            
            // Limit stack size
            if (this.undoStack.length > this.maxHistorySize) {
                this.undoStack.shift();
            }
            
            // Update button states
            this.updateButtons();
        }
        
        undo() {
            if (this.undoStack.length <= 1) return; // Keep at least one state (initial)
            
            // Current state goes to redo stack
            const currentState = this.undoStack.pop();
            this.redoStack.push(currentState);
            
            // Load previous state
            const previousState = this.undoStack[this.undoStack.length - 1];
            this.loadState(previousState);
            
            this.updateButtons();
        }
        
        redo() {
            if (this.redoStack.length === 0) return;
            
            // Get next state
            const nextState = this.redoStack.pop();
            this.undoStack.push(nextState);
            
            // Load state
            this.loadState(nextState);
            
            this.updateButtons();
        }
        
        loadState(stateJson) {
            this.isUndoRedoAction = true;
            
            const state = JSON.parse(stateJson);
            
            // Clear canvas and load state
            this.canvas.clear();
            this.canvas.loadFromJSON(state, () => {
                this.canvas.renderAll();
                this.isUndoRedoAction = false;
            });
        }
        
        updateButtons() {
                // Update undo button (new compact controls)
                if (this.undoStack.length > 1) {
                    $('#toolbar-undo').prop('disabled', false).removeClass('text-muted');
                } else {
                    $('#toolbar-undo').prop('disabled', true).addClass('text-muted');
                }

                // Update redo button (new compact controls)
                if (this.redoStack.length > 0) {
                    $('#toolbar-redo').prop('disabled', false).removeClass('text-muted');
                } else {
                    $('#toolbar-redo').prop('disabled', true).addClass('text-muted');
                }
        }
        
        clear() {
            this.undoStack = [];
            this.redoStack = [];
            this.saveState(); // Save initial state
            this.updateButtons();
        }
    }
    
    $(document).ready(function() {
        // Minimize/collapse the main navigation by default for this page.
        // AdminLTE responds to the `sidebar-collapse` class on <body>.
        try { document.body.classList.add('sidebar-collapse'); } catch (err) { /* ignore on SSR or odd contexts */ }

        const canvas = window.canvas=new fabric.Canvas('certificate-canvas', {
            width: 1123, // A4 landscape width: 29.7cm at 96 DPI
            height: 794, // A4 landscape height: 21cm at 96 DPI
            backgroundColor: '#ffffff',
            // 🎯 IMPROVE TEXT RENDERING QUALITY
            enableRetinaScaling: true,  // Enable high-DPI rendering
            renderOnAddRemove: true,
            // 🎯 Visual cue for drag-selection (rubber-band box) - OPAQUE VERSION
            selection: true,
            selectionColor: 'rgba(23,162,184,0.3)',  // More opaque teal
            selectionBorderColor: '#17a2b8',
            selectionLineWidth: 2,
            selectionDashArray: [10, 5]
        });
        
        // 🎨 Configure canvas for better text quality
        // 🔧 Safely access the underlying context
        try {
            const canvasElement = canvas.lowerCanvasEl || canvas.upperCanvasEl;
            if (canvasElement) {
                const ctx = canvasElement.getContext('2d', { willReadFrequently: true });
                if (ctx) {
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    if ('textRendering' in ctx) {
                        ctx.textRendering = 'optimizeLegibility';
                    }
                }
            }
        } catch (err) {
            console.warn('Could not configure canvas context:', err);
        }
        
        // 🔧 Ensure initial render
        canvas.renderAll();

        // 🔄 Initialize Undo/Redo Manager
        window.undoRedoManager = new UndoRedoManager(canvas);
        
        // 🎨 Initialize Align Within Group Mode
        window.alignWithinGroupMode = false;
        
        // Global state untuk menyimpan checkbox yang dipilih di semua halaman
        window.selectedKaryawanIds = new Set();

        // ========== 📄 MULTI-PAGE CANVAS MANAGEMENT ==========
        // Initialize multi-page state
        window.canvasPages = [{
            id: 1,
            state: null,
            bgImage: null,
            bgColor: '#ffffff'
        }];
        window.currentPageIndex = 0;
        
        // ========== FLOATING TOOLBAR & GROUP/UNGROUP ==========
        initFloatingFormattingPreview(canvas);
        initFloatingColorDropdown();
        initGroupingFeatures(canvas);

        // ========== 1. === EDITOR & PLACEHOLDER ==========
        initCanvasEvents(canvas);
        initSidebarEvents(canvas);
        populateTextPlaceholders();
        bindSignatureBlocks(canvas);
        bindEditorControls(canvas);

        // ========== 2. === TEMPLATE HANDLER ==========
        const savedTemplates = @json($templates);
        bindTemplateHandlers(canvas, savedTemplates);

        // ========== 3. === CERTIFICATE NUMBER PREVIEW ==========
        setupCertificateNumberPreview();

        // ========== 3. === PREVIEW & GENERATE SUBMISSION ==========
        $('#preview-btn').on('click', () => handlePreview(canvas));
        $('#generate-btn-final').on('click', () => handleGenerate(canvas));

        // ========== 4. === INPUT HANDLERS (Signatures, Tanggal, Database) ==========
        bindFormInputHandlers();
        bindDatabaseHandlers();
        bindKaryawanCRUD();
    });

    // ========== 📄 MULTI-PAGE MANAGEMENT FUNCTIONS ==========
    
    /**
     * Save current page state before switching pages
     */
    function saveCurrentPageState() {
        if (!window.canvas) return;
        
        window.canvasPages[window.currentPageIndex] = {
            id: window.canvasPages[window.currentPageIndex].id,
            state: window.canvas.toJSON([
                'isPlaceholder', 'placeholderType', 
                'isSignatureBlock', 'signatureIndex',
                'areaKey', 'isCustomGroup'
            ]),
            bgImage: window.backgroundImageSrc || null,
            bgColor: window.canvasBackgroundColor || '#ffffff'
        };
    }

    /**
     * Switch to specific page
     */
    function switchToPage(pageIndex) {
        if (pageIndex < 0 || pageIndex >= window.canvasPages.length) return;
        
        // Allow re-loading same page (remove early return check)
        // This is needed when deletePage needs to force reload
        
        // Save current state (only if currentPageIndex is valid)
        if (window.currentPageIndex >= 0 && window.currentPageIndex < window.canvasPages.length) {
            saveCurrentPageState();
        }
        
        // Update index
        window.currentPageIndex = pageIndex;
        
        // Clear canvas
        window.canvas.clear();
        
        const page = window.canvasPages[pageIndex];
        
        // Set background color
        window.canvasBackgroundColor = page.bgColor;
        window.canvas.backgroundColor = page.bgColor;
        
        // Load background image if exists
        if (page.bgImage) {
            window.backgroundImageSrc = page.bgImage;
            loadBackgroundImageFromData(page.bgImage);
        } else {
            window.backgroundImageSrc = null;
        }
        
        // Load canvas state if exists
        if (page.state) {
            window.canvas.loadFromJSON(page.state, () => {
                window.canvas.renderAll();
                if (typeof updatePlaceholderMappings === 'function') {
                    updatePlaceholderMappings();
                }
                // Re-apply shared placeholder data to new page (with small delay to ensure sidebar is ready)
                setTimeout(() => {
                    reapplyPlaceholderData();
                }, 100);
            });
        } else {
            window.canvas.renderAll();
            setTimeout(() => {
                reapplyPlaceholderData();
            }, 100);
        }
        
        // Update UI
        updatePageTabs();
    }

    /**
     * Add new page
     */
    function addNewPage() {
        const newId = window.canvasPages.length + 1;
        window.canvasPages.push({
            id: newId,
            state: null,
            bgImage: null,
            bgColor: '#ffffff'
        });
        
        // Add tab to UI
        const tabsContainer = document.getElementById('page-tabs');
        const newTab = document.createElement('li');
        newTab.className = 'nav-item';
        newTab.innerHTML = `
            <a class="nav-link" id="page-${newId}-tab" data-page="${newId - 1}" 
               href="#" onclick="switchToPage(${newId - 1}); return false;">
                Hal ${newId}
            </a>
        `;
        tabsContainer.appendChild(newTab);
        
        // Switch to new page
        switchToPage(newId - 1);
        
        if (typeof toastr !== 'undefined') {
            toastr.success(`Halaman ${newId} ditambahkan`);
        }
    }

    /**
     * Delete current page
     */
    function deletePage() {
        if (window.canvasPages.length === 1) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Minimal harus ada 1 halaman');
            } else {
                alert('Minimal harus ada 1 halaman');
            }
            return;
        }
        
        if (!confirm(`Hapus Halaman ${window.currentPageIndex + 1}?`)) return;
        
        const deletedIndex = window.currentPageIndex;
        
        // Remove page from array
        window.canvasPages.splice(deletedIndex, 1);
        
        // Determine which page to switch to
        let newIndex;
        if (deletedIndex >= window.canvasPages.length) {
            // Deleted last page, go to new last page
            newIndex = window.canvasPages.length - 1;
        } else {
            // Deleted middle/first page, stay at same index (which now shows next page)
            newIndex = deletedIndex;
        }
        
        // Rebuild tabs first
        rebuildPageTabs();
        
        // Switch to target page (switchToPage now allows reloading same index)
        switchToPage(newIndex);
        
        if (typeof toastr !== 'undefined') {
            toastr.info('Halaman dihapus');
        }
    }

    /**
     * Update page tabs UI (active state)
     */
    function updatePageTabs() {
        document.querySelectorAll('#page-tabs .nav-link').forEach((tab, index) => {
            tab.classList.toggle('active', index === window.currentPageIndex);
        });
    }

    /**
     * Rebuild all page tabs from scratch
     */
    function rebuildPageTabs() {
        const tabsContainer = document.getElementById('page-tabs');
        tabsContainer.innerHTML = '';
        
        window.canvasPages.forEach((page, index) => {
            const tab = document.createElement('li');
            tab.className = 'nav-item';
            tab.innerHTML = `
                <a class="nav-link ${index === window.currentPageIndex ? 'active' : ''}" 
                   id="page-${index + 1}-tab" data-page="${index}" 
                   href="#" onclick="switchToPage(${index}); return false;">
                    Hal ${index + 1}
                </a>
            `;
            tabsContainer.appendChild(tab);
        });
    }

    /**
     * Helper function to load background image from data URL
     */
    function loadBackgroundImageFromData(dataURL) {
        fabric.Image.fromURL(dataURL, (img) => {
            if (!img) return;
            
            const canvasWidth = window.canvas.width;
            const canvasHeight = window.canvas.height;
            
            const scaleX = canvasWidth / img.width;
            const scaleY = canvasHeight / img.height;
            
            img.set({
                scaleX: scaleX,
                scaleY: scaleY,
                selectable: false,
                evented: false
            });
            
            window.canvas.setBackgroundImage(img, window.canvas.renderAll.bind(window.canvas));
        });
    }

    /**
     * Re-apply shared placeholder data to current page
     * This ensures all pages use the same data without re-entering
     */
    function reapplyPlaceholderData() {
        if (!window.canvas) {
            // console.warn('reapplyPlaceholderData: canvas not ready');
            return;
        }
        if (!window.contextualSidebar) {
            // console.warn('reapplyPlaceholderData: contextualSidebar not ready');
            return;
        }

        // console.log('🔄 Re-applying placeholder data to page', window.currentPageIndex + 1);
        let updateCount = 0;

        // Iterate all text objects with placeholders
        window.canvas.getObjects('text').forEach(obj => {
            if (obj.isPlaceholder && obj.placeholderType) {
                const placeholderText = obj.placeholderType;
                const mapping = window.contextualSidebar.placeholderMappings[placeholderText];
                
                if (mapping) {
                    let formattedText = '';
                    
                    if (mapping.type === 'composite') {
                        // Use ContextualSidebar's formatting logic
                        if (placeholderText === '@{{tanggal_penandatanganan}}') {
                            const place = window.contextualSidebar.getFieldValue('signing_place');
                            const date = window.contextualSidebar.getFieldValue('signing_date');
                            
                            if (place || date) {
                                const formattedDate = date ? window.contextualSidebar.formatIndonesianDate(date) : '';
                                formattedText = [place, formattedDate].filter(x => x).join(', ');
                            }
                        } else if (placeholderText === '@{{tanggal_acara}}') {
                            const startDate = window.contextualSidebar.getFieldValue('start_date');
                            const endDate = window.contextualSidebar.getFieldValue('end_date');
                            
                            if (startDate || endDate) {
                                formattedText = window.contextualSidebar.formatDateRange(startDate, endDate);
                            }
                        }
                    } else {
                        // Simple text/select fields
                        const value = window.contextualSidebar.getFieldValue(mapping.field);
                        formattedText = value || '';
                    }
                    
                    // Always update if we have formatted text (even if empty, to reset placeholder)
                    if (formattedText) {
                        const oldText = obj.text;
                        obj.set('text', formattedText);
                        updateCount++;
                        // console.log(`  ✅ Updated ${placeholderText}: "${oldText}" → "${formattedText}"`);
                    } else {
                        // No value, reset to placeholder text
                        if (obj.text !== placeholderText) {
                            obj.set('text', placeholderText);
                            // console.log(`  ↩️  Reset ${placeholderText} to placeholder`);
                        }
                    }
                }
            }
        });

        // Apply to signature blocks
        window.canvas.getObjects('group').forEach(group => {
            if (group.isSignatureBlock !== undefined) {
                const index = group.signatureIndex;
                if (index !== undefined) {
                    group.getObjects().forEach(obj => {
                        if (obj.type === 'text' && obj.placeholderType) {
                            const placeholderText = obj.placeholderType;
                            let formattedText = '';
                            
                            // Get signature data from sidebar
                            if (placeholderText === `@{{nama_penandatangan_${index + 1}}}`) {
                                formattedText = document.getElementById(`signer-${index + 1}-name`)?.value || '';
                            } else if (placeholderText === `@{{jabatan_penandatangan_${index + 1}}}`) {
                                formattedText = document.getElementById(`signer-${index + 1}-title`)?.value || '';
                            }
                            
                            // Always update if we have data
                            if (formattedText) {
                                const oldText = obj.text;
                                obj.set('text', formattedText);
                                updateCount++;
                                // console.log(`  ✅ Updated ${placeholderText}: "${oldText}" → "${formattedText}"`);
                            } else {
                                // Reset to placeholder if no value
                                if (obj.text !== placeholderText) {
                                    obj.set('text', placeholderText);
                                    // console.log(`  ↩️  Reset ${placeholderText} to placeholder`);
                                }
                            }
                        }
                    });
                }
            }
        });

        window.canvas.renderAll();
        // console.log(`✅ Re-apply complete: ${updateCount} placeholders updated`);
    }


    // State untuk dropdown warna floating toolbar
    const floatingColorState = {
        format: 'hex',
        values: {
            hex: '#000000',
            rgb: 'rgb(0, 0, 0)',
            hsl: 'hsl(0, 0%, 0%)'
        },
        isMixed: true,
        currentHex: '#000000',
        advancedOpen: false,
        swatchesInitialized: false
    };

    const FLOATING_QUICK_COLORS = [
        '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF',
        '#8B4513', '#FFA500', '#800080', '#008080', '#008000', '#FFD700', '#A52A2A', '#2F4F4F',
        '#1D4ED8', '#F97316'
    ];

    const FLOATING_FONT_FAMILIES = [
        'Arial',
        'Helvetica',
        'Times New Roman',
        'Georgia',
        'Verdana',
        'Tahoma',
        'Courier New',
        'Palatino Linotype',
        'Garamond',
        'Bookman Old Style',
        'Comic Sans MS',
        'Trebuchet MS'
    ];

    let floatingRecentFonts = [];

    const FLOATING_FONT_SIZE_PRESETS = [8, 10, 12, 14, 16, 18, 20, 24, 28, 32, 36, 48, 64, 72];

    const FLOATING_LINE_HEIGHT_PRESETS = [0.8, 1.0, 1.2, 1.35, 1.5, 1.75, 2.0, 2.5, 3.0];

    const floatingPopoverState = {
        isOpen: false,
        type: null,
        anchor: null,
        canvas: null
    };

    // ========== FUNCTION DEFINITIONS ==========

    function initCanvasEvents(canvas) {
        $('#bg-upload').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(f) {
                fabric.Image.fromURL(f.target.result, function(img) {
                    // Ensure background image covers entire canvas
                    const scaleX = canvas.width / img.width;
                    const scaleY = canvas.height / img.height;
                    // Use the larger scale to ensure full coverage
                    const scale = Math.max(scaleX, scaleY);
                    
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                        scaleX: scale,
                        scaleY: scale,
                        // Center the image if it's larger than canvas
                        originX: 'center',
                        originY: 'center',
                        left: canvas.width / 2,
                        top: canvas.height / 2
                    });
                });
            };
            reader.readAsDataURL(file);
        });

        $('#add-text').on('click', function() {
            const text = new fabric.IText('Teks Baru', {
                left: 100, top: 100,
                fontFamily: 'Arial', 
                fontSize: 24, 
                fill: '#000000', 
                textAlign: 'center', 
                lineHeight: 1.2,
                // 🎯 IMPROVE TEXT RENDERING
                strokeWidth: 0,
                paintFirst: 'fill'
            });
            text.areaKey = generateAreaKey();
            text.set('areaKey', text.areaKey);
            canvas.add(text);
            canvas.setActiveObject(text);
        });

        $('#remove-element').on('click', () => {
            if (canvas.getActiveObject()) canvas.remove(canvas.getActiveObject());
        });
        

        
        // Canvas selection events for sidebar
        canvas.on('selection:created', (e) => {
            handleCanvasSelection(e, canvas);
        });
        canvas.on('selection:updated', (e) => {
            handleCanvasSelection(e, canvas);
        });
        canvas.on('selection:cleared', () => {
            disableFormattingToolbar();
            window.contextualSidebar.showDefault();
        });
        
        // ✅ Handle text editing mode (double-click text inside group)
        canvas.on('text:editing:entered', (e) => {
            const textObj = e.target;
            if (!textObj) return;
            
            // Enable formatting toolbar for text being edited
            enableFormattingToolbar();
            syncCanvasToToolbar(textObj);
            
            // Show text properties in sidebar
            if (textObj.isPlaceholder && textObj.placeholderType) {
                window.contextualSidebar.showPlaceholder(textObj.placeholderType);
            } else {
                const text = textObj.text;
                $('#sidebar-text #text-content').val(text);
                $('#sidebar-text #text-pos-x').val(Math.round(textObj.left));
                $('#sidebar-text #text-pos-y').val(Math.round(textObj.top));
                
                window.contextualSidebar.hideAll();
                $('#sidebar-text').show();
                $('#sidebar-subtitle').text('Properti: Teks (Edit Mode)');
            }
        });
        
        // ✅ Handle exiting text editing mode
        canvas.on('text:editing:exited', (e) => {
            // Reselect the group if text was inside a group
            const textObj = e.target;
            if (textObj && textObj.group) {
                // Text is part of a group - reselect the group
                setTimeout(() => {
                    canvas.setActiveObject(textObj.group);
                    handleCanvasSelection({ target: textObj.group }, canvas);
                }, 10);
            }
        });
        
        // Force update UI on mouse down (klik objek di canvas)
        canvas.on('mouse:down', (e) => {
            // Option B: allow sub-selection of signature name/title
            // If clicking inside a signature group and a text child is hit, route selection to that child
            if (e && e.subTargets && e.subTargets.length > 0) {
                const child = e.subTargets[0];
                if (child && (child.type === 'i-text' || child.type === 'text' || child.type === 'textbox')) {
                    canvas.setActiveObject(child);
                    handleCanvasSelection({ target: child }, canvas);
                    return;
                }
            }
            if (e.target) {
                handleCanvasSelection({target: e.target}, canvas);
            }
        });

        // =============================
        // SMART GUIDES + SNAPPING (move only)
        // =============================
        if (!window.smartGuides) {
            window.smartGuides = { enabled: true, snapToObjects: true, tolerance: 8, guides: [], temporarilyDisabled: false };
        }

        // Track Ctrl key state for temporary disable
        window.isCtrlPressed = false;

        canvas.on('object:moving', function(e) {
            if (!window.smartGuides || !window.smartGuides.enabled) return;
            
            // Check if temporarily disabled by Ctrl key
            if (window.smartGuides.temporarilyDisabled || window.isCtrlPressed) {
                window.smartGuides.guides = [];
                return;
            }
            
            const target = e.target;
            if (!target) return;
            if (target.selectable === false || target.visible === false) return;

            const snap = computeSnapAdjustment(canvas, target);
            window.smartGuides.guides = [];

            if (snap && (snap.dx || snap.dy)) {
                if (snap.dx) target.left += snap.dx;
                if (snap.dy) target.top += snap.dy;
                target.setCoords();
            }

            if (snap && snap.vLineX !== null && snap.vLineX !== undefined) {
                window.smartGuides.guides.push({ type: 'v', x: snap.vLineX });
            }
            if (snap && snap.hLineY !== null && snap.hLineY !== undefined) {
                window.smartGuides.guides.push({ type: 'h', y: snap.hLineY });
            }
        });

        // Clear guide overlay BEFORE Fabric draws selection/controls
        canvas.on('before:render', function() {
            const cfg = window.smartGuides;
            if (!cfg || !cfg.enabled) return;
            const ctxTop = canvas.contextTop;
            canvas.clearContext(ctxTop);
        });

        // Draw guides on top context
        canvas.on('after:render', function() {
            const cfg = window.smartGuides;
            if (!cfg || !cfg.enabled) return;
            const ctxTop = canvas.contextTop;
            if (!cfg.guides || cfg.guides.length === 0) return;
            ctxTop.save();
            ctxTop.strokeStyle = 'rgba(0, 123, 255, 0.9)';
            ctxTop.lineWidth = 1;
            ctxTop.setLineDash([6, 4]);
            cfg.guides.forEach(g => {
                if (g.type === 'v') {
                    ctxTop.beginPath();
                    ctxTop.moveTo(g.x, 0);
                    ctxTop.lineTo(g.x, canvas.height);
                    ctxTop.stroke();
                } else if (g.type === 'h') {
                    ctxTop.beginPath();
                    ctxTop.moveTo(0, g.y);
                    ctxTop.lineTo(canvas.width, g.y);
                    ctxTop.stroke();
                }
            });
            ctxTop.restore();
        });

        // Clear guides when done
        canvas.on('mouse:up', function() {
            if (window.smartGuides) {
                window.smartGuides.guides = [];
                canvas.renderAll();
            }
        });

        // Keyboard shortcut: Delete selected element via Delete key
        $(document).on('keydown', function(e) {
            // Track Ctrl key state for smart guide temporary disable
            if (e.ctrlKey || e.metaKey) {
                if (!window.isCtrlPressed) {
                    window.isCtrlPressed = true;
                    // Change cursor to indicate snap is disabled
                    if (canvas.getActiveObject()) {
                        canvas.defaultCursor = 'move';
                        canvas.hoverCursor = 'move';
                    }
                }
            }
            
            // =============================
            // KEYBOARD NUDGE: Arrow Keys
            // =============================
            const active = canvas.getActiveObject();
            
            // Arrow key nudging
            if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
                // Ignore when typing in inputs, textareas, selects, or contenteditable elements
                const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                const isFormField = tag === 'input' || tag === 'textarea' || tag === 'select' || $(e.target).prop('contenteditable') === 'true';
                if (isFormField) return;
                
                if (!active) return;
                
                // If editing IText, don't nudge
                if (active.isEditing) return;
                
                e.preventDefault();
                
                // Calculate nudge distance (1px normal, 10px with Shift)
                const nudgeDistance = e.shiftKey ? 10 : 1;
                
                // Get objects to nudge (support multi-select)
                let objectsToNudge = [];
                if (active.type === 'activeSelection' && typeof active.getObjects === 'function') {
                    objectsToNudge = active.getObjects();
                } else {
                    objectsToNudge = [active];
                }
                
                // Apply nudge
                objectsToNudge.forEach(obj => {
                    const currentLeft = obj.left || 0;
                    const currentTop = obj.top || 0;
                    
                    switch(e.key) {
                        case 'ArrowLeft':
                            obj.set('left', Math.max(0, currentLeft - nudgeDistance));
                            break;
                        case 'ArrowRight':
                            obj.set('left', Math.min(canvas.width, currentLeft + nudgeDistance));
                            break;
                        case 'ArrowUp':
                            obj.set('top', Math.max(0, currentTop - nudgeDistance));
                            break;
                        case 'ArrowDown':
                            obj.set('top', Math.min(canvas.height, currentTop + nudgeDistance));
                            break;
                    }
                    
                    obj.setCoords();
                });
                
                // Update activeSelection coordinates if multi-select
                if (active.type === 'activeSelection') {
                    active.setCoords();
                }
                
                canvas.requestRenderAll();
                
                // Trigger object:modified for Undo/Redo
                canvas.fire('object:modified', { target: active });
                
                return;
            }
            
            // Delete key handling
            if (e.key !== 'Delete') return;

            // Ignore when typing in inputs, textareas, selects, or contenteditable elements
            const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
            const isFormField = tag === 'input' || tag === 'textarea' || tag === 'select' || $(e.target).prop('contenteditable') === 'true';
            if (isFormField) return;

            if (!active) return;

            // If editing IText, don't delete; let Delete work inside text editing
            if (active.isEditing) return;

            // Support multi-select deletion
            if (active.type === 'activeSelection' && typeof active.getObjects === 'function') {
                const toRemove = active.getObjects();
                canvas.discardActiveObject();
                toRemove.forEach(obj => canvas.remove(obj));
            } else {
                canvas.remove(active);
                canvas.discardActiveObject();
            }
            canvas.requestRenderAll();
            e.preventDefault();
        });
        
        // Track Ctrl key release
        $(document).on('keyup', function(e) {
            if (!e.ctrlKey && !e.metaKey) {
                if (window.isCtrlPressed) {
                    window.isCtrlPressed = false;
                    // Restore default cursor
                    canvas.defaultCursor = 'default';
                    canvas.hoverCursor = 'move';
                }
            }
        });
    }
    
    function initSidebarEvents(canvas) {
        // Initialize sidebar with default state
        window.contextualSidebar.showDefault();
        
        // 🔄 Toolbar Undo/Redo Integration (bound to compact buttons)
        $('#toolbar-undo').on('click', () => {
            if (window.undoRedoManager) {
                window.undoRedoManager.undo();
            }
        });

        $('#toolbar-redo').on('click', () => {
            if (window.undoRedoManager) {
                window.undoRedoManager.redo();
            }
        });
        
        // Sidebar text content changes
        $(document).on('input', '#text-content', function() {
            const activeObject = canvas.getActiveObject();
            
            // ✅ Support text editing: single text, textbox, or text inside group (IText editing mode)
            if (activeObject && (
                activeObject.type === 'text' || 
                activeObject.type === 'textbox' ||
                activeObject.type === 'i-text' ||
                activeObject.type === 'IText'
            )) {
                activeObject.set('text', $(this).val());
                canvas.renderAll();
            }
        });
        
        // Sidebar position changes
        $(document).on('input', '#text-pos-x, #text-pos-y', function() {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                const x = parseInt($('#text-pos-x').val()) || 0;
                const y = parseInt($('#text-pos-y').val()) || 0;
                activeObject.set({ left: x, top: y });
                canvas.renderAll();
            }
        });
        
        // Minimize panel properti saat klik ikon gear
        $('#sidebar-header-gear').on('click', function() {
            // ✅ PERBAIKAN: Tidak perlu apply manual, karena live update sudah handle
            // Input sudah ter-apply real-time via event handler di updateCanvasPlaceholder()
            
            // Minimize panel
            $('#sidebar-header-full').hide();
            $('#sidebar-content').hide();
            $('#sidebar-header-minimized').show();
        });

        // Restore panel properti saat klik tombol minimized
        $('#sidebar-minimized-btn').on('click', function() {
            $('#sidebar-header-minimized').hide();
            $('#sidebar-header-full').show();
            $('#sidebar-content').show();
        });

        // Event handler klik di luar panel properti untuk minimize
        $(document).on('mousedown', function(e) {
            const sidebar = $('#contextual-sidebar');
            if (sidebar.is(':visible')) {
                // Jika klik di luar sidebar
                if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0) {
                    // ✅ PERBAIKAN: Tidak perlu apply manual
                    // Live update sudah handle semua perubahan real-time
                    
                    // Minimize panel
                    $('#sidebar-header-full').hide();
                    $('#sidebar-content').hide();
                    $('#sidebar-header-minimized').show();
                }
            }
        });
    }
    
    // ========================================
    // FLOATING TOOLBAR MANAGEMENT (HYBRID PREVIEW)
    // ========================================
    function initFloatingFormattingPreview(canvas) {
        const container = $('#floating-toolbar');
        if (!container.length) return;

        ensureFloatingPopover();
        initFloatingFormattingControls(canvas);

        const updateHandler = () => updateFloatingFormattingPreview(canvas);

        canvas.on('selection:created', updateHandler);
        canvas.on('selection:updated', updateHandler);
        canvas.on('object:moving', updateHandler);
        canvas.on('object:scaling', updateHandler);
        canvas.on('object:rotating', updateHandler);
        canvas.on('object:modified', updateHandler);
        canvas.on('selection:cleared', () => {
            container.hide();
            clearFloatingColorChip();
            closeFloatingPopover();
        });
        canvas.on('mouse:wheel', () => {
            if (canvas.getActiveObject()) {
                setTimeout(updateHandler, 50);
            }
        });

        // Bind align within group toggle
        $('#align-within-group-checkbox').on('change', function() {
            const isEnabled = $(this).is(':checked');
            window.alignWithinGroupMode = isEnabled;

            if (isEnabled) {
                $('#align-mode-toggle').addClass('active');
            } else {
                $('#align-mode-toggle').removeClass('active');
            }

            updateFloatingFormattingPreview(canvas);
        });

        $('#floating-bold, #floating-italic, #floating-underline')
            .off('click')
            .on('click', function(e) {
                e.preventDefault();
                const action = $(this).attr('id').replace('floating-', '');
                handleFormattingAction(canvas, action);
            });

        $('.floating-align-button')
            .off('click')
            .on('click', function(e) {
                e.preventDefault();
                const direction = $(this).data('align');
                handleFloatingAlign(canvas, direction);
            });
    }

    function initFloatingColorDropdown() {
        const host = $('#floating-font-color');
        if (!host.length) return;

        const trigger = $('#floating-color-trigger');
        const dropdown = $('#floating-color-dropdown');
        const tabs = $('#floating-color-advanced .color-tab');
        const nativeInput = $('#floating-color-input');
        const swatchContainer = $('#floating-color-swatches');
        const advancedToggle = $('#floating-color-advanced-toggle');
        const advancedPanel = $('#floating-color-advanced');
        const advancedInput = $('#floating-color-display');

        dropdown.attr('aria-hidden', 'true');
        updateAdvancedToggleUI();
        buildFloatingColorSwatches();

        trigger.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            closeFloatingPopover();

            const willOpen = !host.hasClass('open');
            host.toggleClass('open', willOpen);
            dropdown.attr('aria-hidden', willOpen ? 'false' : 'true');

            if (willOpen) {
                renderFloatingColorDisplay();
            }
        });

        dropdown.on('click', function(e) {
            e.stopPropagation();
        });

        tabs.on('click', function(e) {
            e.preventDefault();

            const format = $(this).data('format');
            if (!format) return;

            floatingColorState.format = format;
            renderFloatingColorDisplay();
        });

        nativeInput.on('input change', function() {
            if (!this.value) return;
            applyFloatingColor(this.value);
        });

        swatchContainer.on('click', '.color-swatch', function(e) {
            e.preventDefault();
            const color = $(this).data('color');
            if (!color) return;
            applyFloatingColor(color);
        });

        advancedToggle.on('click', function(e) {
            e.preventDefault();
            floatingColorState.advancedOpen = !floatingColorState.advancedOpen;
            updateAdvancedToggleUI();
            renderFloatingColorDisplay();
        });

        const applyAdvancedInput = () => {
            applyAdvancedColorInput(advancedInput.val());
        };

        advancedInput.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyAdvancedInput();
            }
        });

        advancedInput.on('blur', function() {
            applyAdvancedInput();
        });

        $(document).on('click.floatingColor', function(e) {
            if (!host.is(e.target) && host.has(e.target).length === 0) {
                closeFloatingColorDropdown();
            }
        });

        $(document).on('keydown.floatingColor', function(e) {
            if (e.key === 'Escape') {
                closeFloatingColorDropdown();
            }
        });
    }

    function ensureFloatingPopover() {
        let popover = $('#floating-control-popover');
        if (!popover.length) {
            popover = $('<div id="floating-control-popover" class="floating-popover" aria-hidden="true"></div>');
            $('body').append(popover);
            // Prevent clicks/focus inside the popover from bubbling to document handlers
            popover.on('mousedown.floatingPopover click.floatingPopover touchstart.floatingPopover focusin.floatingPopover', function(e) {
                e.stopPropagation();
            });
        }

        if (!window.floatingPopoverDocEventsBound) {
            // Close the floating popover when clicking outside. Interactions inside the popover
            // stop propagation, so clicks inside won't accidentally close it.
            $(document).on('click.floatingPopover', function(e) {
                const activePopover = $('#floating-control-popover');
                if (!activePopover.length || !activePopover.hasClass('active')) return;

                const anchor = floatingPopoverState.anchor;
                if (anchor && (anchor === e.target || $.contains(anchor, e.target))) {
                    return;
                }

                if (activePopover.is(e.target) || activePopover.has(e.target).length) {
                    return;
                }

                closeFloatingPopover();
            });

            // Keep Escape key working to close quickly.
            $(document).on('keydown.floatingPopover', function(e) {
                if (e.key === 'Escape') {
                    closeFloatingPopover();
                }
            });

            window.floatingPopoverDocEventsBound = true;
        }

        return popover;
    }

    function initFloatingFormattingControls(canvas) {
        const popover = ensureFloatingPopover();
        const selectors = '#floating-font-family, #floating-font-size, #floating-line-height';

        $(selectors).off('click.floatingControls').on('click.floatingControls', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const id = this.id;
            let type = null;
            if (id === 'floating-font-family') type = 'fontFamily';
            if (id === 'floating-font-size') type = 'fontSize';
            if (id === 'floating-line-height') type = 'lineHeight';
            if (!type) return;

            toggleFloatingPopover(type, this, canvas);
        });

        popover.off('click.floatingControls', '.option-button').on('click.floatingControls', '.option-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const action = $(this).data('action');
            const value = $(this).data('value');
            const targetCanvas = floatingPopoverState.canvas;
            if (!targetCanvas || !action) return;

            if (action === 'font-family') {
                applyFloatingFontFamily(targetCanvas, value);
            } else if (action === 'font-size') {
                applyFloatingFontSize(targetCanvas, parseFloat(value));
            } else if (action === 'line-height') {
                applyFloatingLineHeight(targetCanvas, parseFloat(value));
            }
        });
        // Note: 'Terapkan' button removed — commit happens on Enter or when popover closes.
        // Handle enter key to apply
        popover.off('keydown.floatingControls', 'input').on('keydown.floatingControls', 'input', function(e) {
            if (e.key !== 'Enter') return;

            const targetCanvas = floatingPopoverState.canvas;
            if (!targetCanvas) return;

            e.preventDefault();
            const id = this.id;
            const value = parseFloat($(this).val());
            if (id === 'floating-font-size-input') {
                applyFloatingFontSize(targetCanvas, value);
            } else if (id === 'floating-line-height-input') {
                applyFloatingLineHeight(targetCanvas, value);
            }
        });

        // Typing in the numeric input will NOT preview immediately to avoid
        // interfering with user editing (they can clear and type freely).
        // Commit will occur on Enter or when the popover is closed (click outside / preset selection).

        // Select input on focus and allow free deletion: remove min while editing to avoid browser-enforced clamping
        popover.off('focusin.floatingControls', 'input').on('focusin.floatingControls', 'input', function(e) {
            try { $(this).select(); } catch (err) {}
            // temporarily remove min so user can clear the field
            const id = this.id;
            if (id === 'floating-font-size-input') $(this).removeAttr('min');
            if (id === 'floating-line-height-input') $(this).removeAttr('min');
        });

        popover.off('blur.floatingControls', 'input').on('blur.floatingControls', 'input', function(e) {
            const id = this.id;
            if (id === 'floating-font-size-input') $(this).attr('min', 6);
            if (id === 'floating-line-height-input') $(this).attr('min', 0.5);
        });

        // +/- spin buttons inside popover (increment/decrement the input and preview)
        popover.off('click.floatingControls', '.input-row .spin-button').on('click.floatingControls', '.input-row .spin-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            const dir = $btn.data('dir'); // 'up' or 'down'
            const $input = $btn.siblings('input');
            if (!$input.length) return;

            let val = parseFloat($input.val()) || 0;
            const step = parseFloat($input.attr('step')) || 1;
            if (dir === 'up') val = val + step; else val = val - step;
            const min = parseFloat($input.attr('min'));
            const max = parseFloat($input.attr('max'));
            if (!isNaN(min)) val = Math.max(val, min);
            if (!isNaN(max)) val = Math.min(val, max);

            $input.val(val);

            const targetCanvas = floatingPopoverState.canvas;
            if (!targetCanvas) return;

            if ($input.attr('id') === 'floating-font-size-input') {
                previewFloatingFontSize(targetCanvas, val);
            } else if ($input.attr('id') === 'floating-line-height-input') {
                previewFloatingLineHeight(targetCanvas, val);
            }
        });
    }

    function toggleFloatingPopover(type, anchor, canvas) {
        if (floatingPopoverState.isOpen) {
            if (floatingPopoverState.type === type && floatingPopoverState.anchor === anchor) {
                closeFloatingPopover();
                return;
            }
            closeFloatingPopover();
        }

        closeFloatingColorDropdown();
        openFloatingPopover(type, anchor, canvas);
    }

    function openFloatingPopover(type, anchor, canvas) {
        const popover = ensureFloatingPopover();
        floatingPopoverState.isOpen = true;
        floatingPopoverState.type = type;
        floatingPopoverState.anchor = anchor;
        floatingPopoverState.canvas = canvas;

        renderFloatingPopover(type, canvas);

        const $anchor = $(anchor);
        $anchor.addClass('open');
        popover.detach().appendTo($anchor);

        popover.addClass('active').attr('aria-hidden', 'false');
    }

    function closeFloatingPopover() {
        const popover = $('#floating-control-popover');
        if (!popover.length) return;
        // If popover is open and contains a pending valid numeric input, commit it
        if (floatingPopoverState.isOpen && !floatingPopoverState.autoApplying) {
            const type = floatingPopoverState.type;
            const canvas = floatingPopoverState.canvas;
            if (type === 'fontSize') {
                const input = $('#floating-font-size-input');
                if (input.length) {
                    const val = parseFloat(input.val());
                    if (!isNaN(val)) {
                        floatingPopoverState.autoApplying = true;
                        applyFloatingFontSize(canvas, val);
                        floatingPopoverState.autoApplying = false;
                        return; // applyFloatingFontSize will call closeFloatingPopover again
                    }
                }
            } else if (type === 'lineHeight') {
                const input = $('#floating-line-height-input');
                if (input.length) {
                    const val = parseFloat(input.val());
                    if (!isNaN(val)) {
                        floatingPopoverState.autoApplying = true;
                        applyFloatingLineHeight(canvas, val);
                        floatingPopoverState.autoApplying = false;
                        return; // applyFloatingLineHeight will call closeFloatingPopover again
                    }
                }
            }
        }

        const anchor = floatingPopoverState.anchor;
        if (anchor && anchor.classList) {
            anchor.classList.remove('open');
        }

        popover.removeClass('active').attr('aria-hidden', 'true');
        popover.detach().appendTo('body');

        floatingPopoverState.isOpen = false;
        floatingPopoverState.type = null;
        floatingPopoverState.anchor = null;
        floatingPopoverState.canvas = null;
    }

    function renderFloatingPopover(type, canvas) {
        const popover = ensureFloatingPopover();
        popover.removeAttr('data-type');
        popover.attr('data-type', type);
        popover.empty();

        const textObjects = getActiveTextObjects(canvas);
        if (!textObjects.length) {
            popover.append($('<div class="popover-note">').text('Pilih objek teks untuk mengubah properti.'));
            return;
        }

        if (type === 'fontFamily') {
            renderFontFamilyPopover(popover, textObjects);
        } else if (type === 'fontSize') {
            renderFontSizePopover(popover, textObjects);
        } else if (type === 'lineHeight') {
            renderLineHeightPopover(popover, textObjects);
        }
    }

    function renderFontFamilyPopover(popover, textObjects) {
        const commonFont = getCommonPropertyValue(textObjects, 'fontFamily');
        const note = commonFont ? `Saat ini: ${commonFont}` : 'Saat ini campuran';

        popover.append($('<div class="popover-title">').text('Font Family'));
        popover.append($('<div class="popover-note">').text(note));

        const searchInput = $('<input type="text" class="search-input" placeholder="Cari font…" autocomplete="off">');
        popover.append(searchInput);

        if (floatingRecentFonts.length > 0) {
            popover.append($('<div class="recent-heading">').text('Font Terakhir'));
            const recentList = $('<div class="recent-list">');
            floatingRecentFonts.forEach(font => {
                const button = $('<button type="button" class="option-button font-option">')
                    .attr('data-action', 'font-family')
                    .attr('data-value', font)
                    .text(font);
                if (commonFont && font.toLowerCase() === commonFont.toLowerCase()) {
                    button.addClass('active');
                }
                recentList.append(button);
            });
            popover.append(recentList);
        }

        popover.append($('<div class="all-heading">').text('Semua Font'));

        const section = $('<div class="popover-section">');
        const grid = $('<div class="option-grid font-list">');

        const activeFont = commonFont ? commonFont.toLowerCase() : null;
        FLOATING_FONT_FAMILIES.forEach(font => {
            const button = $('<button type="button" class="option-button font-option">')
                .attr('data-action', 'font-family')
                .attr('data-value', font)
                .append($('<span class="sample">').text(font).css('font-family', font));
            if (activeFont && activeFont === font.toLowerCase()) {
                button.addClass('active');
            }
            grid.append(button);
        });

        section.append(grid);
        popover.append(section);

        const applyFilter = (term) => {
            const lower = (term || '').trim().toLowerCase();
            grid.children('button').each(function() {
                const text = ($(this).text() || '').toLowerCase();
                $(this).toggle(!lower || text.includes(lower));
            });
        };

        searchInput.on('input', function() {
            applyFilter($(this).val());
        });

        searchInput.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const term = $(this).val();
                const lower = (term || '').trim().toLowerCase();
                let targetFont = null;

                if (floatingRecentFonts.length) {
                    targetFont = floatingRecentFonts.find(font => font.toLowerCase().includes(lower));
                }

                if (!targetFont) {
                    targetFont = FLOATING_FONT_FAMILIES.find(font => font.toLowerCase().includes(lower));
                }

                if (targetFont) {
                    applyFloatingFontFamily(floatingPopoverState.canvas, targetFont);
                }
            }
        });

        setTimeout(() => searchInput.trigger('focus'), 10);
    }

    function renderFontSizePopover(popover, textObjects) {
        const commonSize = getCommonFontSize(textObjects);
        const note = (commonSize === null || commonSize === undefined)
            ? 'Saat ini campuran'
            : `Saat ini: ${commonSize}px`;

        popover.append($('<div class="popover-title">').text('Ukuran Font'));
        popover.append($('<div class="popover-note">').text(note));

        const quickSection = $('<div class="popover-section">');
        const grid = $('<div class="option-grid">');

        FLOATING_FONT_SIZE_PRESETS.forEach(size => {
            const button = $('<button type="button" class="option-button">')
                .attr('data-action', 'font-size')
                .attr('data-value', size)
                .text(`${size}px`);
            if (commonSize === size) {
                button.addClass('active');
            }
            grid.append(button);
        });

        quickSection.append(grid);
        popover.append(quickSection);

        const inputSection = $('<div class="popover-section">');
        const inputRow = $('<div class="input-row">');

        // Build spin control: [-] [input] [+]
        const spinGroup = $('<div class="input-group" style="width:100%;">');
    const decBtn = $('<button type="button" class="spin-button" data-dir="down" aria-label="Kurangi ukuran font">−</button>');

    const input = $('<input type="number" min="6" max="200" id="floating-font-size-input" step="1" class="form-control text-center">').attr('inputmode','numeric');
        if (typeof commonSize === 'number' && !Number.isNaN(commonSize)) {
            input.val(commonSize);
        } else {
            input.attr('placeholder', 'Mixed');
        }

    const incBtn = $('<button type="button" class="spin-button" data-dir="up" aria-label="Tambah ukuran font">+</button>');

        spinGroup.append(decBtn, input, incBtn);

    inputRow.append(spinGroup);
        inputSection.append(inputRow);
        popover.append(inputSection);
    }

    function renderLineHeightPopover(popover, textObjects) {
        let commonLineHeight = getCommonPropertyValue(textObjects, 'lineHeight');
        if (commonLineHeight === undefined) {
            commonLineHeight = null;
        }

        const note = (commonLineHeight === null)
            ? 'Saat ini campuran'
            : `Saat ini: ${formatLineHeight(commonLineHeight)}`;

        popover.append($('<div class="popover-title">').text('Spasi Baris'));
        popover.append($('<div class="popover-note">').text(note));

        const quickSection = $('<div class="popover-section">');
        const grid = $('<div class="option-grid">');

        FLOATING_LINE_HEIGHT_PRESETS.forEach(value => {
            const button = $('<button type="button" class="option-button">')
                .attr('data-action', 'line-height')
                .attr('data-value', value)
                .text(formatLineHeight(value));
            if (commonLineHeight !== null && Math.abs(value - commonLineHeight) < 0.001) {
                button.addClass('active');
            }
            grid.append(button);
        });

        quickSection.append(grid);
        popover.append(quickSection);

        const inputSection = $('<div class="popover-section">');
        const inputRow = $('<div class="input-row">');

        const spinGroup = $('<div class="input-group" style="width:100%;">');
    const decBtn = $('<button type="button" class="spin-button" data-dir="down" aria-label="Kurangi spasi baris">−</button>');

    const input = $('<input type="number" min="0.5" max="3" step="0.05" id="floating-line-height-input" class="form-control text-center">').attr('inputmode','numeric');
        if (commonLineHeight !== null && commonLineHeight !== undefined) {
            input.val(parseFloat(commonLineHeight).toFixed(2).replace(/\.00$/, ''));
        } else {
            input.attr('placeholder', 'Mixed');
        }

    const incBtn = $('<button type="button" class="spin-button" data-dir="up" aria-label="Tambah spasi baris">+</button>');

        spinGroup.append(decBtn, input, incBtn);

    inputRow.append(spinGroup);
        inputSection.append(inputRow);
        popover.append(inputSection);
    }

    function applyFloatingFontFamily(canvas, fontFamily) {
        if (!canvas || !fontFamily) return;
        const active = canvas.getActiveObject();
        if (!active) return;

        updateFloatingRecentFonts(fontFamily);

        applyToAllTextObjects(active, (textObj) => {
            textObj.set('fontFamily', fontFamily);
        });

        canvas.requestRenderAll();
        canvas.fire('object:modified', { target: active });
        closeFloatingPopover();

        setTimeout(() => {
            const current = canvas.getActiveObject() || active;
            if (current) {
                syncCanvasToToolbar(current);
                updateFloatingFormattingPreview(canvas);
            }
        }, 10);
    }

    function applyFloatingFontSize(canvas, sizeValue) {
        if (!canvas) return;
        const active = canvas.getActiveObject();
        if (!active) return;

        const parsed = parseInt(sizeValue, 10);
        if (Number.isNaN(parsed)) return;

        const constrained = Math.max(6, Math.min(200, parsed));

        applyToAllTextObjects(active, (textObj) => {
            const currentScale = textObj.scaleY || 1;
            textObj.set({
                fontSize: constrained / currentScale,
                scaleY: currentScale
            });
        });

        canvas.requestRenderAll();
        canvas.fire('object:modified', { target: active });
        closeFloatingPopover();

        setTimeout(() => {
            const current = canvas.getActiveObject() || active;
            if (current) {
                syncCanvasToToolbar(current);
                updateFloatingFormattingPreview(canvas);
            }
        }, 10);
    }

    function applyFloatingLineHeight(canvas, rawValue) {
        if (!canvas) return;
        const active = canvas.getActiveObject();
        if (!active) return;

        const value = parseFloat(rawValue);
        if (Number.isNaN(value)) return;

        const constrained = Math.max(0.5, Math.min(3, value));

        applyToAllTextObjects(active, (textObj) => {
            textObj.set('lineHeight', constrained);
        });

        canvas.requestRenderAll();
        canvas.fire('object:modified', { target: active });
        closeFloatingPopover();

        setTimeout(() => {
            const current = canvas.getActiveObject() || active;
            if (current) {
                syncCanvasToToolbar(current);
                updateFloatingFormattingPreview(canvas);
            }
        }, 10);
    }

    // Live preview helpers: apply changes visually WITHOUT closing the popover or firing final object:modified
    function previewFloatingFontSize(canvas, sizeValue) {
        if (!canvas) return;
        const active = canvas.getActiveObject();
        if (!active) return;

        const parsed = parseInt(sizeValue, 10);
        if (Number.isNaN(parsed)) return;

        const constrained = Math.max(6, Math.min(200, parsed));

        applyToAllTextObjects(active, (textObj) => {
            const currentScale = textObj.scaleY || 1;
            textObj.set({
                fontSize: constrained / currentScale,
                scaleY: currentScale
            });
        });

        canvas.requestRenderAll();

        setTimeout(() => {
            const current = canvas.getActiveObject() || active;
            if (current) {
                syncCanvasToToolbar(current);
                updateFloatingFormattingPreview(canvas);
            }
        }, 10);
    }

    function previewFloatingLineHeight(canvas, rawValue) {
        if (!canvas) return;
        const active = canvas.getActiveObject();
        if (!active) return;

        const value = parseFloat(rawValue);
        if (Number.isNaN(value)) return;

        const constrained = Math.max(0.5, Math.min(3, value));

        applyToAllTextObjects(active, (textObj) => {
            textObj.set('lineHeight', constrained);
        });

        canvas.requestRenderAll();

        setTimeout(() => {
            const current = canvas.getActiveObject() || active;
            if (current) {
                syncCanvasToToolbar(current);
                updateFloatingFormattingPreview(canvas);
            }
        }, 10);
    }

    function updateFloatingRecentFonts(fontFamily) {
        if (!fontFamily) return;
        const normalized = fontFamily.toLowerCase();
        floatingRecentFonts = floatingRecentFonts.filter(font => font.toLowerCase() !== normalized);
        floatingRecentFonts.unshift(fontFamily);
        if (floatingRecentFonts.length > 2) {
            floatingRecentFonts = floatingRecentFonts.slice(0, 2);
        }
    }

    function getActiveTextObjects(canvas) {
        if (!canvas) return [];
        const active = canvas.getActiveObject();
        if (!active) return [];

        if (isTextObject(active)) {
            return [active];
        }

        return getTextObjectsFromSelection(active);
    }

    function isElementInDocument(element) {
        if (!element) return false;
        return document.body.contains(element);
    }

    function closeFloatingColorDropdown() {
        const host = $('#floating-font-color');
        const dropdown = $('#floating-color-dropdown');
        if (!host.length) return;

        host.removeClass('open');
        if (dropdown.length) {
            dropdown.attr('aria-hidden', 'true');
        }

        $('#floating-color-display').removeClass('is-invalid');
    }

    function renderFloatingColorDisplay() {
        const dropdown = $('#floating-color-dropdown');
        if (!dropdown.length) return;

        buildFloatingColorSwatches();

        const label = $('#floating-color-label');
        const advancedInput = $('#floating-color-display');
        const helper = $('#floating-color-helper');
        const tabs = $('#floating-color-advanced .color-tab');
        const nativeInput = $('#floating-color-input');

        const isMixed = !!floatingColorState.isMixed;
        const format = floatingColorState.format || 'hex';
        const hexValue = floatingColorState.currentHex || '#000000';

        nativeInput.val(hexValue);

        if (isMixed) {
            highlightFloatingColorSwatch(null);
        } else {
            highlightFloatingColorSwatch(hexValue);
        }

        tabs.removeClass('active');
        const activeTab = tabs.filter(`[data-format="${format}"]`);
        if (activeTab.length) {
            activeTab.addClass('active');
        }

        if (isMixed) {
            label.text('MIXED');
            advancedInput.val('');
            advancedInput.attr('placeholder', 'Masukkan nilai baru untuk menyamakan warna');
            helper.text('Pilih warna atau isi format lanjutan untuk menerapkan ke semua objek.');
        } else {
            label.text(format.toUpperCase());
            advancedInput.attr('placeholder', '');
            const targetValue = floatingColorState.values[format] || floatingColorState.values.hex || hexValue;
            advancedInput.val(targetValue);
            helper.text('Gunakan format sesuai tab aktif lalu tekan Enter.');
        }

        advancedInput.removeClass('is-invalid');
        updateAdvancedToggleUI();
    }

    function buildFloatingColorSwatches() {
        const container = $('#floating-color-swatches');
        if (!container.length || floatingColorState.swatchesInitialized) return;

        FLOATING_QUICK_COLORS.forEach(colorHex => {
            const hex = normalizeHexColor(colorHex);
            const button = $('<button type="button" class="color-swatch">')
                .attr('data-color', hex)
                .attr('title', hex)
                .attr('aria-label', `Pilih warna ${hex}`)
                .css('background', hex);
            container.append(button);
        });

        floatingColorState.swatchesInitialized = true;
    }

    function highlightFloatingColorSwatch(hexColor) {
        const swatches = $('#floating-color-swatches .color-swatch');
        if (!swatches.length) return;

        swatches.removeClass('active');
        if (!hexColor) return;

        const normalized = normalizeHexColor(hexColor);
        swatches.filter(function() {
            return normalizeHexColor($(this).data('color')) === normalized;
        }).addClass('active');
    }

    function updateAdvancedToggleUI() {
        const advancedPanel = $('#floating-color-advanced');
        const toggle = $('#floating-color-advanced-toggle');

        const isOpen = !!floatingColorState.advancedOpen;
        advancedPanel.toggleClass('open', isOpen);
        if (toggle.length) {
            toggle.text(isOpen ? 'Sembunyikan Format Lanjutan' : 'Format Lanjutan');
        }
    }

    function applyFloatingColor(colorValue) {
        const canvas = window.canvas;
        if (!canvas) return;

        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;

        let hex;
        try {
            hex = parseColorToHex(colorValue);
        } catch (err) {
            return;
        }

        const hasTextSelection = isTextObject(activeObject) || activeObject.type === 'activeSelection' || activeObject.type === 'group';
        if (!hasTextSelection) return;

        applyToAllTextObjects(activeObject, (textObj) => {
            textObj.set('fill', hex);
        });

        canvas.requestRenderAll();

        floatingColorState.isMixed = false;
        updateFloatingColorValuesFromHex(hex);
        renderFloatingColorDisplay();

        syncCanvasToToolbar(activeObject);
        canvas.fire('object:modified', { target: activeObject });
    }

    function applyAdvancedColorInput(rawValue) {
        const input = $('#floating-color-display');
        if (!input.length) return;

        const value = (rawValue || '').trim();
        if (!value) {
            input.removeClass('is-invalid');
            return;
        }

        const format = floatingColorState.format || 'hex';
        let candidate = value;

        if (format === 'hex' && !candidate.startsWith('#')) {
            candidate = `#${candidate}`;
        } else if (format === 'rgb' && !candidate.toLowerCase().startsWith('rgb')) {
            candidate = candidate.includes(',') ? `rgb(${candidate})` : candidate;
        } else if (format === 'hsl' && !candidate.toLowerCase().startsWith('hsl')) {
            candidate = candidate.includes(',') ? `hsl(${candidate})` : candidate;
        }

        try {
            const hex = parseColorToHex(candidate);
            applyFloatingColor(hex);
            input.removeClass('is-invalid');
        } catch (err) {
            input.addClass('is-invalid');
        }
    }

    function updateFloatingColorValuesFromHex(hex) {
        let source = null;
        if (window.fabric && fabric.Color) {
            try {
                source = new fabric.Color(hex).getSource();
            } catch (err) {
                source = null;
            }
        }

        if (!Array.isArray(source)) {
            source = [0, 0, 0, 1];
        }

        floatingColorState.currentHex = hex;
        floatingColorState.values = {
            hex,
            rgb: formatRgbStringFromSource(source),
            hsl: formatHslStringFromSource(source)
        };
    }

    function parseColorToHex(value) {
        if (typeof value !== 'string') throw new Error('Invalid color');
        let candidate = value.trim();
        if (!candidate) throw new Error('Invalid color');

        const simpleHex = candidate.match(/^#?[0-9a-fA-F]{3,6}$/);
        if (simpleHex) {
            if (!candidate.startsWith('#')) {
                candidate = `#${candidate}`;
            }
            if (candidate.length === 4) {
                candidate = `#${candidate[1]}${candidate[1]}${candidate[2]}${candidate[2]}${candidate[3]}${candidate[3]}`;
            }
            return candidate.toUpperCase();
        }

        if (!candidate.startsWith('#') && !candidate.toLowerCase().startsWith('rgb') && !candidate.toLowerCase().startsWith('hsl')) {
            if (candidate.includes(',')) {
                candidate = `rgb(${candidate})`;
            } else {
                candidate = `#${candidate}`;
            }
        }

        if (window.fabric && fabric.Color) {
            const parsed = new fabric.Color(candidate);
            return `#${parsed.toHex().toUpperCase()}`;
        }

        if (/^#([0-9a-fA-F]{6})$/.test(candidate)) {
            return candidate.toUpperCase();
        }

        throw new Error('Invalid color');
    }

    function normalizeHexColor(value) {
        try {
            return parseColorToHex(value);
        } catch (err) {
            return '#000000';
        }
    }

    function formatRgbStringFromSource(source) {
        if (!Array.isArray(source) || source.length < 3) return 'rgb(0, 0, 0)';
        const [r, g, b, a] = source;
        const clamp = (value) => Math.max(0, Math.min(255, Math.round(value)));
        const rr = clamp(r);
        const gg = clamp(g);
        const bb = clamp(b);

        if (typeof a === 'number' && a >= 0 && a < 1) {
            return `rgba(${rr}, ${gg}, ${bb}, ${parseFloat(a.toFixed(2))})`;
        }
        return `rgb(${rr}, ${gg}, ${bb})`;
    }

    function formatHslStringFromSource(source) {
        if (!Array.isArray(source) || source.length < 3) return 'hsl(0, 0%, 0%)';
        const [r, g, b, a] = source;

        const rr = Math.max(0, Math.min(255, r)) / 255;
        const gg = Math.max(0, Math.min(255, g)) / 255;
        const bb = Math.max(0, Math.min(255, b)) / 255;

        const max = Math.max(rr, gg, bb);
        const min = Math.min(rr, gg, bb);
        let h = 0;
        let s = 0;
        const l = (max + min) / 2;

        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

            switch (max) {
                case rr:
                    h = (gg - bb) / d + (gg < bb ? 6 : 0);
                    break;
                case gg:
                    h = (bb - rr) / d + 2;
                    break;
                case bb:
                    h = (rr - gg) / d + 4;
                    break;
            }

            h /= 6;
        }

        const hh = Math.round(h * 360);
        const ss = Math.round(s * 100);
        const ll = Math.round(l * 100);

        if (typeof a === 'number' && a >= 0 && a < 1) {
            return `hsla(${hh}, ${ss}%, ${ll}%, ${parseFloat(a.toFixed(2))})`;
        }
        return `hsl(${hh}, ${ss}%, ${ll}%)`;
    }

    function updateFloatingFormattingPreview(canvas) {
    const container = $('#floating-toolbar');
    if (!container.length) return;

        const activeObject = canvas.getActiveObject();

        if (!activeObject) {
            container.hide();
            closeFloatingPopover();
            return;
        }

        const type = activeObject.type;
        const isMultiSelect = type === 'activeSelection';
        const isGroup = type === 'group';
        const isCustomGroup = isGroup && activeObject.isCustomGroup;
        const isSignatureGroup = isGroup && activeObject.isSignatureBlock;
        const supportsGrouping = isMultiSelect || isCustomGroup;

        let textObjects = [];
        if (isMultiSelect || isGroup) {
            textObjects = getTextObjectsFromSelection(activeObject);
        } else if (isTextObject(activeObject)) {
            textObjects = [activeObject];
        }

        const hasText = textObjects.length > 0;

        if (!hasText && !supportsGrouping) {
            container.hide();
            closeFloatingPopover();
            return;
        }

    const formattingGroup = $('#floating-formatting-group');
    const groupRow = $('#floating-group-row');
    const groupDivider = $('#floating-row-divider');
    const groupActions = $('#floating-group-actions');
    const infoLabel = $('#floating-toolbar-info');
    const alignHorizontalGroup = $('#floating-align-horizontal');
    const alignVerticalGroup = $('#floating-align-vertical');
    const alignVerticalDivider = container.find('.align-vertical-divider');
    const alignToggle = $('#align-mode-toggle');
    const alignCheckbox = $('#align-within-group-checkbox');
    const alignContext = resolveFloatingAlignContext(activeObject, textObjects);
    const textAlignTitles = {
        left: 'Rata teks kiri',
        center: 'Rata teks tengah',
        right: 'Rata teks kanan',
    };
    const objectAlignTitles = {
        left: 'Sejajarkan objek ke kiri',
        center: 'Sejajarkan objek ke tengah horizontal',
        right: 'Sejajarkan objek ke kanan',
    };
    const verticalAlignTitles = {
        top: 'Sejajarkan objek ke atas',
        middle: 'Sejajarkan objek ke tengah vertikal',
        bottom: 'Sejajarkan objek ke bawah',
    };

    window.floatingAlignMode = alignContext.mode;
    container.attr('data-align-mode', alignContext.mode);
    alignVerticalGroup.toggleClass('is-hidden', !alignContext.showVertical);
    alignVerticalDivider.toggleClass('is-hidden', !alignContext.showVertical);

    const isSignatureAlignMode = alignContext.mode === 'signature-text';
    const horizontalTitleMap = (alignContext.mode === 'text' || isSignatureAlignMode) ? textAlignTitles : objectAlignTitles;
    ['left', 'center', 'right'].forEach(pos => {
        const btn = $(`#floating-align-${pos}`);
        btn.attr('title', horizontalTitleMap[pos]);
        if (alignContext.mode !== 'text') {
            btn.removeClass('active mixed');
        }
    });

    ['top', 'middle', 'bottom'].forEach(pos => {
        const btn = $(`#floating-align-${pos}`);
        btn.attr('title', verticalAlignTitles[pos]);
        if (alignContext.mode !== 'object') {
            btn.removeClass('active mixed');
        }
    });

        if (hasText) {
            formattingGroup.removeClass('is-hidden');
            const label = textObjects.length === 1 ? '1 objek teks dipilih' : `${textObjects.length} objek teks dipilih`;
            infoLabel.text(label);

            const commonFont = getCommonPropertyValue(textObjects, 'fontFamily');
            setFloatingChip('#floating-font-family', commonFont, value => value || 'Mixed');

            const commonFontSize = getCommonFontSize(textObjects);
            setFloatingChip('#floating-font-size', commonFontSize, value => (value === null || value === undefined) ? 'Mixed' : `${value}`);

            const commonLineHeight = getCommonPropertyValue(textObjects, 'lineHeight');
            setFloatingChip('#floating-line-height', commonLineHeight, value => formatLineHeight(value));

            const commonColor = getCommonPropertyValue(textObjects, 'fill');
            setFloatingColorChip('#floating-font-color', commonColor);

            const isBold = getCommonBooleanProperty(textObjects, 'fontWeight', 'bold');
            const isItalic = getCommonBooleanProperty(textObjects, 'fontStyle', 'italic');
            const isUnderline = getCommonBooleanProperty(textObjects, 'underline', true);

            updatePreviewToggle('#floating-bold', isBold);
            updatePreviewToggle('#floating-italic', isItalic);
            updatePreviewToggle('#floating-underline', isUnderline);

            if (alignContext.mode === 'text' || isSignatureAlignMode) {
                const commonAlign = getCommonPropertyValue(textObjects, 'textAlign');
                ['left', 'center', 'right'].forEach(pos => {
                    const btn = $(`#floating-align-${pos}`);
                    btn.removeClass('active mixed');
                    if (commonAlign === pos) {
                        btn.addClass('active');
                    } else if (commonAlign === null || commonAlign === undefined) {
                        btn.addClass('mixed');
                    }
                });
            }
        } else {
            formattingGroup.addClass('is-hidden');
            const count = isMultiSelect
                ? (activeObject._objects ? activeObject._objects.length : (activeObject.size ? activeObject.size() : 0))
                : (activeObject._objects ? activeObject._objects.length : 1);
            infoLabel.text(`${count} objek dipilih`);

            ['#floating-font-family', '#floating-font-size', '#floating-line-height'].forEach(selector => {
                const chip = $(selector);
                chip.removeClass('mixed');
                chip.find('.value').text('—');
            });

            clearFloatingColorChip();

            ['#floating-bold', '#floating-italic', '#floating-underline', '#floating-align-left', '#floating-align-center', '#floating-align-right']
                .forEach(selector => $(selector).removeClass('active mixed'));
        }

    // Show group/action row for multi-selects and custom groups. Also allow
    // signature blocks to expose group-related actions (Option B) while
    // keeping them protected from ungrouping.
    const groupRowVisible = supportsGrouping || isSignatureGroup;
        groupRow.toggleClass('active', groupRowVisible);
        groupDivider.toggleClass('active', groupRowVisible);

        if (groupRowVisible) {
            alignToggle.attr('title', 'Align Within Group').removeClass('readonly');
            alignCheckbox
                .removeAttr('data-signature-readonly')
                .prop('disabled', false);

            if (isMultiSelect) {
                $('#float-group').show();
                $('#float-ungroup').hide().prop('disabled', false).removeClass('disabled');
                alignToggle.hide();
                alignCheckbox.prop('checked', false);
            } else if (isCustomGroup) {
                if (isSignatureGroup) {
                    $('#float-group').hide();
                    $('#float-ungroup').hide().prop('disabled', true).addClass('disabled');
                    alignToggle.show().addClass('active').attr('title', 'Align Within Signature Block');
                    alignCheckbox
                        .prop('checked', true)
                        .prop('disabled', true)
                        .attr('data-signature-readonly', 'true');
                } else {
                    $('#float-group').hide();
                    $('#float-ungroup')
                        .show()
                        .prop('disabled', false)
                        .removeClass('disabled')
                        .attr('title', 'Ungroup (Ctrl+Shift+G)');
                    alignToggle.show();
                    alignCheckbox
                        .prop('checked', !!window.alignWithinGroupMode);
                    alignToggle.toggleClass('active', !!window.alignWithinGroupMode);
                }
            } else if (isSignatureGroup) {
                // Signature block selected directly (not marked as custom group)
                $('#float-group').hide();
                $('#float-ungroup').hide().prop('disabled', true).addClass('disabled');
                alignToggle.show().addClass('active').attr('title', 'Align Within Signature Block');
                alignCheckbox
                    .prop('checked', true)
                    .prop('disabled', true)
                    .attr('data-signature-readonly', 'true');
            }
        } else {
            $('#float-group').hide();
            $('#float-ungroup').hide().prop('disabled', false).removeClass('disabled');
            alignToggle.hide().removeClass('active');
            alignCheckbox
                .prop('disabled', false)
                .removeAttr('data-signature-readonly')
                .prop('checked', !!window.alignWithinGroupMode);
        }

        // Compute bounding box to anchor the floating toolbar.
        // If the active object is a signature-block child text, align toolbar
        // with the parent block rather than the inner text to keep position
        // consistent with the block reference.
        let bounds;
        if (isTextObject(activeObject) && activeObject.group && activeObject.group.isSignatureBlock) {
            bounds = activeObject.group.getBoundingRect(true, true);
        } else {
            bounds = activeObject.getBoundingRect(true, true);
        }
        const containerWrapper = $(canvas.wrapperEl).parent();
        const containerWidth = containerWrapper.innerWidth();
        const containerHeight = containerWrapper.innerHeight();

        const wasHidden = container.css('display') === 'none';
        if (wasHidden) {
            container.css({ visibility: 'hidden', display: 'block' });
        }

        const panelWidth = container.outerWidth();
        const panelHeight = container.outerHeight();
        const margin = 12;

        let left = bounds.left + (bounds.width / 2) - (panelWidth / 2);
        const maxLeft = Math.max(margin, containerWidth - panelWidth - margin);
        left = Math.min(Math.max(left, margin), maxLeft);

        const desiredAbove = bounds.top - panelHeight - margin;
        const desiredBelow = bounds.top + bounds.height + margin;
        const spaceAbove = bounds.top;
        const spaceBelow = containerHeight - (bounds.top + bounds.height);

        let top = desiredAbove;
        if (desiredAbove < margin) {
            if (desiredBelow + panelHeight <= containerHeight - margin) {
                top = desiredBelow;
            } else if (spaceBelow >= spaceAbove) {
                top = Math.min(Math.max(desiredBelow, margin), containerHeight - panelHeight - margin);
            } else {
                top = Math.min(Math.max(desiredAbove, margin), containerHeight - panelHeight - margin);
            }
        }

        const maxTop = Math.max(margin, containerHeight - panelHeight - margin);
        top = Math.min(Math.max(top, margin), maxTop);

        let placement = 'overlap';
        if (top + panelHeight <= bounds.top - 4) {
            placement = 'above';
        } else if (top >= bounds.top + bounds.height + 4) {
            placement = 'below';
        }

        container
            .css({ left: `${left}px`, top: `${top}px`, visibility: 'visible' })
            .data('placement', placement)
            .attr('data-placement', placement)
            .attr('data-mode', hasText ? 'text' : 'group')
            .show();

        if (floatingPopoverState.isOpen) {
            if (!isElementInDocument(floatingPopoverState.anchor)) {
                closeFloatingPopover();
            } else {
                floatingPopoverState.canvas = canvas;
                renderFloatingPopover(floatingPopoverState.type, canvas);
                if (floatingPopoverState.anchor && floatingPopoverState.anchor.classList) {
                    floatingPopoverState.anchor.classList.add('open');
                }
            }
        }
    }

    function setFloatingChip(selector, value, formatter) {
        const chip = $(selector);
        if (!chip.length) return;

        chip.toggleClass('mixed', value === null || value === undefined);
        const formatted = formatter && typeof formatter === 'function'
            ? formatter(value)
            : (value === null || value === undefined ? 'Mixed' : value);
        chip.find('.value').text(formatted);

        const titleText = value === null || value === undefined
            ? (formatted || '')
            : String(value);
        chip.attr('title', titleText);
    }

    function setFloatingColorChip(selector, colorValue) {
        const chip = $(selector);
        if (!chip.length) return;

        const accessibleValue = chip.find('.value');

        const isMixed = colorValue === null || colorValue === undefined;
        chip.toggleClass('mixed', isMixed);
        if (isMixed) {
            accessibleValue.text('Mixed');
            chip.get(0).style.removeProperty('--preview-color');

            floatingColorState.isMixed = true;
            if (!floatingColorState.currentHex) {
                floatingColorState.currentHex = '#000000';
            }
            renderFloatingColorDisplay();
            return;
        }

        let hexColor = '#000000';
        let source = null;

        try {
            if (window.fabric && fabric.Color) {
                const fabricColor = new fabric.Color(colorValue);
                hexColor = `#${fabricColor.toHex()}`.toUpperCase();
                source = fabricColor.getSource();
            } else if (typeof colorValue === 'string') {
                hexColor = colorValue.toUpperCase();
            }
        } catch (err) {
            if (typeof colorValue === 'string') {
                hexColor = colorValue.toUpperCase();
            }
        }

        if (!Array.isArray(source)) {
            try {
                if (window.fabric && fabric.Color) {
                    const fallback = new fabric.Color(hexColor);
                    source = fallback.getSource();
                }
            } catch (err) {
                source = [0, 0, 0, 1];
            }
        }

        if (!hexColor.startsWith('#')) {
            try {
                if (window.fabric && fabric.Color) {
                    const normalized = new fabric.Color(colorValue);
                    hexColor = `#${normalized.toHex()}`.toUpperCase();
                }
            } catch (err) {
                hexColor = '#000000';
            }
        }

        const rgbString = formatRgbStringFromSource(source);
        const hslString = formatHslStringFromSource(source);

        floatingColorState.isMixed = false;
        floatingColorState.currentHex = hexColor;
        floatingColorState.values = {
            hex: hexColor,
            rgb: rgbString,
            hsl: hslString
        };

        accessibleValue.text(hexColor);
        chip.get(0).style.setProperty('--preview-color', hexColor);

        renderFloatingColorDisplay();
    }

    function clearFloatingColorChip() {
        const chip = $('#floating-font-color');
        if (!chip.length) return;

        chip.removeClass('mixed open');
        chip.get(0).style.removeProperty('--preview-color');
        chip.find('.value').text('—');

        floatingColorState.isMixed = true;
        floatingColorState.values = {
            hex: '#000000',
            rgb: 'rgb(0, 0, 0)',
            hsl: 'hsl(0, 0%, 0%)'
        };
        floatingColorState.currentHex = '#000000';

        closeFloatingColorDropdown();
        renderFloatingColorDisplay();
    }

    function updatePreviewToggle(selector, state) {
        const btn = $(selector);
        if (!btn.length) return;

        btn.removeClass('active mixed');
        if (state === true) {
            btn.addClass('active');
        } else if (state === null) {
            btn.addClass('mixed');
        }
    }

    function formatLineHeight(value) {
        if (value === null || value === undefined) return 'Mixed';
        const numeric = parseFloat(value);
        if (Number.isNaN(numeric)) return `${value}`;
        return numeric % 1 === 0 ? `${numeric.toFixed(0)}` : `${numeric.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')}`;
    }

    // ========================================
    // GROUP/UNGROUP FUNCTIONALITY
    // ========================================
    function initGroupingFeatures(canvas) {
        // Ctrl+G / Ctrl+Shift+G shortcuts
        $(document).on('keydown', function(e) {
            const key = (e.key || '').toLowerCase();
            if (!(e.ctrlKey || e.metaKey)) return;
            if (e.repeat) return; // avoid repeat firing

            // Ignore when typing in form fields
            const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
            const isFormField = tag === 'input' || tag === 'textarea' || tag === 'select' || $(e.target).prop('contenteditable') === 'true';
            if (isFormField) return;

            if (key === 'g' && !e.shiftKey) {
                e.preventDefault();
                e.stopPropagation();
                groupSelection(canvas);
                return;
            } else if (key === 'g' && e.shiftKey) {
                e.preventDefault();
                e.stopPropagation();
                ungroupSelection(canvas);
                return;
            }
        });
        
        // Floating toolbar buttons
    $('#float-group').on('click', () => groupSelection(canvas));
    $('#float-ungroup').on('click', () => ungroupSelection(canvas));
        
        // Double-click to enter group edit mode
        canvas.on('mouse:dblclick', function(e) {
            const target = e.target;
            if (target && target.type === 'group' && target.isCustomGroup) {
                // Enable sub-target check for individual selection
                target.subTargetCheck = true;
                canvas.renderAll();
            }
        });
        
        // Exit edit mode on selection cleared
        canvas.on('selection:cleared', function() {
            canvas.getObjects().forEach(obj => {
                if (obj.type === 'group' && obj.isCustomGroup) {
                    obj.subTargetCheck = false;
                }
            });
        });
    }
    
    function groupSelection(canvas) {
        const activeSelection = canvas.getActiveObject();
        if (!activeSelection || activeSelection.type !== 'activeSelection') return;

        const selected = activeSelection.getObjects();
        if (!selected || selected.length < 2) return;

        console.log('Grouping objects:', selected.length);

        // Use Fabric's built-in toGroup() method - it handles all coordinate transformations
        const newGroup = activeSelection.toGroup();
        newGroup.set({ 
            isCustomGroup: true, 
            selectable: true, 
            subTargetCheck: false 
        });

        console.log('Group created:', newGroup);

        // Ensure the new group is selected
        canvas.setActiveObject(newGroup);
        canvas.requestRenderAll();
        canvas.fire('object:modified', { target: newGroup });
        
        // Force toolbar update with delay to ensure DOM is ready
        setTimeout(() => {
            console.log('Refreshing floating toolbar after group');
            updateFloatingFormattingPreview(canvas);
        }, 100);
    }
    
    function ungroupSelection(canvas) {
        const activeObject = canvas.getActiveObject();
        if (!activeObject || activeObject.type !== 'group') return;

        // Locked signature blocks cannot be ungrouped
        if (activeObject.isSignatureBlock) return;
        if (!activeObject.isCustomGroup) return;

        console.log('Ungrouping group');

        // Use Fabric's built-in toActiveSelection() method
        const selection = activeObject.toActiveSelection();
        canvas.setActiveObject(selection);
        canvas.requestRenderAll();

        // Notify undo/redo and refresh toolbar
        const first = selection._objects && selection._objects[0] ? selection._objects[0] : selection;
        canvas.fire('object:modified', { target: first });
        
        // Force toolbar update with delay
        setTimeout(() => {
            console.log('Refreshing floating toolbar after ungroup');
            updateFloatingFormattingPreview(canvas);
        }, 100);
    }
    
    function handleCanvasSelection(e, canvas) {
        const selectedObject = e.target;
        
        if (!selectedObject) {
            disableFormattingToolbar();
            window.contextualSidebar.showDefault();
            return;
        }
        
        // 🆕 MULTI-SELECTION SUPPORT
        const isMultiSelect = selectedObject.type === 'activeSelection';
        
        if (isMultiSelect) {
            // Get all text objects from multi-selection
            const textObjects = getTextObjectsFromSelection(selectedObject);
            
            if (textObjects.length > 0) {
                // Has text objects - enable toolbar
                enableFormattingToolbar();
                syncCanvasToToolbar(selectedObject); // Pass activeSelection object
                
                // Update sidebar
                window.contextualSidebar.hideAll();
                $('#sidebar-default').show();
                $('#sidebar-subtitle').text(`${textObjects.length} objek teks dipilih`);
            } else {
                // No text objects in selection
                disableFormattingToolbar();
                window.contextualSidebar.showDefault();
            }
            
            return;
        }
        
    // � GROUP DETECTION SUPPORT
    // NOTE: exclude signature blocks from generic group handling so
    // signature groups are reported with their specific sidebar UI.
    const isGroup = selectedObject.type === 'group' && !selectedObject.isSignatureBlock;
        
        if (isGroup) {
            // Get all text objects from group
            const textObjects = getTextObjectsFromSelection(selectedObject);
            
            if (textObjects.length > 0) {
                // Has text objects - enable toolbar
                enableFormattingToolbar();
                syncCanvasToToolbar(selectedObject); // Pass group object
                
                // Update sidebar
                window.contextualSidebar.hideAll();
                $('#sidebar-default').show();
                $('#sidebar-subtitle').text(`Group dengan ${textObjects.length} objek teks`);
            } else {
                // No text objects in group
                disableFormattingToolbar();
                window.contextualSidebar.showDefault();
            }
            
            return;
        }
        
        // �🎯 LOGIKA UTAMA: Deteksi jenis objek yang dipilih (single selection)
        const isText = selectedObject.type === 'text' || 
                      selectedObject.type === 'textbox' || 
                      selectedObject.type === 'i-text' ||
                      selectedObject.type === 'IText' ||
                      (selectedObject.text !== undefined); // Fallback check
        
        if (isText) {
            // ✅ STATE: Objek Teks Dipilih - AKTIFKAN toolbar
            enableFormattingToolbar();
            
            // 🔄 SINKRONISASI SATU ARAH: Kanvas -> Toolbar
            syncCanvasToToolbar(selectedObject);
            
            // 📝 Update sidebar berdasarkan jenis teks
            // 🔑 PERBAIKAN: Gunakan placeholderType yang tersimpan, bukan hanya format teks
            const isPlaceholder = selectedObject.isPlaceholder && selectedObject.placeholderType;
            
            if (isPlaceholder) {
                window.contextualSidebar.showPlaceholder(selectedObject.placeholderType);
            } else {
                // Show text properties sidebar
                const text = selectedObject.text;
                $('#sidebar-text #text-content').val(text);
                $('#sidebar-text #text-pos-x').val(Math.round(selectedObject.left));
                $('#sidebar-text #text-pos-y').val(Math.round(selectedObject.top));
                
                window.contextualSidebar.hideAll();
                $('#sidebar-text').show();
                $('#sidebar-subtitle').text('Properti: Teks Bebas');
            }
            
        } else if (selectedObject.isSignatureBlock) {
            // ❌ STATE: Signature Block Dipilih - NONAKTIFKAN toolbar
            disableFormattingToolbar();
            
            // Handle signature block selection
            const index = selectedObject.signatureIndex || 0;
            window.contextualSidebar.showSignatureBlock(index);
            
        } else {
            // ❌ STATE: Objek Non-Teks Dipilih - NONAKTIFKAN toolbar
            disableFormattingToolbar();
            window.contextualSidebar.showDefault();
        }
    }
    
    // 🔧 FUNGSI HELPER: Enable/Disable Toolbar
    function enableFormattingToolbar() {
        // Static toolbar sudah dihapus - fungsi ini dibiarkan kosong untuk kompatibilitas
        // Floating toolbar akan otomatis muncul saat ada selection
    }
    
    function disableFormattingToolbar() {
        // Static toolbar sudah dihapus - fungsi ini dibiarkan kosong untuk kompatibilitas
        // Floating toolbar akan otomatis hide saat selection cleared
    }
    
    // 🔄 SINKRONISASI SATU ARAH: Canvas -> Toolbar  
    function syncCanvasToToolbar(textObject) {
        // 🆕 Fungsi ini sekarang hanya untuk kompatibilitas dan update floating toolbar
        // Static toolbar sudah dihapus, jadi kita hanya perlu update floating toolbar preview
        
        const canvas = window.canvas;
        if (canvas && canvas.getActiveObject()) {
            updateFloatingFormattingPreview(canvas);
        }
    }
    
    /**
     * Sync single text object to toolbar (kept for compatibility)
     * @param {fabric.Text} textObject - Single text object
     */
    function syncSingleTextToToolbar(textObject) {
        // Fungsi ini dipanggil dari beberapa tempat untuk kompatibilitas
        // Sekarang hanya update floating toolbar
        if (window.canvas) {
            updateFloatingFormattingPreview(window.canvas);
        }
    }
    
    /**
     * Sync multiple text objects to toolbar (kept for compatibility)
     * @param {Array} textObjects - Array of text objects
     */
    function syncMultipleTextsToToolbar(textObjects) {
        // Fungsi ini dipanggil dari beberapa tempat untuk kompatibilitas
        // Sekarang hanya update floating toolbar
        if (window.canvas) {
            updateFloatingFormattingPreview(window.canvas);
        }
    }
    
    // 🎯 HELPER: Cek apakah objek adalah teks
    function isTextObject(obj) {
        return obj && (obj.type === 'text' || 
                      obj.type === 'textbox' || 
                      obj.type === 'i-text' ||
                      obj.type === 'IText' ||
                      obj.text !== undefined);
    }
    
    // ========================================
    // 🆕 MULTI-SELECTION FORMATTING HELPERS
    // ========================================
    
    /**
     * Extract all text objects from selection (including those inside groups)
     * @param {fabric.Object} activeSelection - The active selection object
     * @returns {Array} Array of text objects
     */
    function getTextObjectsFromSelection(activeSelection) {
        if (!activeSelection) return [];
        
        // If single object
        if (activeSelection.type !== 'activeSelection') {
            if (isTextObject(activeSelection)) {
                return [activeSelection];
            }
            // If it's a group, extract text children
            if (activeSelection.type === 'group' && activeSelection._objects) {
                return extractTextFromGroup(activeSelection);
            }
            return [];
        }
        
        // Multi-selection: loop through all objects
        const allTexts = [];
        const objects = activeSelection._objects || activeSelection.getObjects();
        
        objects.forEach(obj => {
            if (isTextObject(obj)) {
                allTexts.push(obj);
            } else if (obj.type === 'group' && obj._objects) {
                // Recursively extract from groups
                allTexts.push(...extractTextFromGroup(obj));
            }
        });
        
        return allTexts;
    }
    
    /**
     * Recursively extract text objects from a group
     * @param {fabric.Group} group - The group object
     * @returns {Array} Array of text objects
     */
    function extractTextFromGroup(group) {
        const texts = [];
        const children = group._objects || group.getObjects();
        
        children.forEach(child => {
            if (isTextObject(child)) {
                texts.push(child);
            } else if (child.type === 'group' && child._objects) {
                texts.push(...extractTextFromGroup(child));
            }
        });
        
        return texts;
    }
    
    /**
     * Get common property value from multiple objects
     * Returns the value if all objects have the same value, null otherwise
     * @param {Array} objects - Array of objects to compare
     * @param {string} property - Property name to compare
     * @returns {*|null} Common value or null if mixed
     */
    function getCommonPropertyValue(objects, property) {
        if (!objects || objects.length === 0) return null;
        if (objects.length === 1) return objects[0][property];
        
        const firstValue = objects[0][property];
        const allSame = objects.every(obj => obj[property] === firstValue);
        
        return allSame ? firstValue : null;
    }
    
    /**
     * Get effective font size (with scaling applied)
     * @param {fabric.Text} textObject - Text object
     * @returns {number} Effective font size
     */
    function getEffectiveFontSize(textObject) {
        const fontSize = textObject.fontSize || 24;
        const scaleY = textObject.scaleY || 1;
        return fontSize * scaleY;
    }
    
    /**
     * Check if all text objects have the same effective font size
     * @param {Array} textObjects - Array of text objects
     * @returns {number|null} Common font size or null if mixed
     */
    function getCommonFontSize(textObjects) {
        if (!textObjects || textObjects.length === 0) return null;
        if (textObjects.length === 1) {
            return Math.round(getEffectiveFontSize(textObjects[0]));
        }
        
        const effectiveSizes = textObjects.map(getEffectiveFontSize);
        const firstSize = effectiveSizes[0];
        
        // ✅ FIX: Add 0.5px tolerance for floating point precision issues
        const allSame = effectiveSizes.every(size => Math.abs(size - firstSize) < 0.5);
        
        return allSame ? Math.round(firstSize) : null;
    }
    
    /**
     * Get common boolean property (for bold/italic/underline)
     * Returns true if all have it, false if none have it, null if mixed
     * @param {Array} objects - Array of objects
     * @param {string} property - Property name (e.g., 'fontWeight', 'fontStyle', 'underline')
     * @param {*} trueValue - Value that represents "true" state (e.g., 'bold' for fontWeight)
     * @returns {boolean|null} true, false, or null if mixed
     */
    function getCommonBooleanProperty(objects, property, trueValue) {
        if (!objects || objects.length === 0) return null;
        if (objects.length === 1) {
            return objects[0][property] === trueValue;
        }
        
        const hasProperty = objects.map(obj => obj[property] === trueValue);
        const allTrue = hasProperty.every(v => v === true);
        const allFalse = hasProperty.every(v => v === false);
        
        if (allTrue) return true;
        if (allFalse) return false;
        return null; // Mixed
    }
    
    /**
     * Update toolbar field with value or "Mixed" placeholder
     * @param {string} selector - jQuery selector for the field
     * @param {*} value - Value to set, or null for "Mixed"
     * @param {string} placeholderText - Custom placeholder text (default: "Mixed")
     */
    // Fungsi updateToolbarField dihapus karena static toolbar sudah dihapus
    
    function getSelectionObjects(activeObject) {
        if (!activeObject) return [];

        if (activeObject.type === 'activeSelection') {
            return activeObject._objects ? activeObject._objects.slice() : [];
        }

        if (activeObject.type === 'group') {
            if (typeof activeObject.getObjects === 'function') {
                return activeObject.getObjects();
            }
            return activeObject._objects ? activeObject._objects.slice() : [];
        }

        return [activeObject];
    }

    function resolveFloatingAlignContext(activeObject, textObjects) {
        const context = {
            mode: 'text',
            showVertical: false,
            hasNonText: false,
            totalObjects: 0,
        };

        if (!activeObject) {
            return context;
        }

        const objects = getSelectionObjects(activeObject);
        context.totalObjects = objects.length || (isTextObject(activeObject) ? 1 : 0);
        context.hasNonText = objects.some(obj => !isTextObject(obj));

        const isGroup = activeObject.type === 'group';
        const isSignatureGroup = isGroup && !!activeObject.isSignatureBlock;
        const isCustomGroup = isGroup && !!activeObject.isCustomGroup;
        const isMultiSelect = activeObject.type === 'activeSelection';
        const alignWithin = !!window.alignWithinGroupMode && isGroup && isCustomGroup;

        if (isSignatureGroup) {
            context.mode = 'signature-text';
            context.showVertical = false;
            context.hasNonText = false;
            return context;
        }

        if (alignWithin || !textObjects.length || isGroup || context.hasNonText) {
            context.mode = 'object';
        }

        if (context.mode === 'object') {
            context.showVertical = isMultiSelect || isCustomGroup;
        }

        return context;
    }

    /**
     * Apply function to all text objects in selection (including those in groups)
     * Handles both single text objects and multi-selections
     * @param {fabric.Object} activeObject - The active object or selection
     * @param {Function} applyFn - Function to apply to each text object
     */
    function applyToAllTextObjects(activeObject, applyFn) {
        if (!activeObject) return;
        
        // Single text object
        if (isTextObject(activeObject)) {
            applyFn(activeObject);
            return;
        }
        
        // Multi-selection or group
        const textObjects = getTextObjectsFromSelection(activeObject);
        textObjects.forEach(textObj => applyFn(textObj));
    }
    
    // 🎨 APPLY FORMATTING: Toolbar -> Canvas
    function applyTextFormatting(textObject, action, canvas, isBulkOperation = false, commonState = null) {
        switch(action) {
            case 'bold':
                // ✅ OPSI A: Smart Apply Logic (FIXED - Handle null for mixed)
                // - If all bold (true): toggle all to normal
                // - If all normal (false): make all bold  
                // - If mixed (null): make all bold (unify to bold state)
                if (isBulkOperation) {
                    // Bulk operation with known state
                    if (commonState === true) {
                        // All bold -> toggle to normal
                        textObject.set('fontWeight', 'normal');
                    } else {
                        // All normal (false) OR mixed (null) -> make bold
                        textObject.set('fontWeight', 'bold');
                    }
                } else {
                    // Single object - simple toggle
                    textObject.set('fontWeight', textObject.fontWeight === 'bold' ? 'normal' : 'bold');
                }
                break;
            case 'italic':
                // Same logic untuk italic
                if (isBulkOperation) {
                    if (commonState === true) {
                        textObject.set('fontStyle', 'normal');
                    } else {
                        textObject.set('fontStyle', 'italic');
                    }
                } else {
                    textObject.set('fontStyle', textObject.fontStyle === 'italic' ? 'normal' : 'italic');
                }
                break;
            case 'underline':
                // Same logic untuk underline
                if (isBulkOperation) {
                    if (commonState === true) {
                        textObject.set('underline', false);
                    } else {
                        textObject.set('underline', true);
                    }
                } else {
                    textObject.set('underline', !textObject.underline);
                }
                break;
            case 'align-left':
                textObject.set('textAlign', 'left');
                break;
            case 'align-center':
                textObject.set('textAlign', 'center');
                break;
            case 'align-right':
                textObject.set('textAlign', 'right');
                break;
        }
        canvas.renderAll();
    }
    
    function handleFormattingAction(canvas, action) {
        if (!canvas) return;

        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;

        const textObjects = getTextObjectsFromSelection(activeObject);
        const effectiveObjects = textObjects.length > 0
            ? textObjects
            : (isTextObject(activeObject) ? [activeObject] : []);
        if (effectiveObjects.length === 0) return;

        const toggleActions = ['bold', 'italic', 'underline'];
        const isToggle = toggleActions.includes(action);
        let commonState = null;

        if (isToggle) {
            if (action === 'bold') {
                commonState = getCommonBooleanProperty(effectiveObjects, 'fontWeight', 'bold');
            } else if (action === 'italic') {
                commonState = getCommonBooleanProperty(effectiveObjects, 'fontStyle', 'italic');
            } else if (action === 'underline') {
                commonState = getCommonBooleanProperty(effectiveObjects, 'underline', true);
            }
        }

        applyToAllTextObjects(activeObject, (textObj) => {
            if (isToggle) {
                applyTextFormatting(textObj, action, canvas, true, commonState);
            } else {
                applyTextFormatting(textObj, action, canvas);
            }
        });

        canvas.fire('object:modified', { target: activeObject });

        const refreshTarget = canvas.getActiveObject() || activeObject;
        if (refreshTarget) {
            setTimeout(() => {
                syncCanvasToToolbar(refreshTarget);
                updateFloatingFormattingPreview(canvas);
            }, 10);
        }
    }

    function handleFloatingAlign(canvas, direction) {
        if (!canvas || !direction) return;

        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;

        const mode = window.floatingAlignMode || 'text';

        if (mode === 'signature-text') {
            const handledDirections = ['left', 'center', 'right'];
            if (handledDirections.includes(direction)) {
                if (alignSignatureBlockText(activeObject, direction)) {
                    canvas.requestRenderAll();
                    canvas.fire('object:modified', { target: activeObject });
                    setTimeout(() => updateFloatingFormattingPreview(canvas), 10);
                }
                return;
            }
        }

        if (mode === 'text' && (direction === 'left' || direction === 'center' || direction === 'right')) {
            handleFormattingAction(canvas, `align-${direction}`);
            return;
        }

        alignSelection(canvas, direction);
        canvas.fire('object:modified', { target: activeObject });
        setTimeout(() => updateFloatingFormattingPreview(canvas), 10);
    }



    function populateTextPlaceholders() {
        const menu = $('#text-placeholders');
        
        // Add all placeholders from mapping plus additional ones
        const allPlaceholders = [
            // From sidebar mapping (have property panels)
            ...Object.keys(window.contextualSidebar.placeholderMappings),
            // Additional placeholders (auto-filled from data)
            '@{{nama_penerima}}', '@{{id_lengkap_peserta}}', '@{{peran_penerima}}',
            '@{{nilai_1}}', '@{{nilai_2}}', '@{{nilai_3}}', '@{{nilai_4}}'
        ];
        
        allPlaceholders.forEach(p => {
            let displayText = p;
            const mapping = window.contextualSidebar.placeholderMappings[p];
            
            if (mapping) {
                displayText = `${p} - ${mapping.title}`;
            } else {
                // Add descriptions for data placeholders
                if (p.includes('nilai_')) {
                    displayText = p + ' (dari Excel/CSV)';
                } else if (p === '@{{nama_penerima}}') {
                    displayText = p + ' (dari Data Peserta)';
                } else if (p === '@{{id_lengkap_peserta}}') {
                    displayText = p + ' (NPK/ID dari Data)';
                } else if (p === '@{{peran_penerima}}') {
                    displayText = p + ' (Peran dari Data)';
                }
            }
            
            menu.append(`<a class="dropdown-item text-placeholder-item" href="#" data-placeholder="${p}">${displayText}</a>`);
        });

        $('#insert-menu').on('click', '.text-placeholder-item', function(e) {
            e.preventDefault();
            const placeholderText = $(this).data('placeholder');
            const text = new fabric.IText(placeholderText, {
                left: 150, top: 150,
                fontFamily: 'Arial', 
                fontSize: 24, 
                fill: '#000000', 
                textAlign: 'center', 
                lineHeight: 1.2, 
                isPlaceholder: true,
                placeholderType: placeholderText, // 🔑 Simpan tipe placeholder asli
                // 🎯 DEFAULT FORMATTING PROPERTIES
                fontWeight: 'normal',
                fontStyle: 'normal', 
                underline: false,
                textAlign: 'center',
                // 🎨 IMPROVE TEXT RENDERING QUALITY
                strokeWidth: 0,
                paintFirst: 'fill'
            });
            text.areaKey = generateAreaKey();
            text.set('areaKey', text.areaKey);
            canvas.add(text);
            // 🔥 FIX: Set as active object untuk trigger selection events
            canvas.setActiveObject(text);
            
            // Manually trigger selection handler
            setTimeout(() => {
                handleCanvasSelection({target: text}, canvas);
            }, 50);
        });
    }

    // =============================
    // SMART GUIDES HELPERS
    // =============================
    function getBounds(obj) {
        const rect = obj.getBoundingRect(true, true);
        const left = rect.left;
        const top = rect.top;
        const right = rect.left + rect.width;
        const bottom = rect.top + rect.height;
        const cx = left + rect.width / 2;
        const cy = top + rect.height / 2;
        return { left, top, right, bottom, cx, cy, width: rect.width, height: rect.height };
    }

    function collectSnapLines(canvas, excludeObj) {
        const v = [0, canvas.width / 2, canvas.width];
        const h = [0, canvas.height / 2, canvas.height];
        const objects = canvas.getObjects() || [];
        objects.forEach(o => {
            if (o === excludeObj) return;
            if (o.visible === false || o.selectable === false) return;
            // Defer signature children as snap targets for now (only group bounds via bounding box will count)
            const b = getBounds(o);
            v.push(b.left, b.cx, b.right);
            h.push(b.top, b.cy, b.bottom);
        });
        return { v: Array.from(new Set(v)), h: Array.from(new Set(h)) };
    }

    function computeSnapAdjustment(canvas, target) {
        const zoom = canvas.getZoom ? canvas.getZoom() : 1;
        const tol = (window.smartGuides && window.smartGuides.tolerance ? window.smartGuides.tolerance : 8) / (zoom || 1);
        const lines = collectSnapLines(canvas, target);
        const b = getBounds(target);

        const objV = [ {val: b.left, key:'left'}, {val: b.cx, key:'cx'}, {val: b.right, key:'right'} ];
        const objH = [ {val: b.top, key:'top'}, {val: b.cy, key:'cy'}, {val: b.bottom, key:'bottom'} ];

        let bestV = { dist: tol + 1, line: null, key: null };
        let bestH = { dist: tol + 1, line: null, key: null };

        lines.v.forEach(L => {
            objV.forEach(p => {
                const d = Math.abs(p.val - L);
                if (d < bestV.dist && d <= tol) {
                    bestV = { dist: d, line: L, key: p.key };
                }
            });
        });
        lines.h.forEach(L => {
            objH.forEach(p => {
                const d = Math.abs(p.val - L);
                if (d < bestH.dist && d <= tol) {
                    bestH = { dist: d, line: L, key: p.key };
                }
            });
        });

        let dx = 0, dy = 0;
        if (bestV.line !== null) {
            if (bestV.key === 'left') dx = bestV.line - b.left;
            else if (bestV.key === 'cx') dx = bestV.line - b.cx;
            else if (bestV.key === 'right') dx = bestV.line - b.right;
        }
        if (bestH.line !== null) {
            if (bestH.key === 'top') dy = bestH.line - b.top;
            else if (bestH.key === 'cy') dy = bestH.line - b.cy;
            else if (bestH.key === 'bottom') dy = bestH.line - b.bottom;
        }

        return { dx, dy, vLineX: bestV.line, hLineY: bestH.line };
    }

    // =============================
    // ALIGN/DISTRIBUTE HELPERS
    // =============================
    function alignSignatureBlockText(group, direction) {
        if (!group || group.type !== 'group' || !group.isSignatureBlock) return false;
        if (!['left', 'center', 'right'].includes(direction)) return false;

        const objects = group.getObjects ? group.getObjects() : (group._objects || []);
        if (!objects.length) return false;

        const textChildren = objects.filter(obj => isTextObject(obj));
        if (!textChildren.length) return false;

        const baseObject = objects.find(obj => !isTextObject(obj)) || group;
        const baseWidth = typeof baseObject.getScaledWidth === 'function'
            ? (baseObject === group
                ? baseObject.getScaledWidth() / (group.scaleX || 1)
                : baseObject.getScaledWidth())
            : (baseObject.width || group.width || 0) * (baseObject.scaleX || 1);
        const groupWidth = baseWidth || group.width || 0;
        const baseCenter = baseObject === group ? 0 : (baseObject.left || 0);
        const halfWidth = groupWidth / 2;

        textChildren.forEach(child => {
            const childWidth = typeof child.getScaledWidth === 'function'
                ? child.getScaledWidth()
                : (child.width || 0) * (child.scaleX || 1);

            let newCenter = baseCenter;
            let newAlign = child.textAlign || 'center';

            switch (direction) {
                case 'left':
                    newCenter = (baseCenter - halfWidth) + (childWidth / 2);
                    newAlign = 'left';
                    break;
                case 'center':
                    newCenter = baseCenter;
                    newAlign = 'center';
                    break;
                case 'right':
                    newCenter = (baseCenter + halfWidth) - (childWidth / 2);
                    newAlign = 'right';
                    break;
            }

            child.set({
                left: newCenter,
                originX: 'center',
                textAlign: newAlign
            });
            child.setCoords();
        });

        group.addWithUpdate();
        group.setCoords();
        if (window.undoRedoManager) {
            window.undoRedoManager.saveState();
        }
        return true;
    }

    function alignSelection(canvas, mode) {
        const active = canvas.getActiveObject();
        if (!active) return;

        // ========================================
        // ALIGN WITHIN GROUP MODE
        // ========================================
        if (window.alignWithinGroupMode && active.type === 'group' && active.isCustomGroup) {
            // Align children within group bounds (not canvas)
            const groupChildren = active.getObjects();
            if (!groupChildren || groupChildren.length === 0) return;
            
            // Get group's internal bounds
            const groupWidth = active.width;
            const groupHeight = active.height;
            const groupBounds = {
                left: -groupWidth / 2,
                right: groupWidth / 2,
                top: -groupHeight / 2,
                bottom: groupHeight / 2,
                cx: 0,
                cy: 0
            };
            
            // Align each child relative to group's coordinate system
            groupChildren.forEach(child => {
                const childB = getBounds(child);
                let dx = 0, dy = 0;
                
                switch(mode) {
                    case 'left': dx = (groupBounds.left - childB.left); break;
                    case 'center': dx = (groupBounds.cx - childB.cx); break;
                    case 'right': dx = (groupBounds.right - childB.right); break;
                    case 'top': dy = (groupBounds.top - childB.top); break;
                    case 'middle': dy = (groupBounds.cy - childB.cy); break;
                    case 'bottom': dy = (groupBounds.bottom - childB.bottom); break;
                }
                
                child.left += dx;
                child.top += dy;
                child.setCoords();
            });
            
            // Update group coordinates
            active.addWithUpdate();
            canvas.requestRenderAll();
            if (window.undoRedoManager) window.undoRedoManager.saveState();
            return;
        }

        // ========================================
        // STANDARD ALIGN MODE
        // ========================================
        if (active.type === 'activeSelection') {
            const items = active.getObjects();
            if (!items || items.length === 0) return;
            const selB = getBounds(active);
            items.forEach(it => {
                const b = getBounds(it);
                let dx = 0, dy = 0;
                switch(mode) {
                    case 'left': dx = (selB.left - b.left); break;
                    case 'center': dx = (selB.cx - b.cx); break;
                    case 'right': dx = (selB.right - b.right); break;
                    case 'top': dy = (selB.top - b.top); break;
                    case 'middle': dy = (selB.cy - b.cy); break;
                    case 'bottom': dy = (selB.bottom - b.bottom); break;
                }
                it.left += dx;
                it.top += dy;
                it.setCoords();
            });
            canvas.requestRenderAll();
            if (window.undoRedoManager) window.undoRedoManager.saveState();
        } else {
            // Single object: align to canvas bounds
            const b = getBounds(active);
            let dx = 0, dy = 0;
            const cW = canvas.width, cH = canvas.height;
            const canvasB = { left:0, top:0, right:cW, bottom:cH, cx:cW/2, cy:cH/2 };
            switch(mode) {
                case 'left': dx = (canvasB.left - b.left); break;
                case 'center': dx = (canvasB.cx - b.cx); break;
                case 'right': dx = (canvasB.right - b.right); break;
                case 'top': dy = (canvasB.top - b.top); break;
                case 'middle': dy = (canvasB.cy - b.cy); break;
                case 'bottom': dy = (canvasB.bottom - b.bottom); break;
            }
            active.left += dx;
            active.top += dy;
            active.setCoords();
            canvas.requestRenderAll();
            if (window.undoRedoManager) window.undoRedoManager.saveState();
        }
    }

    function distributeSelection(canvas, orientation) {
        const active = canvas.getActiveObject();
        if (!active || active.type !== 'activeSelection') return;
        const items = active.getObjects();
        if (!items || items.length < 3) return; // Need at least 3 for meaningful distribution

        const selB = getBounds(active);
        const entries = items.map(it => ({ it, b: getBounds(it) }));
        if (orientation === 'h') {
            entries.sort((a,b) => a.b.left - b.b.left);
            const totalWidth = entries.reduce((sum, e) => sum + e.b.width, 0);
            const space = (selB.right - selB.left) - totalWidth;
            const gap = space / (entries.length - 1);
            let cursor = selB.left;
            entries.forEach(e => {
                const dx = cursor - e.b.left;
                e.it.left += dx;
                e.it.setCoords();
                cursor += e.b.width + gap;
            });
        } else if (orientation === 'v') {
            entries.sort((a,b) => a.b.top - b.b.top);
            const totalHeight = entries.reduce((sum, e) => sum + e.b.height, 0);
            const space = (selB.bottom - selB.top) - totalHeight;
            const gap = space / (entries.length - 1);
            let cursor = selB.top;
            entries.forEach(e => {
                const dy = cursor - e.b.top;
                e.it.top += dy;
                e.it.setCoords();
                cursor += e.b.height + gap;
            });
        }
        canvas.requestRenderAll();
        if (window.undoRedoManager) window.undoRedoManager.saveState();
    }

    function bindSignatureBlocks(canvas) {
        $('#insert-menu').on('click', '.signature-block-item', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            addSignatureBlock(canvas, index);
            
            // Show signature sidebar immediately after adding
            setTimeout(() => {
                window.contextualSidebar.showSignatureBlock(index);
            }, 100);
        });
    }

    function bindEditorControls(canvas) {
        // Delegated handler: works for dynamically inserted inputs (signature sidebar)
        $(document).on('change', '.custom-file-input', function(e) {
            const inputId = $(this).attr('id');
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            const label = $(this).next('.custom-file-label');
            if (label && label.length) {
                label.addClass('selected').text(file.name);
            }

            // Signature image flow
            if (inputId && inputId.startsWith('signature_image_')) {
                const previewId = inputId.replace('image', 'preview');
                const match = previewId.match(/\d+/);
                const index = match ? parseInt(match[0]) : 0;
                const reader = new FileReader();
                reader.onload = function(event) {
                    $('#' + previewId).attr('src', event.target.result).show();
                    // Store base64 data into hidden field so it can be submitted with the form
                    const hiddenId = `#hidden_signatures_${index}_image`;
                    if ($(hiddenId).length) {
                        $(hiddenId).val(event.target.result);
                    }
                    // Immediately replace signature placeholder with uploaded image
                    replaceSignaturePlaceholderWithImage(canvas, index, event.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function bindTemplateHandlers(canvas, templates) {
        // 🔄 PERBAIKAN: Unbind existing handlers to prevent duplicates
        $('.load-template-btn').off('click');
        $('.edit-template-btn').off('click');
        $('#save-template').off('click');
        
        $('.load-template-btn').on('click', function() {
            const id = $(this).data('template-id');
            if (id && templates[id]) {
                // Store template ID for submission
                $('#template_id').val(id);
                
                const templateData = JSON.parse(templates[id].template_data);
                
                // 🆕 MULTI-PAGE: Detect format and auto-convert legacy templates
                let isMultiPage = false;
                let pagesData = [];
                
                if (templateData.version === 2 && templateData.pages) {
                    // New multi-page format
                    isMultiPage = true;
                    pagesData = templateData.pages;
                } else {
                    // Legacy single-page format - auto-convert to array
                    console.log('Converting legacy single-page template to multi-page format');
                    isMultiPage = false;
                    pagesData = [{
                        id: 1,
                        state: templateData, // Legacy template_data is the canvas state itself
                        bgImage: null,
                        bgColor: '#ffffff'
                    }];
                }
                
                // 🆕 MULTI-PAGE: Populate canvasPages array
                window.canvasPages = pagesData;
                window.currentPageIndex = 0;
                
                // 🆕 MULTI-PAGE: Load first page onto canvas
                const firstPageState = window.canvasPages[0].state;
                
                canvas.loadFromJSON(firstPageState, () => {
                    // Backfill areaKey for any objects missing it
                    canvas.getObjects().forEach((obj, idx) => {
                        if (!obj.areaKey) {
                            const key = generateAreaKey();
                            obj.areaKey = key;
                            obj.set('areaKey', key);
                        }
                        
                        // 🔑 PERBAIKAN: Restore placeholderType untuk objek placeholder
                        if (obj.text && obj.text.startsWith('@{{') && obj.text.endsWith('}}')) {
                            if (!obj.placeholderType) {
                                obj.placeholderType = obj.text;
                                obj.isPlaceholder = true;
                                obj.set('placeholderType', obj.text);
                                obj.set('isPlaceholder', true);
                            }
                        }

                        // 🧩 Backfill signature groups for Option B
                        if (obj.type === 'group' && obj.isSignatureBlock) {
                            obj.subTargetCheck = true;
                            obj.perPixelTargetFind = true;
                            obj.targetFindTolerance = 8;
                            const children = obj._objects || obj.getObjects();
                            const textChildren = children.filter(c => c.type === 'i-text' || c.type === 'text' || c.type === 'textbox');
                            // Ensure flags on first two text nodes (name, title)
                            if (textChildren[0] && textChildren[0].isSignatureName !== true) {
                                textChildren[0].isSignatureName = true;
                                textChildren[0].signatureIndex = obj.signatureIndex;
                            }
                            if (textChildren[1] && textChildren[1].isSignatureTitle !== true) {
                                textChildren[1].isSignatureTitle = true;
                                textChildren[1].signatureIndex = obj.signatureIndex;
                            }
                        }
                    });

                    canvas.renderAll();
                    
                    // 🔄 Reset undo/redo history after loading template
                    if (window.undoRedoManager) {
                        window.undoRedoManager.clear();
                    }
                    
                    // 🆕 MULTI-PAGE: Apply background if exists
                    if (window.canvasPages[0].bgImage) {
                        loadBackgroundImageFromData(window.canvasPages[0].bgImage, 0);
                    }
                    if (window.canvasPages[0].bgColor) {
                        canvas.backgroundColor = window.canvasPages[0].bgColor;
                        canvas.renderAll();
                    }
                    
                    // 🆕 MULTI-PAGE: Rebuild page tabs
                    rebuildPageTabs();
                    updatePageTabs();
                    
                    alert(`Template "${templates[id].name}" berhasil dimuat (${window.canvasPages.length} halaman).`);

                    // After template is loaded, check for any uploaded signature images and apply them
                    setTimeout(() => {
                        applyExistingSignatureImages(canvas);
                    }, 500); // Small delay to ensure template is fully loaded

                    // Try to fetch design_settings for this template and apply styles
                    fetch(`/templates/${id}/design`).then(r => r.json()).then(data => {
                        if (data.success && data.design_settings && data.design_settings.areas) {
                            applyDesignSettingsToCanvas(canvas, data.design_settings.areas);
                        }
                    }).catch(err => { console.warn('No design settings or fetch failed', err); });
                });
            }
        });

        $('.edit-template-btn').on('click', function() {
            const id = $(this).data('template-id');
            const currentName = $(this).data('template-name');
            const newName = prompt("Ubah nama template:", currentName);
            if (!newName || newName === currentName) return;

            fetch(`/templates/${id}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({ name: newName })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    alert(data.message);
                    $('#template-name-' + id).text(newName);
                    $(this).data('template-name', newName);
                } else {
                    alert('Gagal: ' + (data.errors?.name[0] || data.message));
                }
            });
        });

        $('#save-template').on('click', function() {
            const name = prompt("Nama template:", "Template " + new Date().toLocaleString());
            if (!name) return;

            // 🆕 MULTI-PAGE: Save current page state before saving template
            saveCurrentPageState();

            // 🆕 MULTI-PAGE: Prepare template data with pages array
            const templatePayload = {
                name: name,
                total_pages: window.canvasPages.length,
                template_data: JSON.stringify({
                    version: 2, // Mark as multi-page format
                    pages: window.canvasPages // Array of {id, state, bgImage, bgColor}
                })
            };

            fetch('{{ route('templates.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(templatePayload)
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    alert(data.message);
                    
                    // 🔄 PERBAIKAN: Update templates object with new template
                    if (data.template) {
                        const t = data.template;
                        templates[t.id] = t;
                        
                        // 🔄 DATATABLES: Add new row using DataTables API
                        if (templatesDataTable) {
                            templatesDataTable.row.add([
                                `<span id="template-name-${t.id}">${t.name}</span>`,
                                new Date(t.created_at).toLocaleString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'}),
                                `<div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success load-template-btn" data-template-id="${t.id}"><i class="fas fa-check"></i> Muat</button>
                                    <button type="button" class="btn btn-sm btn-warning edit-template-btn" data-template-id="${t.id}" data-template-name="${t.name}"><i class="fas fa-edit"></i> Ubah Nama</button>
                                    <form action="/templates/${t.id}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template ini?');">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                                    </form>
                                </div>`
                            ]).draw(false); // false = stay on current page
                            
                            // 🔄 PERBAIKAN: Rebind event handlers for new buttons
                            bindTemplateHandlers(canvas, templates);
                        } else {
                            alert('Template berhasil disimpan, silakan refresh halaman untuk melihat.');
                        }
                    }
                } else {
                    alert('Gagal menyimpan: ' + (data.errors?.name.join(', ') || data.message));
                }
            }).catch(err => {
                alert('Terjadi kesalahan saat menyimpan template.');
            });
        });

        // Form input sync to canvas placeholders
        function syncFormToCanvas() {
            // Map form input IDs/names to placeholder keys
            const mapping = {
                'event_name': '@{{nama_acara}}',
                'certificate_number_prefix': '@{{nomor_sertifikat}}',
                'certificate_type': '@{{jenis_sertifikat}}',
                'start_date': '@{{tanggal_acara}}',
                'signing_place': '@{{tanggal_penandatanganan}}',
                'signing_date': '@{{tanggal_penandatanganan}}',
                'descriptions[0]': '@{{deskripsi_1}}',
                'descriptions[1]': '@{{deskripsi_2}}',
                'descriptions[2]': '@{{deskripsi_3}}'
            };
            Object.keys(mapping).forEach(function(field) {
                const sel = $(`[name='${field}'],#${field}`);
                sel.on('input change', function() {
                    const val = $(this).val();
                    // Find canvas object with matching placeholder text
                    canvas.getObjects().forEach(obj => {
                        if (obj.isPlaceholder && obj.text === mapping[field]) {
                            obj.set('text', val || mapping[field]);
                            canvas.requestRenderAll();
                        }
                    });
                });
            });
        }
        syncFormToCanvas();
        
        // === SYNC FONT SIZE WITH SCALING ===
            // Live update toolbar font size during scaling (shows effective font size)
            canvas.on('object:scaling', function(e) {
                const obj = e.target;
                if (!isTextObject(obj)) return;

                // Initialize baseline values once per drag gesture
                if (!obj.__scalingData) {
                    obj.__scalingData = {
                        baseFontSize: obj.fontSize || 24,
                        baseScaleY: obj.scaleY || 1
                    };
                }

                const base = obj.__scalingData;
                const effectiveScaleY = (obj.scaleY || 1) / (base.baseScaleY || 1);
                const effectiveFontSize = Math.max(1, Math.round(base.baseFontSize * effectiveScaleY));

                // Update toolbar live
                $('#toolbar-font-size').val(effectiveFontSize);
            });

            // Commit font size after scaling finished: convert scale into fontSize, reset scale to 1
            canvas.on('object:modified', function(e) {
                const obj = e.target;
                
                // ✅ SYNC TOOLBAR AFTER SCALING/TRANSFORM (Group, ActiveSelection, Text)
                if (obj.type === 'group' || obj.type === 'activeSelection') {
                    // Group/multi-selection modified (scaled, rotated, etc.)
                    syncCanvasToToolbar(obj);
                } else if (isTextObject(obj)) {
                    // Single text object modified
                    if (obj.__scalingData) {
                        const base = obj.__scalingData;
                        const effectiveScaleY = (obj.scaleY || 1) / (base.baseScaleY || 1);
                        const finalFontSize = Math.max(1, Math.round((obj.fontSize || base.baseFontSize) * effectiveScaleY));

                        obj.set({ fontSize: finalFontSize, scaleX: 1, scaleY: 1 });
                        obj.setCoords();
                        delete obj.__scalingData;

                        // Reflect final value in toolbar and canvas
                        $('#toolbar-font-size').val(finalFontSize);
                        canvas.requestRenderAll();
                    } else {
                        // Not a scaling modification (e.g., move/rotate) – keep toolbar in sync
                        syncCanvasToToolbar(obj);
                    }
                }
            });
        canvas.on('selection:updated', handleCanvasSelection);
        canvas.on('selection:cleared', function() {
            // reset controls to defaults when nothing selected
            // (do not auto-apply)
        });
    }

    function bindFormInputHandlers() {
        $('#start_date').on('change', function() {
            const val = $(this).val();
            $('#end_date').prop('disabled', !val).attr('min', val).val(val || '');
        });

        $('#signature_count').on('change', function() {
            const count = parseInt($(this).val());
            $('.signature-block').hide();
            for (let i = 0; i < count; i++) $('#signature-block-' + i).show();
        }).trigger('change');
    }

    // ========== DATABASE HANDLERS ==========
    function bindDatabaseHandlers() {
        // Toggle between file and database source
        $('input[name="data_source"]').on('change', function() {
            const source = $(this).val();
            if (source === 'database') {
                $('#database-section').show();
                $('#file-section').hide();
                $('#participant_file').prop('required', false);
                
                // Reset state ketika switch ke database
                window.selectedKaryawanIds.clear();
                updateSelectedCount();
                
                loadKaryawanData(); // Load initial data
            } else {
                $('#database-section').hide();
                $('#file-section').show();
                $('#participant_file').prop('required', true);
                
                // Clear state ketika switch ke file
                window.selectedKaryawanIds.clear();
                updateSelectedCount();
            }
        });

        // Search and filter - AJAX version
        $('#search-btn').on('click', function() {
            loadKaryawanData();
        });

        $('#reset-search').on('click', function() {
            $('#search-karyawan').val('');
            $('#filter-divisi').val('');
            loadKaryawanData();
        });

        // Enter key for search
        $('#search-karyawan').on('keypress', function(e) {
            if (e.which === 13) {
                loadKaryawanData();
            }
        });

        // Filter change
        $('#filter-divisi').on('change', function() {
            loadKaryawanData();
        });

        // Select all/none functionality
        $('#check-all-karyawan').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.karyawan-checkbox').each(function() {
                const karyawanId = $(this).val();
                $(this).prop('checked', isChecked);
                
                if (isChecked) {
                    window.selectedKaryawanIds.add(karyawanId);
                } else {
                    window.selectedKaryawanIds.delete(karyawanId);
                }
            });
            updateSelectedCount();
        });

        $('#select-all-karyawan').on('click', function() {
            // Pilih semua data di semua halaman
            selectAllKaryawan();
        });

        $('#select-none-karyawan').on('click', function() {
            // Batal semua pilihan
            window.selectedKaryawanIds.clear();
            $('.karyawan-checkbox').prop('checked', false);
            $('#check-all-karyawan').prop('checked', false);
            updateSelectedCount();
        });

        // Individual checkbox change
        $(document).on('change', '.karyawan-checkbox', function() {
            const karyawanId = $(this).val();
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                window.selectedKaryawanIds.add(karyawanId);
            } else {
                window.selectedKaryawanIds.delete(karyawanId);
            }
            
            updateSelectedCount();
            updateSelectAllCheckbox();
        });

        // Pagination click handler
        $(document).on('click', '.karyawan-pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            if (url && !$(this).parent().hasClass('disabled')) {
                const urlParams = new URLSearchParams(url.split('?')[1]);
                const page = urlParams.get('page');
                loadKaryawanData(page);
            }
        });
    }

    // Function to load karyawan data via AJAX
    function loadKaryawanData(page = 1) {
        const search = $('#search-karyawan').val();
        const divisiFilter = $('#filter-divisi').val();

        // Show loading state
        const tableBody = $('#karyawan-table-body');
        tableBody.html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');

        $.ajax({
            url: '/karyawan/ajax',
            method: 'GET',
            data: {
                search: search,
                divisi_filter: divisiFilter,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    updateKaryawanTable(response.data);
                    updatePagination(response.pagination);
                    updateStatistics(response.pagination);
                    updateDivisiFilter(response.divisiList, response.divisiFilter);
                }
            },
            error: function(xhr) {
                console.error('Error loading karyawan data:', xhr);
                tableBody.html('<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>');
            }
        });
    }

    // Function to update table content
    function updateKaryawanTable(data) {
        const tableBody = $('#karyawan-table-body');
        let html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="5" class="text-center">Tidak ada data yang ditemukan</td></tr>';
        } else {
            data.forEach(function(karyawan) {
                const isSelected = window.selectedKaryawanIds.has(karyawan.id.toString());
                const checkedAttr = isSelected ? 'checked' : '';
                
                html += `
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_karyawan[]" value="${karyawan.id}" class="karyawan-checkbox" ${checkedAttr}>
                        </td>
                        <td>${karyawan.nama}</td>
                        <td>${karyawan.npk_id}</td>
                        <td>${karyawan.divisi}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-warning edit-karyawan-btn" 
                                    data-id="${karyawan.id}" 
                                    data-nama="${karyawan.nama}" 
                                    data-npk="${karyawan.npk_id}" 
                                    data-divisi="${karyawan.divisi}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-danger delete-karyawan-btn" data-id="${karyawan.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        tableBody.html(html);
        
        // Update checkbox "select all" berdasarkan state saat ini
        updateSelectAllCheckbox();
        updateSelectedCount();
    }

    // Function to update pagination
    function updatePagination(pagination) {
        const paginationContainer = $('#karyawan-pagination');
        let html = '';
        
        if (pagination.has_pages) {
            // Previous button
            if (pagination.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="?page=${pagination.current_page - 1}">‹</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">‹</span></li>`;
            }
            
            // Page numbers
            const start = Math.max(1, pagination.current_page - 2);
            const end = Math.min(pagination.last_page, pagination.current_page + 2);
            
            if (start > 1) {
                html += `<li class="page-item"><a class="page-link" href="?page=1">1</a></li>`;
                if (start > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            for (let i = start; i <= end; i++) {
                if (i === pagination.current_page) {
                    html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    html += `<li class="page-item"><a class="page-link" href="?page=${i}">${i}</a></li>`;
                }
            }
            
            if (end < pagination.last_page) {
                if (end < pagination.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="?page=${pagination.last_page}">${pagination.last_page}</a></li>`;
            }
            
            // Next button
            if (pagination.current_page < pagination.last_page) {
                html += `<li class="page-item"><a class="page-link" href="?page=${pagination.current_page + 1}">›</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">›</span></li>`;
            }
        }
        
        paginationContainer.html(html);
    }

    // Function to update statistics
    function updateStatistics(pagination) {
        const statsText = pagination.total > 0 
            ? `Menampilkan ${pagination.from} - ${pagination.to} dari ${pagination.total} data`
            : 'Tidak ada data';
        $('#karyawan-stats').text(statsText);
    }

    // Function to update divisi filter options
    function updateDivisiFilter(divisiList, selectedDivisi) {
        const select = $('#filter-divisi');
        const currentValue = selectedDivisi || select.val();
        
        let html = '<option value="">Semua Divisi</option>';
        
        // 🔧 Check if divisiList is an array, if not convert or handle gracefully
        if (Array.isArray(divisiList)) {
            divisiList.forEach(function(divisi) {
                const selected = divisi === currentValue ? 'selected' : '';
                html += `<option value="${divisi}" ${selected}>${divisi}</option>`;
            });
        } else if (divisiList && typeof divisiList === 'object') {
            // If it's an object, try to iterate its values
            Object.values(divisiList).forEach(function(divisi) {
                const selected = divisi === currentValue ? 'selected' : '';
                html += `<option value="${divisi}" ${selected}>${divisi}</option>`;
            });
        }
        
        select.html(html);
    }

    function updateSelectedCount() {
        const count = window.selectedKaryawanIds.size;
        $('#selected-count').text(count);
        if (count > 0) {
            $('#selected-info').show();
        } else {
            $('#selected-info').hide();
        }
    }

    // Function untuk update checkbox "select all" 
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.karyawan-checkbox').length;
        const checkedCheckboxes = $('.karyawan-checkbox:checked').length;
        
        if (totalCheckboxes === 0) {
            $('#check-all-karyawan').prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#check-all-karyawan').prop('checked', true);
        } else {
            $('#check-all-karyawan').prop('checked', false);
        }
    }

    // Function untuk select all data di semua halaman
    function selectAllKaryawan() {
        const search = $('#search-karyawan').val();
        const divisiFilter = $('#filter-divisi').val();

        // Show loading untuk feedback
        $('#select-all-karyawan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            url: '/karyawan/ajax/all-ids',
            method: 'GET',
            data: {
                search: search,
                divisi_filter: divisiFilter
            },
            success: function(response) {
                if (response.success) {
                    // Add all IDs to selected set
                    response.ids.forEach(function(id) {
                        window.selectedKaryawanIds.add(id.toString());
                    });
                    
                    // Update current page checkboxes
                    $('.karyawan-checkbox').prop('checked', true);
                    $('#check-all-karyawan').prop('checked', true);
                    updateSelectedCount();
                    
                    //alert(`${response.ids.length} karyawan telah dipilih dari semua halaman`);
                }
            },
            error: function(xhr) {
                console.error('Error selecting all karyawan:', xhr);
                alert('Gagal memilih semua data');
            },
            complete: function() {
                $('#select-all-karyawan').prop('disabled', false).html('<i class="fas fa-check-square"></i> Pilih Semua');
            }
        });
    }

    // ========== KARYAWAN CRUD ==========
    function bindKaryawanCRUD() {
        // Add new karyawan
        $('#add-karyawan-btn').on('click', function() {
            $('#karyawan-modal-title').text('Tambah Karyawan');
            $('#karyawan-form')[0].reset();
            $('#karyawan-id').val('');
            resetNamaValidation();
            $('#karyawan-modal').modal('show');
        });

        // Edit karyawan
        $(document).on('click', '.edit-karyawan-btn', function() {
            const id = $(this).data('id');
            const nama = $(this).data('nama');
            const npk = $(this).data('npk');
            const divisi = $(this).data('divisi');

            $('#karyawan-modal-title').text('Edit Karyawan');
            $('#karyawan-id').val(id);
            $('#karyawan-nama').val(nama);
            $('#karyawan-npk').val(npk);
            $('#karyawan-divisi').val(divisi);
            
            // Trigger validation for existing name
            validateNamaInput();
            $('#karyawan-modal').modal('show');
        });

        // Real-time validation for nama input
        $('#karyawan-nama').on('input', function() {
            validateNamaInput();
        });

        // Save karyawan
        $('#karyawan-form').on('submit', function(e) {
            e.preventDefault();
            
            // Final validation before submit
            if (!validateNamaInput()) {
                return false;
            }
            
            const id = $('#karyawan-id').val();
            const isEdit = id !== '';
            const url = isEdit ? `/karyawan/${id}` : '/karyawan';
            const method = isEdit ? 'PUT' : 'POST';

            const data = {
                nama: $('#karyawan-nama').val(),
                npk_id: $('#karyawan-npk').val(),
                divisi: $('#karyawan-divisi').val(),
                _token: '{{ csrf_token() }}'
            };

            if (isEdit) {
                data._method = 'PUT';
            }

            // Disable submit button
            $('#save-karyawan-btn').prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#karyawan-modal').modal('hide');
                        loadKaryawanData(); // Reload table via AJAX
                    }
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        let errorMsg = 'Validation errors:\n';
                        Object.keys(errors).forEach(key => {
                            errorMsg += `- ${errors[key][0]}\n`;
                        });
                        alert(errorMsg);
                    } else {
                        alert('Terjadi kesalahan');
                    }
                },
                complete: function() {
                    // Re-enable submit button
                    $('#save-karyawan-btn').prop('disabled', false).text('Simpan');
                }
            });
        });

        // Delete karyawan
        $(document).on('click', '.delete-karyawan-btn', function() {
            const id = $(this).data('id');
            if (confirm('Apakah Anda yakin ingin menghapus karyawan ini?')) {
                $.ajax({
                    url: `/karyawan/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            loadKaryawanData(); // Reload table via AJAX
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus');
                    }
                });
            }
        });
    }

    // Nama validation functions
    function validateNamaInput() {
        const namaInput = $('#karyawan-nama');
        const nama = namaInput.val();
        const length = nama.length;
        const maxLength = 25;
        
        // Update counter
        updateNamaCounter(length, maxLength);
        
        // Validate length
        if (length > maxLength) {
            setNamaError('Nama tidak boleh lebih dari 25 karakter');
            return false;
        } else if (length === 0) {
            setNamaError('Nama tidak boleh kosong');
            return false;
        } else {
            setNamaValid();
            return true;
        }
    }
    
    function updateNamaCounter(current, max) {
        const counter = $('#nama-counter');
        counter.text(`${current}/${max} karakter`);
        
        if (current > max) {
            counter.removeClass('text-muted text-success').addClass('text-danger');
        } else if (current > 0) {
            counter.removeClass('text-muted text-danger').addClass('text-success');
        } else {
            counter.removeClass('text-success text-danger').addClass('text-muted');
        }
    }
    
    function setNamaError(message) {
        const namaInput = $('#karyawan-nama');
        const errorElement = $('#nama-error');
        
        namaInput.removeClass('is-valid').addClass('is-invalid');
        errorElement.text(message).show();
        $('#save-karyawan-btn').prop('disabled', true);
    }
    
    function setNamaValid() {
        const namaInput = $('#karyawan-nama');
        const errorElement = $('#nama-error');
        
        namaInput.removeClass('is-invalid').addClass('is-valid');
        errorElement.hide();
        $('#save-karyawan-btn').prop('disabled', false);
    }
    
    function resetNamaValidation() {
        const namaInput = $('#karyawan-nama');
        const errorElement = $('#nama-error');
        const counter = $('#nama-counter');
        
        namaInput.removeClass('is-valid is-invalid');
        errorElement.hide();
        counter.text('0/25 karakter').removeClass('text-success text-danger').addClass('text-muted');
        $('#save-karyawan-btn').prop('disabled', false);
    }


    // ========== PREVIEW SUBMISSION ==========
    function handlePreview(canvas) {
        // 🔧 Validate canvas is ready
        if (!canvas) {
            alert('Canvas tidak ditemukan. Silakan refresh halaman.');
            console.error('Canvas is null or undefined');
            return;
        }
        
        // Check if canvas has the underlying canvas element
        const canvasElement = canvas.lowerCanvasEl || canvas.upperCanvasEl;
        if (!canvasElement) {
            alert('Canvas element tidak ditemukan. Silakan refresh halaman.');
            console.error('Canvas element is null');
            return;
        }
        
        // Ensure canvas is rendered
        try {
            canvas.renderAll();
        } catch (err) {
            console.error('Error rendering canvas:', err);
            alert('Terjadi kesalahan saat memproses canvas. Silakan coba lagi.');
            return;
        }
        
        // Update template JSON
        let templateJson;
        try {
            templateJson = JSON.stringify(canvas.toJSON(['isPlaceholder', 'placeholderType', 'isSignatureBlock', 'signatureIndex', 'signatureField', 'areaKey', 'isSignatureName', 'isSignatureTitle']));
        } catch (err) {
            console.error('Error converting canvas to JSON:', err);
            alert('Terjadi kesalahan saat menyimpan template. Silakan coba lagi.');
            return;
        }
        
        // Get main form and create FormData from it
        const mainForm = document.getElementById('main-form');
        const formData = new FormData(mainForm);
        
        // Override template_json with current canvas state
        formData.set('template_json', templateJson);
        
        // Get data source
        const dataSource = document.querySelector('input[name="data_source"]:checked');
        
        // Debug: Log all form data being sent
        console.log('=== Preview Form Data ===');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`${key}: [File] ${value.name}`);
            } else {
                console.log(`${key}: ${value}`);
            }
        }
        
        if (dataSource && dataSource.value === 'database') {
            console.log('Data Source: database');
            // Add selected karyawan IDs
            window.selectedKaryawanIds.forEach(function(id) {
                formData.append('selected_karyawan[]', id);
                console.log(`selected_karyawan[]: ${id}`);
            });
        }
        
        console.log('=== End Preview Form Data ===');
        
        // Use XMLHttpRequest to submit form with file upload to new tab
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route('certificates.render.preview') }}', true);
        
        // This is important: We need to handle the response as a new window
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Open response in new tab
                const newWindow = window.open('', '_blank');
                newWindow.document.write(xhr.responseText);
                newWindow.document.close();
            } else {
                console.error('Preview error:', xhr.statusText);
                alert('Terjadi kesalahan saat membuka preview. Silakan coba lagi.');
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error');
            alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
        };
        
        // Send the FormData (includes files automatically)
        xhr.send(formData);
    }

    // ========== GENERATE SUBMISSION ==========
    function handleGenerate(canvas) {
        // 🔧 Validate canvas is ready and has proper context
        if (!canvas) {
            alert('Canvas tidak ditemukan. Silakan refresh halaman.');
            console.error('Canvas is null or undefined');
            return;
        }
        
        // Check if canvas has the underlying canvas element
        const canvasElement = canvas.lowerCanvasEl || canvas.upperCanvasEl;
        if (!canvasElement) {
            alert('Canvas element tidak ditemukan. Silakan refresh halaman.');
            console.error('Canvas element is null');
            return;
        }
        
        // Check if context is available
        try {
            const ctx = canvasElement.getContext('2d');
            if (!ctx) {
                alert('Canvas context tidak tersedia. Silakan tunggu sebentar dan coba lagi.');
                console.error('Canvas context is null');
                return;
            }
        } catch (err) {
            console.error('Error getting canvas context:', err);
            alert('Terjadi kesalahan saat mengakses canvas. Silakan refresh halaman.');
            return;
        }

        // 🆕 VALIDATE REQUIRED FIELDS BEFORE SUBMITTING
        const eventName = $('input[name="event_name"]').val();
        const certificateType = $('input[name="certificate_type"]').val();
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        const signingDate = $('input[name="signing_date"]').val();
        const signingPlace = $('input[name="signing_place"]').val();
        const certificateNumberPrefix = $('input[name="certificate_number_prefix"]').val();

        const missingFields = [];
        if (!eventName) missingFields.push('Nama Acara (@{{nama_acara}})');
        if (!certificateType) missingFields.push('Jenis Sertifikat (@{{jenis_sertifikat}})');
        if (!startDate || !endDate) missingFields.push('Tanggal Acara (@{{tanggal_acara}})');
        if (!signingDate || !signingPlace) missingFields.push('Tanggal & Tempat Penandatanganan (@{{tanggal_penandatanganan}})');
        if (!certificateNumberPrefix) missingFields.push('Format Nomor Sertifikat (@{{nomor_sertifikat}})');

        if (missingFields.length > 0) {
            alert('Mohon lengkapi placeholder berikut terlebih dahulu:\\n\\n' + 
                  missingFields.join('\\n') + 
                  '\\n\\nKlik placeholder di canvas untuk mengisi data.');
            return;
        }

        // Validate data source
        const dataSource = $('input[name="data_source"]:checked').val();
        
        if (dataSource === 'database') {
            const selectedCount = window.selectedKaryawanIds.size;
            if (selectedCount === 0) {
                alert('Pilih minimal 1 karyawan untuk generate sertifikat');
                return;
            }
            
            // Add all selected IDs as hidden inputs to form
            $('#main-form input[name="selected_karyawan[]"]').remove(); // Remove existing
            window.selectedKaryawanIds.forEach(function(id) {
                $('#main-form').append(`<input type="hidden" name="selected_karyawan[]" value="${id}">`);
            });
        } else {
            const fileInput = document.getElementById('participant_file');
            if (!fileInput.files.length) {
                alert('Pilih file data peserta');
                return;
            }
        }

        // 🔧 Ensure canvas is rendered before converting to JSON/DataURL
        try {
            // Deselect all objects to avoid selection artifacts
            canvas.discardActiveObject();
            canvas.renderAll();
            
            // Longer delay to ensure render is complete and context is ready
            setTimeout(() => {
                proceedWithGenerate(canvas);
            }, 300);
        } catch (err) {
            console.error('Error rendering canvas:', err);
            alert('Terjadi kesalahan saat memproses canvas. Silakan coba lagi.');
            return;
        }
    }
    
    function proceedWithGenerate(canvas) {
        try {
            $('#template_json').val(JSON.stringify(canvas.toJSON(['isPlaceholder', 'placeholderType', 'isSignatureBlock', 'signatureIndex', 'signatureField', 'areaKey', 'isSignatureName', 'isSignatureTitle'])));
        } catch (err) {
            console.error('Error converting canvas to JSON:', err);
            alert('Terjadi kesalahan saat menyimpan template. Silakan coba lagi.');
            return;
        }
        
        const form = document.getElementById('main-form');
        const formData = new FormData(form);

        $('#progress-bar-wrapper').show();
        const bar = document.getElementById('progress-bar');
        bar.style.width = '0%'; 
        bar.innerText = 'Initializing project...';
        bar.classList.add('progress-bar-animated');
        bar.classList.add('progress-bar-striped');

        console.log('🚀 Starting project generation...');

        // 🔧 ROBUST SOLUTION: Export canvas using multiple fallback methods
        let dataUrl;
        
        try {
            // Method 1: Direct export from lower canvas (fastest, most reliable)
            const canvasEl = canvas.lowerCanvasEl;
            if (!canvasEl) {
                throw new Error('Canvas element not found');
            }
            
            // Ensure canvas is fully rendered
            canvas.renderAll();
            
            // Use the native canvas toDataURL
            dataUrl = canvasEl.toDataURL('image/png', 1.0);
            
            // Validate the result
            if (!dataUrl || dataUrl.length < 100 || !dataUrl.startsWith('data:image')) {
                throw new Error('Invalid data URL generated');
            }
            
        } catch (err1) {
            console.warn('Method 1 (lowerCanvasEl) failed:', err1);
            
            try {
                // Method 2: Use fabric's toDataURL with minimal options
                canvas.renderAll();
                dataUrl = canvas.toDataURL('png');
                
                if (!dataUrl || dataUrl.length < 100) {
                    throw new Error('Invalid data URL');
                }
                
            } catch (err2) {
                console.warn('Method 2 (fabric toDataURL) failed:', err2);
                
                try {
                    // Method 3: Create a new canvas and manually render
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = canvas.width;
                    tempCanvas.height = canvas.height;
                    const tempCtx = tempCanvas.getContext('2d', { 
                        willReadFrequently: false,
                        alpha: true 
                    });
                    
                    // Clear canvas
                    tempCtx.clearRect(0, 0, tempCanvas.width, tempCanvas.height);
                    
                    // Draw background color
                    if (canvas.backgroundColor) {
                        tempCtx.fillStyle = canvas.backgroundColor;
                        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                    }
                    
                    // Copy from fabric canvas
                    if (canvas.lowerCanvasEl) {
                        tempCtx.drawImage(canvas.lowerCanvasEl, 0, 0);
                    }
                    
                    dataUrl = tempCanvas.toDataURL('image/png', 1.0);
                    
                    if (!dataUrl || dataUrl.length < 100) {
                        throw new Error('Failed to generate valid image');
                    }
                    
                } catch (err3) {
                    console.error('All export methods failed:', err3);
                    alert('Tidak dapat mengkonversi canvas ke gambar. Silakan:\n1. Refresh halaman\n2. Muat ulang template\n3. Coba lagi');
                    $('#progress-bar-wrapper').hide();
                    return;
                }
            }
        }
        
        console.log('Canvas export successful, data URL length:', dataUrl.length);
        formData.append('canvas_image', dataUrl);

        // 🔧 IMPORTANT: Start a placeholder polling to show indeterminate progress
        // This gives better UX while waiting for server response
        let pendingProjectId = null;
        let pollingStarted = false;
        
        // Simulate progress while waiting (fake smooth progress 0-10%)
        let fakeProgress = 0;
        const fakeInterval = setInterval(() => {
            if (!pollingStarted && fakeProgress < 10) {
                fakeProgress += 1;
                bar.style.width = fakeProgress + '%';
                bar.innerText = `${fakeProgress}% - Initializing...`;
            } else {
                clearInterval(fakeInterval);
            }
        }, 200);

        fetch(form.action, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value},
            body: formData
        })
        .then(response => {
            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check content type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // If not JSON, try to get text for debugging
                return response.text().then(text => {
                    console.error('Server returned non-JSON response:', text.substring(0, 500));
                    throw new Error('Server mengembalikan response yang tidak valid. Periksa console untuk detail.');
                });
            }
            
            return response.json();
        })
        .then(data => {
            // NEW: Handle project-based workflow with real-time progress
            if (data.success && data.project_id) {
                console.log('✅ Project created, ID:', data.project_id);
                
                // Stop fake progress
                clearInterval(fakeInterval);
                pollingStarted = true;
                
                // Start real polling for this project
                startGenerationPolling(data.project_id, data.total_certificates, data.redirect_url);
            }
            // LEGACY: Old batch workflow (for backward compatibility)
            else if (data.batchId) {
                startPolling(data.batchId);
            } else if (data.error) {
                throw new Error(data.error);
            } else {
                throw new Error('Response tidak mengandung data yang valid');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            bar.classList.add('bg-danger');
            bar.innerText = '❌ ' + (error.message || 'Terjadi kesalahan');
            
            // Show more detailed alert
            alert('Terjadi kesalahan saat generate sertifikat:\n\n' + error.message + '\n\nSilakan periksa:\n1. File data peserta sudah valid\n2. Template sudah disimpan\n3. Semua field wajib sudah diisi');
        });
    }

    function startGenerationPolling(projectId, totalCertificates, redirectUrl) {
        const bar = document.getElementById('progress-bar');
        let lastPercentage = 0;
        let pollCount = 0;
        let tabOpened = false; // 🔧 FIX: Prevent double tab opening
        
        console.log(`🔄 Starting progress polling for project ${projectId}, total: ${totalCertificates}`);
        
        const interval = setInterval(() => {
            pollCount++;
            
            fetch(`/projects/${projectId}/generation-progress`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.progress) {
                        const progress = data.progress;
                        const pct = progress.percentage || 0;
                        const current = progress.current || 0;
                        const total = progress.total || totalCertificates;
                        
                        // Debug log only when progress changes
                        if (pct !== lastPercentage || pollCount <= 3) {
                            console.log(`Progress: ${pct}% - ${current}/${total} - ${progress.currentName || 'Processing...'}`);
                        }
                        
                        // Update progress bar (override fake progress)
                        if (pct > 0 || current > 0) {
                            bar.style.width = pct + '%';
                            bar.innerText = `${pct}% - Generating ${current}/${total}: ${progress.currentName || '...'}`;
                        }
                        
                        lastPercentage = pct;
                        
                        // Check if completed (🔧 FIX: Only open tab once)
                        if ((progress.status === 'completed' || current >= total) && !tabOpened) {
                            tabOpened = true; // 🔧 FIX: Mark as opened
                            clearInterval(interval);
                            
                            console.log('✅ Generation completed!');
                            
                            bar.classList.remove('progress-bar-animated');
                            bar.classList.remove('progress-bar-striped');
                            bar.classList.add('bg-success');
                            bar.style.width = '100%';
                            bar.innerText = `✅ ${totalCertificates} canvas states created! Opening editor...`;
                            
                            // Open project editor in NEW TAB (only once)
                            setTimeout(() => {
                                window.open(redirectUrl, '_blank');
                                
                                // Show success message
                                bar.innerText = `✅ Project created! Editor opened in new tab.`;
                                
                                // Optional: Reset form
                                setTimeout(() => {
                                    if (confirm('Project editor dibuka di tab baru. Reset form untuk generate project lain?')) {
                                        location.reload();
                                    }
                                }, 2000);
                            }, 1000);
                        }
                    }
                })
                .catch(err => {
                    console.error('Progress polling error:', err);
                    // Don't stop polling on error, just log it
                });
        }, 300); // Poll every 300ms for smoother updates
    }

    function startPolling(batchId) {
        const bar = document.getElementById('progress-bar');
        const interval = setInterval(() => {
            fetch(`/progress-status/${batchId}`)
                .then(res => res.json())
                .then(data => {
                    const pct = Math.round((data.completed / data.total) * 100);
                    bar.style.width = pct + '%';
                    bar.innerText = pct + '%';

                    if (data.completed >= data.total) {
                        if (data.is_zipped && data.download_url) {
                            clearInterval(interval);
                            bar.classList.remove('progress-bar-animated');
                            bar.classList.add('bg-success');
                            bar.innerText = '✅ Selesai! Mengunduh ZIP...';
                            
                            // Automatically start download using the provided URL
                            const link = document.createElement('a');
                            link.href = data.download_url;
                            link.setAttribute('download', data.zip_filename || 'sertifikat.zip');
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            // Show success message
                            setTimeout(() => {
                                bar.innerText = '✅ Download selesai!';
                                $('#progress-bar-wrapper').delay(3000).fadeOut();
                            }, 1000);
                        } else {
                            bar.innerText = 'Membuat ZIP...';
                        }
                    }
                })
                .catch(err => {
                    console.error('Polling error:', err);
                    clearInterval(interval);
                    bar.classList.add('bg-danger');
                    bar.innerText = '❌ Terjadi kesalahan';
                });
        }, 3000);
    }

    function slugify(text) {
        return text.toLowerCase().normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function addSignatureBlock(canvas, index, imageObj = null) {
        const number = index + 1;
        const namePlaceholder = `@{{nama_penandatangan_${number}}}`;
        const titlePlaceholder = `@{{jabatan_penandatangan_${number}}}`;

        let signatureImage = imageObj ?? new fabric.Rect({
            width: 180, height: 90,
            fill: 'rgba(0,0,0,0.05)',
            stroke: '#ccc',
            strokeDashArray: [5, 5]
        });

        signatureImage.set({ top: 0, left: 0, originX: 'center', originY: 'top' });

        const nameText = new fabric.IText(namePlaceholder, {
            fontSize: 16, 
            fontWeight: 'bold',
            fill: '#000', 
            top: 90 + 5, 
            left: 0,
            originX: 'center', 
            originY: 'top',
            // 🎨 IMPROVE TEXT RENDERING QUALITY
            strokeWidth: 0,
            paintFirst: 'fill'
        });

        const titleText = new fabric.IText(titlePlaceholder, {
            fontSize: 14, 
            fill: '#333',
            top: 90 + 30, 
            left: 0,
            originX: 'center', 
            originY: 'top',
            // 🎨 IMPROVE TEXT RENDERING QUALITY
            strokeWidth: 0,
            paintFirst: 'fill'
        });

        // Tag children for robust identification and sub-selection
        nameText.isSignatureName = true;
        nameText.signatureIndex = index;
        nameText.lockMovementX = true;
        nameText.lockMovementY = true;
        nameText.evented = true;

        titleText.isSignatureTitle = true;
        titleText.signatureIndex = index;
        titleText.lockMovementX = true;
        titleText.lockMovementY = true;
        titleText.evented = true;

        const group = new fabric.Group([signatureImage, nameText, titleText], {
            left: 250 + (index * 350), top: 500,
            originX: 'center', originY: 'top',
            hasControls: true, hasBorders: false,
            lockUniScaling: true,
            isSignatureBlock: true, signatureIndex: index,
            subTargetCheck: true,
            perPixelTargetFind: true,
            targetFindTolerance: 8
        });

        canvas.add(group);
        canvas.setActiveObject(group);
    }

    function replaceSignaturePlaceholderWithImage(canvas, index, imageUrl) {
        // Find signature block on canvas by index
        const group = canvas.getObjects().find(obj =>
            obj.type === 'group' && obj.isSignatureBlock && obj.signatureIndex === index
        );
        
        if (!group) {
            return;
        }

    fabric.Image.fromURL(imageUrl, function(img) {
            // Configure the signature image
            img.set({ 
                originX: 'center', 
                originY: 'top', 
                top: 0, 
                left: 0 
            });
            
            // Scale image to fit signature area
            img.scaleToWidth(180); 
            img.scaleToHeight(90);

            // Get existing text elements from the group
            const groupObjects = group._objects || group.getObjects();
            let nameText = groupObjects.find(o => (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') && o.isSignatureName === true);
            let titleText = groupObjects.find(o => (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') && o.isSignatureTitle === true);
            
            // Backfill flags if missing (legacy templates)
            const textObjsAll = groupObjects.filter(o => o.type === 'i-text' || o.type === 'text' || o.type === 'textbox');
            if (!nameText && textObjsAll[0]) {
                nameText = textObjsAll[0];
                nameText.isSignatureName = true;
                nameText.signatureIndex = index;
            }
            if (!titleText && textObjsAll[1]) {
                titleText = textObjsAll[1];
                titleText.isSignatureTitle = true;
                titleText.signatureIndex = index;
            }

            // Position text below the image
            if (nameText) {
                nameText.set({ 
                    top: img.getScaledHeight() + 5,
                    left: 0,
                    originX: 'center', 
                    originY: 'top' 
                });
            }
            
            if (titleText) {
                titleText.set({ 
                    top: img.getScaledHeight() + 30,
                    left: 0,
                    originX: 'center', 
                    originY: 'top' 
                });
            }

            // Create new group with image and text
            const newObjects = [img];
            if (nameText) newObjects.push(nameText);
            if (titleText) newObjects.push(titleText);

            const newGroup = new fabric.Group(newObjects, {
                left: group.left,
                top: group.top,
                originX: group.originX || 'center',
                originY: group.originY || 'top',
                hasControls: true, 
                hasBorders: true,
                lockUniScaling: true,
                isSignatureBlock: true, 
                signatureIndex: index,
                subTargetCheck: true,
                perPixelTargetFind: true,
                targetFindTolerance: 8
            });

            // Replace old group with new one
            canvas.remove(group);
            canvas.add(newGroup);
            canvas.setActiveObject(newGroup);
            canvas.requestRenderAll();
        });
    }

    // Certificate number preview functionality
    function setupCertificateNumberPreview() {
        const prefixInput = document.getElementById('certificate_number_prefix');
        if (!prefixInput) return;

        const helpText = prefixInput.parentNode ? prefixInput.parentNode.querySelector('.form-text') : null;
        if (!helpText) return;
        
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
                preview = `<strong>Preview:</strong> ${example1}, ${example2}, ${example3}, ...`;
            }
            // Check for {AUTO} format (default start from 1)
            else if (prefix.includes('{AUTO}')) {
                const example1 = prefix.replace('{AUTO}', '001');
                const example2 = prefix.replace('{AUTO}', '002');
                const example3 = prefix.replace('{AUTO}', '003');
                preview = `<strong>Preview:</strong> ${example1}, ${example2}, ${example3}, ...`;
            } 
            // Legacy format - numbers at end
            else if (/\d+$/.test(prefix)) {
                const match = prefix.match(/^(.+?)(\d+)$/);
                if (match) {
                    const basePrefix = match[1];
                    const startNum = parseInt(match[2]);
                    const pad = match[2].length;
                    const example1 = basePrefix + String(startNum).padStart(pad, '0');
                    const example2 = basePrefix + String(startNum + 1).padStart(pad, '0');
                    const example3 = basePrefix + String(startNum + 2).padStart(pad, '0');
                    preview = `<strong>Preview:</strong> ${example1}, ${example2}, ${example3}, ...`;
                }
            } else {
                // No pattern - will append counter
                preview = `<strong>Preview:</strong> ${prefix}-001, ${prefix}-002, ${prefix}-003, ...`;
            }
            
            if (preview) {
                const currentText = helpText.innerHTML;
                const lines = currentText.split('<br>');
                // Replace or add preview line
                const previewLineIndex = lines.findIndex(line => line.includes('Preview:'));
                if (previewLineIndex >= 0) {
                    lines[previewLineIndex] = preview;
                } else {
                    lines.push(preview);
                }
                helpText.innerHTML = lines.join('<br>');
            }
        }
        
        // Update preview on input with slight delay
        let timeoutId;
        prefixInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(updatePreview, 300);
        });
        
        // Initial preview if field has value
        if (prefixInput.value.trim()) {
            updatePreview();
        }
    }

    function applyExistingSignatureImages(canvas) {
        // Check all signature file inputs for uploaded images
        for (let i = 0; i < 3; i++) {
            const fileInput = document.getElementById(`signature_image_${i}`);
            const previewImg = document.getElementById(`signature_preview_${i}`);
            
            // If there's a file in the input and preview is showing
            if (fileInput && fileInput.files && fileInput.files[0] && 
                previewImg && previewImg.style.display !== 'none' && previewImg.src) {
                replaceSignaturePlaceholderWithImage(canvas, i, previewImg.src);
            }
        }
    }

    // Generate a stable, random areaKey for objects
    function generateAreaKey() {
        // area_ + 8 hex chars
        return 'area_' + Math.random().toString(16).slice(2, 10);
    }

    // Apply design settings (areas object) to canvas objects based on areaKey
    function applyDesignSettingsToCanvas(canvas, areas) {
        canvas.getObjects().forEach((obj) => {
            const key = obj.areaKey || obj.name;
            if (!key) return;
            const settings = areas[key];
            if (!settings) return;

            // Apply basic style properties
            if (settings.fontFamily) obj.set('fontFamily', settings.fontFamily);
            if (settings.fontSize) obj.set('fontSize', settings.fontSize);
            if (settings.color) obj.set('fill', settings.color);
            if (settings.textAlign) obj.set('textAlign', settings.textAlign);
            if (settings.lineHeight) obj.set('lineHeight', settings.lineHeight);
            if (settings.maxLines) obj.maxLines = settings.maxLines;
            if (obj.setCoords) obj.setCoords();
        });
        canvas.requestRenderAll();
    }
    
    // 📊 DATATABLES: Initialize template table with pagination
    let templatesDataTable;
    $(document).ready(function() {
        templatesDataTable = $('#templates-table').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[1, "desc"]], // Sort by date column (newest first)
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ template",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ template",
                "infoEmpty": "Tidak ada template",
                "infoFiltered": "(difilter dari _MAX_ total template)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                },
                "emptyTable": "Belum ada template yang disimpan. Buat desain di bawah dan klik \"Simpan Template\"."
            },
            "columnDefs": [
                { "orderable": false, "targets": 2 } // Disable sorting on action column
            ]
        });
    });
</script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
@endpush
