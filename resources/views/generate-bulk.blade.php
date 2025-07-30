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
                        <label for="certificate_number_prefix">Nomor Sertifikat</label>
                        <input type="text" class="form-control" id="certificate_number_prefix" name="certificate_number_prefix" 
                               placeholder="Contoh: CERT-2025-001 (akan otomatis bertambah untuk setiap peserta)" required>
                        <small class="form-text text-muted">
                            Format akan otomatis bertambah: CERT-2025-001, CERT-2025-002, dst. untuk setiap peserta
                        </small>
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
        <div style="border: 1px solid #ccc; width: 1122px; height: 793px; margin: auto;"><canvas id="certificate-canvas"></canvas></div>
    </div>
    <div class="card-footer">
        <button type="button" id="save-template" class="btn btn-success"><i class="fas fa-save"></i> Simpan Desain Saat Ini</button>
        <button type="button" id="preview-btn" class="btn btn-info"><i class="fas fa-eye"></i> Preview</button>
        <button type="button" id="generate-btn-final" class="btn btn-primary float-right"><i class="fas fa-download"></i> Generate & Download ZIP</button>
    </div>
</div>

<div class="progress mt-4" id="progress-bar-wrapper" style="display:none;">
    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
</div>

@endsection


@push('scripts')
<script src="{{ asset('js/fabric.min.js') }}"></script>
<script>
    $(document).ready(function() {
        const canvas = window.canvas=new fabric.Canvas('certificate-canvas', {
            width: 1122,
            height: 793,
            backgroundColor: '#ffffff'
        });

        // ========== 1. === EDITOR & PLACEHOLDER ==========
        initCanvasEvents(canvas);
        populateTextPlaceholders();
        bindSignatureBlocks();
        bindEditorControls(canvas);

        // ========== 2. === TEMPLATE HANDLER ==========
        const savedTemplates = @json($templates);
        bindTemplateHandlers(canvas, savedTemplates);

        // ========== 3. === PREVIEW & GENERATE SUBMISSION ==========
        $('#preview-btn').on('click', () => handlePreview(canvas));
        $('#generate-btn-final').on('click', () => handleGenerate(canvas));

        // ========== 4. === INPUT HANDLERS (Signatures, Tanggal) ==========
        bindFormInputHandlers();
    });


    // ========== FUNCTION DEFINITIONS ==========

    function initCanvasEvents(canvas) {
        $('#bg-upload').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(f) {
                fabric.Image.fromURL(f.target.result, function(img) {
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                        scaleX: canvas.width / img.width,
                        scaleY: canvas.height / img.height
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
            '@{{id_lengkap_peserta}}', '@{{peran_penerima}}'
        ];
        const menu = $('#text-placeholders');
        placeholders.forEach(p => {
            menu.append(`<a class="dropdown-item text-placeholder-item" href="#">${p}</a>`);
        });

        $('#insert-menu').on('click', '.text-placeholder-item', function(e) {
            e.preventDefault();
            const placeholderText = $(this).text();
            const text = new fabric.IText(placeholderText, {
                left: 150, top: 150,
                fontFamily: 'helvetica', fontSize: 18, fill: '#333333', isPlaceholder: true
            });
            canvas.add(text);
        });
    }

    function bindSignatureBlocks() {
        $('#insert-menu').on('click', '.signature-block-item', function(e) {
            e.preventDefault();
            addSignatureBlock($(this).data('index'));
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
                    replaceSignaturePlaceholderWithImage(index, event.target.result);
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


    // ========== PREVIEW SUBMISSION ==========
    function handlePreview(canvas) {
        $('#template_json').val(JSON.stringify(canvas.toJSON(['isPlaceholder', 'isSignatureBlock', 'signatureIndex'])));
        const form = $('#main-form');
        form.attr('action', '{{ route('certificates.render.preview') }}').attr('target', '_blank').submit();
        setTimeout(() => form.attr('action', '{{ route('certificates.bulk.download') }}').removeAttr('target'), 500);
    }

    // ========== GENERATE SUBMISSION ==========
    function handleGenerate(canvas) {
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

    function addSignatureBlock(index, imageObj = null) {
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

    function replaceSignaturePlaceholderWithImage(index, imageUrl) {
        const group = canvas.getObjects().find(obj =>
            obj.type === 'group' && obj.isSignatureBlock && obj.signatureIndex === index
        );
        if (!group) return;

        fabric.Image.fromURL(imageUrl, function(img) {
            img.set({ originX: 'center', originY: 'top', top: 0, left: 0 });
            img.scaleToWidth(180); img.scaleToHeight(90);

            const [_, nameText, titleText] = group._objects;
            nameText.set({ top: img.getScaledHeight() + 5 });
            titleText.set({ top: img.getScaledHeight() + 30 });

            const newGroup = new fabric.Group([img, nameText, titleText], {
                ...group,
                isSignatureBlock: true, signatureIndex: index
            });

            canvas.remove(group);
            canvas.add(newGroup);
            canvas.setActiveObject(newGroup);
            canvas.requestRenderAll();
        });
    }
</script>
@endpush
