@extends('layouts.app')

@section('title', 'Generate Sertifikat')
@section('content-title', 'Generate Sertifikat & Editor Template')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Generate & Edit</li>
@endsection

@section('content')
{{-- Bagian Form Input Data --}}
<div class="row">
    <div class="col-lg-12">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Terjadi Kesalahan!</h5>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Menampilkan pesan sukses setelah menyimpan template --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
                {{ session('success') }}
            </div>
        @endif

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Langkah 1: Isi Data Sertifikat</h3>
            </div>
            <form id="main-form" action="{{ route('certificates.bulk.download') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="template_json" id="template_json">
                
                <div class="card-body">
                    {{-- Semua input form ada di sini --}}
                    <div class="form-group">
                        <label for="certificate_type">Jenis Sertifikat</label>
                        <select class="form-control" id="certificate_type" name="certificate_type">
                            <option value="PARTISIPASI">Partisipasi</option>
                            <option value="KEPANITIAAN">Kepanitiaan</option>
                            <option value="KEANGGOTAAN">Keanggotaan</option>
                            <option value="PENGHARGAAN">Penghargaan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event_name">Nama Acara/Pelatihan</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" placeholder="Masukkan nama acara" required>
                    </div>
                    <div class="form-group">
                        <label for="certificate_number_prefix">
                            Nomor Sertifikat 
                            <i class="fas fa-info-circle text-info" data-toggle="tooltip" 
                               title="Gunakan {AUTO} atau {AUTO:start_number} untuk kontrol penuh penomoran"></i>
                        </label>
                        <input type="text" class="form-control" id="certificate_number_prefix" name="certificate_number_prefix" 
                               placeholder="Contoh: CERT-{AUTO:100}-2025 atau PKL-{AUTO:50} atau {AUTO:1000}" required>
                        <small class="form-text text-muted">
                            <strong>Format Fleksibel:</strong><br>
                            • <code>{AUTO}</code> → mulai dari 001: <code>CERT-{AUTO}-2025</code> → CERT-001-2025, CERT-002-2025<br>
                            • <code>{AUTO:100}</code> → mulai dari 100: <code>CERT-{AUTO:100}-2025</code> → CERT-100-2025, CERT-101-2025<br>
                            • <code>{AUTO:50}</code> → mulai dari 50: <code>PKL-{AUTO:50}</code> → PKL-050, PKL-051<br>
                            • <code>{AUTO:1000}</code> → mulai dari 1000: <code>{AUTO:1000}</code> → 1000, 1001, 1002<br>
                            <em>Format lama masih didukung untuk backward compatibility</em>
                        </small>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label for="start_date">Tanggal Mulai Acara</label><input type="date" class="form-control" id="start_date" name="start_date" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label for="end_date">Tanggal Akhir Acara</label><input type="date" class="form-control" id="end_date" name="end_date" required disabled></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="signing_place">Tempat Penandatanganan</label>
                                <input type="text" class="form-control" id="signing_place" name="signing_place" placeholder="Contoh: Bandung" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="signing_date">Tanggal Penandatanganan Sertifikat</label>
                                <input type="date" class="form-control" id="signing_date" name="signing_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Kustom (Opsional)</label>
                        <input type="text" class="form-control mb-2" name="descriptions[0]" placeholder="Deskripsi Kustom 1 (untuk placeholder @{{deskripsi_1}})">
                        <input type="text" class="form-control mb-2" name="descriptions[1]" placeholder="Deskripsi Kustom 2 (untuk placeholder @{{deskripsi_2}})">
                        <input type="text" class="form-control" name="descriptions[2]" placeholder="Deskripsi Kustom 3 (untuk placeholder @{{deskripsi_3}})">
                    </div>
                    
                    <hr>
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
                                            @foreach($divisiList as $kode => $nama)
                                                <option value="{{ $kode }}" {{ $divisiFilter == $kode ? 'selected' : '' }}>{{ $nama }}</option>
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
                                                    <td>{{ $k->npk }}</td>
                                                    <td>{{ $k->divisi->inisial_unit ?? $k->kode_divisi }}</td>
                                                    <td>
                                                        <button type="button" class="btn btn-xs btn-warning edit-karyawan-btn" 
                                                                data-id="{{ $k->id }}" 
                                                                data-nama="{{ $k->nama }}" 
                                                                data-npk="{{ $k->npk }}" 
                                                                data-divisi="{{ $k->kode_divisi }}">
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
                    <hr>
                    <h4>Pengaturan Tanda Tangan</h4>
                    <div class="form-group"><label for="signature_count">Jumlah Tanda Tangan</label><select class="form-control" id="signature_count" name="signature_count"><option value="1">1 Tanda Tangan</option><option value="2" selected>2 Tanda Tangan</option><option value="3">3 Tanda Tangan</option></select></div>
                    <div class="row">
                        @for ($i = 0; $i < 3; $i++)
                        <div class="col-md-4 signature-block" id="signature-block-{{ $i }}" style="display: {{ $i < 2 ? 'block' : 'none' }};">
                            <div class="card card-outline card-secondary">
                                <div class="card-header"><h3 class="card-title">Penandatangan #{{ $i + 1 }}</h3></div>
                                <div class="card-body">
                                    <div class="form-group"><label for="signature_name_{{ $i }}">Nama Lengkap</label><input type="text" class="form-control" name="signatures[{{ $i }}][name]" id="signature_name_{{ $i }}"></div>
                                    <div class="form-group"><label for="signature_title_{{ $i }}">Jabatan</label><input type="text" class="form-control" name="signatures[{{ $i }}][title]" id="signature_title_{{ $i }}"></div>
                                    <div class="form-group">
                                        <label for="signature_image_{{ $i }}">Gambar Tanda Tangan</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="signatures[{{ $i }}][image]" id="signature_image_{{ $i }}" accept="image/png">
                                            <label class="custom-file-label" for="signature_image_{{ $i }}">Pilih file</label>
                                        </div>
                                        <img id="signature_preview_{{ $i }}" src="#" alt="Preview Tanda Tangan" class="mt-2" style="max-height: 60px; display: none;"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endfor
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
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama Template</th>
                    <th>Tanggal Dibuat</th>
                    <th style="width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
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
    <div class="card-header"><h3 class="card-title">Langkah 3: Desain Template Sertifikat</h3></div>
    <div class="card-body">
        <div class="mb-3">
            <div class="form-group">
                <label for="bg-upload">Unggah Gambar Latar</label>
                <div class="custom-file"><input type="file" class="custom-file-input" id="bg-upload" accept="image/*"><label class="custom-file-label" for="bg-upload">Pilih file</label></div>
            </div>
            <hr>
            <button id="add-text" class="btn btn-default"><i class="fas fa-font"></i> Tambah Teks</button>
            {{-- PERUBAHAN: Dropdown diubah menjadi "Sisipkan Elemen" --}}
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
            <button id="remove-element" class="btn btn-danger float-right"><i class="fas fa-trash"></i> Hapus Elemen</button>
        </div>
        <div style="border: 1px solid #ccc; width: 1123px; height: 794px; margin: auto;"><canvas id="certificate-canvas"></canvas></div>
    </div>
    <div class="card-footer">
        <button type="button" id="save-template" class="btn btn-success"><i class="fas fa-save"></i> Simpan Desain Saat Ini</button>
        <button type="button" id="preview-btn" class="btn btn-info"><i class="fas fa-eye"></i> Preview</button>
        <button type="button" id="generate-btn-final" class="btn btn-primary float-right"><i class="fas fa-download"></i> Generate & Download ZIP</button>
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
</style>
<script>
    $(document).ready(function() {
        const canvas = window.canvas=new fabric.Canvas('certificate-canvas', {
            width: 1123, // A4 landscape width: 29.7cm at 96 DPI
            height: 794, // A4 landscape height: 21cm at 96 DPI
            backgroundColor: '#ffffff'
        });

        // Global state untuk menyimpan checkbox yang dipilih di semua halaman
        window.selectedKaryawanIds = new Set();

        // ========== 1. === EDITOR & PLACEHOLDER ==========
        initCanvasEvents(canvas);
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
                fontFamily: 'helvetica', fontSize: 24, fill: '#000000'
            });
            canvas.add(text);
            canvas.setActiveObject(text);
        });

        $('#remove-element').on('click', () => {
            if (canvas.getActiveObject()) canvas.remove(canvas.getActiveObject());
        });
    }

    function populateTextPlaceholders() {
        const placeholders = [
            '@{{nama_penerima}}', '@{{nomor_sertifikat}}', '@{{jenis_sertifikat}}',
            '@{{nama_acara}}', '@{{tanggal_acara}}', '@{{tanggal_penandatanganan}}',
            '@{{deskripsi_1}}', '@{{deskripsi_2}}', '@{{deskripsi_3}}',
            '@{{id_lengkap_peserta}}', '@{{peran_penerima}}',
            '@{{nilai_1}}', '@{{nilai_2}}', '@{{nilai_3}}', '@{{nilai_4}}'
        ];
        const menu = $('#text-placeholders');
        placeholders.forEach(p => {
            let displayText = p;
            // Add description for combined place and date placeholder
            if (p === '@{{tanggal_penandatanganan}}') {
                displayText = p + ' (Tempat, Tanggal)';
            }
            // Add description for nilai placeholders
            if (p.includes('nilai_')) {
                displayText = p + ' (dari Excel/CSV)';
            }
            menu.append(`<a class="dropdown-item text-placeholder-item" href="#" data-placeholder="${p}">${displayText}</a>`);
        });

        $('#insert-menu').on('click', '.text-placeholder-item', function(e) {
            e.preventDefault();
            const placeholderText = $(this).data('placeholder');
            const text = new fabric.IText(placeholderText, {
                left: 150, top: 150,
                fontFamily: 'helvetica', fontSize: 18, fill: '#333333', isPlaceholder: true
            });
            canvas.add(text);
        });
    }

    function bindSignatureBlocks(canvas) {
        $('#insert-menu').on('click', '.signature-block-item', function(e) {
            e.preventDefault();
            addSignatureBlock(canvas, $(this).data('index'));
        });
    }

    function bindEditorControls(canvas) {
        $('.custom-file-input').on('change', function(e) {
            const inputId = $(this).attr('id');
            const file = e.target.files[0];
            if (!file) return;

            const label = $(this).next('.custom-file-label');
            label.addClass("selected").html(file.name);

            if (inputId.startsWith('signature_image_')) {
                const previewId = inputId.replace('image', 'preview');
                const index = parseInt(previewId.match(/\d+/)[0]);
                const reader = new FileReader();
                reader.onload = function(event) {
                    $('#' + previewId).attr('src', event.target.result).show();
                    // Immediately replace signature placeholder with uploaded image
                    replaceSignaturePlaceholderWithImage(canvas, index, event.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function bindTemplateHandlers(canvas, templates) {
        $('.load-template-btn').on('click', function() {
            const id = $(this).data('template-id');
            if (id && templates[id]) {
                canvas.loadFromJSON(JSON.parse(templates[id].template_data), () => {
                    canvas.renderAll();
                    alert(`Template "${templates[id].name}" berhasil dimuat.`);
                    
                    // After template is loaded, check for any uploaded signature images and apply them
                    setTimeout(() => {
                        applyExistingSignatureImages(canvas);
                    }, 500); // Small delay to ensure template is fully loaded
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

            fetch('{{ route('templates.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    name: name,
                    template_data: JSON.stringify(canvas.toJSON(['isPlaceholder', 'isSignatureBlock', 'signatureIndex']))
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Gagal menyimpan: ' + (data.errors?.name.join(', ') || data.message));
                }
            });
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
                        <td>${karyawan.npk}</td>
                        <td>${karyawan.divisi ? karyawan.divisi.inisial_unit : karyawan.kode_divisi}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-warning edit-karyawan-btn" 
                                    data-id="${karyawan.id}" 
                                    data-nama="${karyawan.nama}" 
                                    data-npk="${karyawan.npk}" 
                                    data-divisi="${karyawan.kode_divisi}">
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
        
        // divisiList sekarang adalah object dengan kode_divisi sebagai key dan inisial_unit sebagai value
        for (const [kode, nama] of Object.entries(divisiList)) {
            const selected = kode === currentValue ? 'selected' : '';
            html += `<option value="${kode}" ${selected}>${nama}</option>`;
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
                npk: $('#karyawan-npk').val(),
                kode_divisi: $('#karyawan-divisi').val(),
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
        $('#template_json').val(JSON.stringify(canvas.toJSON(['isPlaceholder', 'isSignatureBlock', 'signatureIndex'])));
        const form = $('#main-form');
        form.attr('action', '{{ route('certificates.render.preview') }}').attr('target', '_blank').submit();
        setTimeout(() => form.attr('action', '{{ route('certificates.bulk.download') }}').removeAttr('target'), 500);
    }

    // ========== GENERATE SUBMISSION ==========
    function handleGenerate(canvas) {
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

        $('#template_json').val(JSON.stringify(canvas.toJSON(['isPlaceholder', 'isSignatureBlock', 'signatureIndex'])));
        const form = document.getElementById('main-form');
        const formData = new FormData(form);

        $('#progress-bar-wrapper').show();
        const bar = document.getElementById('progress-bar');
        bar.style.width = '0%'; bar.innerText = '0%';

        const dataUrl = canvas.toDataURL({ format: 'png' });
        formData.append('canvas_image', dataUrl);

        fetch(form.action, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value},
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.batchId) {
                startPolling(data.batchId);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            bar.classList.add('bg-danger');
            bar.innerText = '❌ Terjadi kesalahan';
        });
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
            fontSize: 16, fontWeight: 'bold',
            fill: '#000', top: 90 + 5, left: 0,
            originX: 'center', originY: 'top'
        });

        const titleText = new fabric.IText(titlePlaceholder, {
            fontSize: 14, fill: '#333',
            top: 90 + 30, left: 0,
            originX: 'center', originY: 'top'
        });

        const group = new fabric.Group([signatureImage, nameText, titleText], {
            left: 250 + (index * 350), top: 500,
            originX: 'center', originY: 'top',
            hasControls: true, hasBorders: false,
            lockUniScaling: true,
            isSignatureBlock: true, signatureIndex: index
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
            console.log(`No signature block found for index ${index} on canvas`);
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
            let nameText = null;
            let titleText = null;
            
            // Find name and title text objects
            groupObjects.forEach(obj => {
                if (obj.type === 'i-text' || obj.type === 'text') {
                    const text = obj.text || '';
                    if (text.includes(`nama_penandatangan_${index + 1}`) || 
                        (text.includes('nama_penandatangan') && !nameText)) {
                        nameText = obj;
                    } else if (text.includes(`jabatan_penandatangan_${index + 1}`) || 
                               (text.includes('jabatan_penandatangan') && !titleText)) {
                        titleText = obj;
                    }
                }
            });

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
                signatureIndex: index
            });

            // Replace old group with new one
            canvas.remove(group);
            canvas.add(newGroup);
            canvas.setActiveObject(newGroup);
            canvas.requestRenderAll();
            
            console.log(`Signature image successfully replaced for index ${index}`);
        });
    }

    // Certificate number preview functionality
    function setupCertificateNumberPreview() {
        const prefixInput = document.getElementById('certificate_number_prefix');
        const helpText = prefixInput.parentNode.querySelector('.form-text');
        
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
                
                console.log(`Applying existing signature image for index ${i}`);
                replaceSignaturePlaceholderWithImage(canvas, i, previewImg.src);
            }
        }
    }
</script>
@endpush
