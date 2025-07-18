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
        <div style="border: 1px solid #ccc; width: 1056px; height: 746px; margin: auto;"><canvas id="certificate-canvas"></canvas></div>
    </div>
    <div class="card-footer">
        <button type="button" id="save-template" class="btn btn-success"><i class="fas fa-save"></i> Simpan Desain Saat Ini</button>
        <button type="button" id="preview-btn" class="btn btn-info"><i class="fas fa-eye"></i> Preview</button>
        <button type="button" id="generate-btn-final" class="btn btn-primary float-right"><i class="fas fa-download"></i> Generate & Download ZIP</button>
    </div>
</div>
@endsection


@push('scripts')
{{-- CDN untuk Fabric.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<script>
    $(document).ready(function() {
        const canvas = window.canvas = new fabric.Canvas('certificate-canvas', { width: 1056, height: 746, backgroundColor: '#ffffff' });

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
        
         // 1. Daftar Placeholder Teks
        const textPlaceholders = [
            '@{{nama_penerima}}', '@{{nomor_sertifikat}}', '@{{jenis_sertifikat}}',
            '@{{nama_acara}}', '@{{tanggal_acara}}', '@{{tanggal_penandatanganan}}',
            '@{{deskripsi_1}}', '@{{deskripsi_2}}', '@{{deskripsi_3}}',
            '@{{id_lengkap_peserta}}', '@{{peran_penerima}}'
        ];
        
        const textPlaceholderMenu = $('#text-placeholders');
        textPlaceholders.forEach(p => {
            textPlaceholderMenu.append(`<a class="dropdown-item text-placeholder-item" href="#">${p}</a>`);
        });

        $('#insert-menu').on('click', '.text-placeholder-item', function(e) {
            e.preventDefault();
            const placeholderText = $(this).text();
            const text = new fabric.IText(placeholderText, { left: 150, top: 150, fontFamily: 'helvetica', fontSize: 18, fill: '#333333', isPlaceholder: true });
            canvas.add(text);
        });

        // 2. Event handler untuk menyisipkan Blok Tanda Tangan
        $('#insert-menu').on('click', '.signature-block-item', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            addSignatureBlock(index);
        });


        function addSignatureBlock(index, imageObj = null) {
            var number = index + 1;

            var namePlaceholder = ['@{{', 'nama_penandatangan_', number, '}}'].join('');
            var titlePlaceholder = ['@{{', 'jabatan_penandatangan_', number, '}}'].join('');

            let signatureImage;
            let imageHeight = 90; // default tinggi gambar
            let imageWidth = 180;

            if (imageObj) {
                signatureImage = imageObj;
                signatureImage.scaleToWidth(imageWidth);
                signatureImage.scaleToHeight(imageHeight);
            } else {
                signatureImage = new fabric.Rect({
                    width: imageWidth,
                    height: imageHeight,
                    fill: 'rgba(0,0,0,0.05)',
                    stroke: '#ccc',
                    strokeDashArray: [5, 5],
                });
            }

            // Atur posisi relatif
            signatureImage.set({
                top: 0,
                left: 0,
                originX: 'center',
                originY: 'top'
            });

            const nameText = new fabric.IText(namePlaceholder, {
                fontSize: 16,
                fontFamily: 'helvetica',
                fontWeight: 'bold',
                fill: '#000',
                top: imageHeight + 5, // tepat di bawah gambar
                left: 0,
                originX: 'center',
                originY: 'top'
            });

            const titleText = new fabric.IText(titlePlaceholder, {
                fontSize: 14,
                fontFamily: 'helvetica',
                fill: '#333',
                top: imageHeight + 30, // di bawah nama
                left: 0,
                originX: 'center',
                originY: 'top'
            });

            const group = new fabric.Group([signatureImage, nameText, titleText], {
                left: 250 + (index * 350),
                top: 500,
                originX: 'center',
                originY: 'top',
                hasControls: true,
                hasBorders: false,
                lockUniScaling: true,
                isSignatureBlock: true,
                signatureIndex: index
            });

            canvas.add(group);
            canvas.setActiveObject(group);
        }


        function replaceSignaturePlaceholderWithImage(index, imageUrl) {
            const signatureGroup = canvas.getObjects().find(obj =>
                obj.type === 'group' &&
                obj.isSignatureBlock &&
                obj.signatureIndex === index
            );

            if (!signatureGroup) return;

            fabric.Image.fromURL(imageUrl, function(img) {
                img.set({
                    originX: 'center',
                    originY: 'top',
                    top: 0,
                    left: 0
                });
                img.scaleToWidth(180);
                img.scaleToHeight(90);

                const oldObjects = signatureGroup._objects;
                const nameText = oldObjects[1];
                const titleText = oldObjects[2];

                // Update posisi teks sesuai dengan gambar baru
                const imageHeight = img.getScaledHeight();
                nameText.set({ top: imageHeight + 5 });
                titleText.set({ top: imageHeight + 30 });

                const newGroup = new fabric.Group([img, nameText, titleText], {
                    left: signatureGroup.left,
                    top: signatureGroup.top,
                    originX: 'center',
                    originY: 'top',
                    hasControls: true,
                    hasBorders: false,
                    lockUniScaling: true,
                    isSignatureBlock: true,
                    signatureIndex: index
                });

                canvas.remove(signatureGroup);
                canvas.add(newGroup);
                canvas.setActiveObject(newGroup);
                canvas.requestRenderAll();
            });
        }

        $('#remove-element').on('click', () => { if (canvas.getActiveObject()) canvas.remove(canvas.getActiveObject()); });
        // Variabel untuk menyimpan data template dari PHP
        const savedTemplates = @json($templates);

        // --- Logika Tombol Baru di Tabel ---
        $('.load-template-btn').on('click', function() {
            const templateId = $(this).data('template-id');
            if (templateId && savedTemplates[templateId]) {
                const templateData = JSON.parse(savedTemplates[templateId].template_data);
                canvas.loadFromJSON(templateData, () => {
                    canvas.renderAll();
                    alert('Template "' + savedTemplates[templateId].name + '" berhasil dimuat ke kanvas!');
                });
            }
        });

        $('.edit-template-btn').on('click', function() {
            const templateId = $(this).data('template-id');
            const currentName = $(this).data('template-name');
            const newName = prompt("Masukkan nama baru untuk template:", currentName);

            if (newName && newName !== currentName) {
                fetch(`/templates/${templateId}`, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({ name: newName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Perbarui nama di tabel tanpa reload halaman
                        $('#template-name-' + templateId).text(newName);
                        $(this).data('template-name', newName);
                    } else {
                        alert('Gagal: ' + (data.errors ? data.errors.name[0] : data.message));
                    }
                }).catch(err => alert('Terjadi kesalahan koneksi.'));
            }
        });

        // --- Logika Tombol Simpan Template ---
        $('#save-template').on('click', function() {
            const templateName = prompt("Masukkan nama untuk template baru ini:", "Template " + new Date().toLocaleString());
            if (templateName) {
                const templateData = JSON.stringify(canvas.toJSON(['isPlaceholder', 'isSignatureBlock', 'signatureIndex']));
                fetch('{{ route('templates.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ name: templateName, template_data: templateData })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload(); // Muat ulang halaman untuk menampilkan template baru
                    } else {
                        alert('Gagal menyimpan: ' + (data.errors ? data.errors.name.join(', ') : data.message));
                    }
                }).catch(err => alert('Terjadi kesalahan koneksi.'));
            }
        });


        // --- FUNGSI UTAMA UNTUK SUBMIT FORM ---
        function submitForm(actionUrl, openInNewTab = false) {
            const mainForm = $('#main-form');
            $('#template_json').val(JSON.stringify(canvas.toJSON(['isPlaceholder', 'isSignatureBlock', 'signatureIndex'])));
            
            // PERUBAHAN UTAMA: Set action dan target
            mainForm.attr('action', actionUrl);
            if (openInNewTab) {
                mainForm.attr('target', '_blank');
            } else {
                mainForm.removeAttr('target');
            }

            mainForm.submit();
            
            // Reset form action ke default setelah submit untuk generate ZIP
            setTimeout(() => {
                mainForm.attr('action', '{{ route('certificates.bulk.download') }}').removeAttr('target');
            }, 500);
        }

        // Pastikan panggilan untuk preview mengarah ke rute BARU
        $('#preview-btn').on('click', () => submitForm('{{ route('certificates.render.preview') }}', true));
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
                            const signatureIndex = parseInt(previewId.match(/\d+/)[0]);
                            replaceSignaturePlaceholderWithImage(signatureIndex, event.target.result);
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
