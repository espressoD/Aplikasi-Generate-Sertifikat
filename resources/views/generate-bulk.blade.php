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
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label for="start_date">Tanggal Mulai Acara</label><input type="date" class="form-control" id="start_date" name="start_date" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label for="end_date">Tanggal Akhir Acara</label><input type="date" class="form-control" id="end_date" name="end_date" required disabled></div></div>
                    </div>
                    <div class="form-group">
                        <label for="signing_date">Tanggal Penandatanganan Sertifikat</label>
                        <input type="date" class="form-control" id="signing_date" name="signing_date" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Kustom (Opsional)</label>
                        <input type="text" class="form-control mb-2" name="descriptions[0]" placeholder="Deskripsi Kustom 1 (untuk placeholder @{{deskripsi_1}})">
                        <input type="text" class="form-control mb-2" name="descriptions[1]" placeholder="Deskripsi Kustom 2 (untuk placeholder @{{deskripsi_2}})">
                        <input type="text" class="form-control" name="descriptions[2]" placeholder="Deskripsi Kustom 3 (untuk placeholder @{{deskripsi_3}})">
                    </div>
                    <div class="form-group">
                        <label for="participant_file">File Data Peserta (.csv, .xlsx)</label>
                        <div class="input-group"><div class="custom-file"><input type="file" class="custom-file-input" id="participant_file" name="participant_file" required><label class="custom-file-label" for="participant_file">Pilih file</label></div></div>
                        <small class="form-text text-muted">Struktur kolom: `nama`, `email`, `peran`, `id_peserta`, `divisi`.</small>
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

{{-- Bagian Editor Kanvas --}}
<div class="row">
    <div class="col-12">
        <div class="card card-secondary">
            <div class="card-header"><h3 class="card-title">Langkah 2: Desain Template Sertifikat</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-group">
                        <label for="bg-upload">Unggah Gambar Latar (A4 Landscape)</label>
                        {{-- PERBAIKAN: Menggunakan struktur custom-file untuk input background --}}
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="bg-upload" accept="image/*">
                            <label class="custom-file-label" for="bg-upload">Pilih file</label>
                        </div>
                    </div>
                    <hr>
                    <button id="add-text" class="btn btn-default"><i class="fas fa-font"></i> Tambah Teks</button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="fas fa-code"></i> Sisipkan Placeholder</button>
                        <div class="dropdown-menu" id="placeholder-menu"></div>
                    </div>
                    <button id="remove-element" class="btn btn-danger float-right"><i class="fas fa-trash"></i> Hapus Elemen</button>
                </div>
                <div style="border: 1px solid #ccc; width: 1056px; height: 746px; margin: auto;">
                     <canvas id="certificate-canvas"></canvas>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" id="save-template" class="btn btn-success"><i class="fas fa-save"></i> Simpan Template</button>
                <button type="button" id="preview-btn" class="btn btn-info"><i class="fas fa-eye"></i> Preview</button>
                <button type="button" id="generate-btn-final" class="btn btn-primary float-right"><i class="fas fa-download"></i> Generate & Download ZIP</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- CDN untuk Fabric.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<script>
    $(document).ready(function() {
        const canvas = new fabric.Canvas('certificate-canvas', { width: 1056, height: 746, backgroundColor: '#ffffff' });

        // --- Logika Editor Kanvas ---
        $('#bg-upload').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(f) {
                fabric.Image.fromURL(f.target.result, function(img) {
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: canvas.width / img.width, scaleY: canvas.height / img.height });
                });
            };
            reader.readAsDataURL(file);
        });

        $('#add-text').on('click', function() {
            const text = new fabric.IText('Teks Baru', { left: 100, top: 100, fontFamily: 'helvetica', fontSize: 24, fill: '#000000' });
            canvas.add(text); canvas.setActiveObject(text);
        });
        
        const placeholders = ['@{{nama_penerima}}', '@{{nomor_sertifikat}}', '@{{jenis_sertifikat}}', '@{{nama_acara}}', '@{{tanggal_acara}}', '@{{tanggal_penandatanganan}}', '@{{deskripsi_1}}', '@{{deskripsi_2}}', '@{{deskripsi_3}}', '@{{id_lengkap_peserta}}', '@{{peran_penerima}}'];
        const placeholderMenu = $('#placeholder-menu');
        placeholders.forEach(p => placeholderMenu.append(`<a class="dropdown-item placeholder-item" href="#" data-placeholder="${p}">${p.replace('@', '')}</a>`));

        // PERBAIKAN: Menggunakan event delegation yang benar
        placeholderMenu.on('click', '.placeholder-item', function(e) {
            e.preventDefault();
            const placeholderText = $(this).data('placeholder'); // ambil dari attribute, bukan dari text()
            const text = new fabric.IText(placeholderText, {
                left: 150,
                top: 150,
                fontFamily: 'helvetica',
                fontSize: 18,
                fill: '#333333',
                isPlaceholder: true
            });
            canvas.add(text);
            canvas.setActiveObject(text);
        });


        $('#remove-element').on('click', () => { if (canvas.getActiveObject()) canvas.remove(canvas.getActiveObject()); });
        $('#save-template').on('click', () => { console.log(JSON.stringify(canvas.toJSON(['isPlaceholder']))); alert('Desain disimpan ke console log.'); });

        // --- FUNGSI UTAMA UNTUK SUBMIT FORM ---
        function submitForm(actionUrl, openInNewTab = false) {
            const mainForm = $('#main-form');
            $('#template_json').val(JSON.stringify(canvas.toJSON(['isPlaceholder'])));
            mainForm.attr('action', actionUrl);
            openInNewTab ? mainForm.attr('target', '_blank') : mainForm.removeAttr('target');
            mainForm.submit();
            setTimeout(() => {
                mainForm.attr('action', '{{ route('certificates.bulk.download') }}').removeAttr('target');
            }, 500);
        }

        $('#preview-btn').on('click', () => submitForm('{{ route('certificates.bulk.preview') }}', true));
        $('#generate-btn-final').on('click', () => submitForm('{{ route('certificates.bulk.download') }}'));
        
        // --- Logika untuk Form Input Biasa ---
        $('.custom-file-input').on('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                $(this).next('.custom-file-label').addClass("selected").html(fileName);

                const inputId = $(this).attr('id');
                if (inputId && inputId.startsWith('signature_image_')) {
                    const previewId = inputId.replace('image', 'preview');
                    const previewElement = $('#' + previewId);
                    if (e.target.files && e.target.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            previewElement.attr('src', event.target.result).show();
                        }
                        reader.readAsDataURL(e.target.files[0]);
                    }
                }
            }
        });

        $('#start_date').on('change', function() {
            const startDateValue = $(this).val();
            const endDateInput = $('#end_date');
            if (startDateValue) {
                endDateInput.prop('disabled', false).attr('min', startDateValue).val(startDateValue);
            } else {
                endDateInput.prop('disabled', true).val('');
            }
        });

        $('#signature_count').on('change', function() {
            const count = $(this).val();
            $('.signature-block').hide();
            for (let i = 0; i < count; i++) {
                $('#signature-block-' + i).show();
            }
        }).trigger('change');
    });
</script>
@endpush
