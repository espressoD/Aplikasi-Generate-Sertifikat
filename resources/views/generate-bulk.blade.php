@extends('layouts.app')

@section('title', 'Generate Sertifikat')

@section('content-title', 'Generate Sertifikat BULK')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Generate Bulk</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Generate Sertifikat Secara Bulk</h3>
            </div>
            {{-- Kita gunakan ID untuk form agar mudah di-handle JavaScript --}}
            <form id="main-form" action="{{ route('certificates.bulk.download') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="event_name">Nama Acara/Pelatihan</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" placeholder="Masukkan nama acara" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Tanggal Mulai Acara</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir Acara</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="participant_file">File Data Peserta (.csv, .xlsx)</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="participant_file" name="participant_file" required>
                                <label class="custom-file-label" for="participant_file">Pilih file</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">Pastikan file berisi kolom 'nama' dan 'email'.</small>
                    </div>
                    <hr>
                    <h4>Pengaturan Tanda Tangan</h4>
                    <div class="form-group">
                        <label for="signature_count">Jumlah Tanda Tangan</label>
                        <select class="form-control" id="signature_count" name="signature_count">
                            <option value="1">1 Tanda Tangan</option>
                            <option value="2" selected>2 Tanda Tangan</option>
                            <option value="3">3 Tanda Tangan</option>
                        </select>
                    </div>

                    <div class="row">
                        @for ($i = 0; $i < 3; $i++)
                        <div class="col-md-4 signature-block" id="signature-block-{{ $i }}" style="display: {{ $i < 2 ? 'block' : 'none' }};">
                            <div class="card card-outline card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">Penandatangan #{{ $i + 1 }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="signature_name_{{ $i }}">Nama Lengkap</label>
                                        <input type="text" class="form-control" name="signatures[{{ $i }}][name]" id="signature_name_{{ $i }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="signature_title_{{ $i }}">Jabatan</label>
                                        <input type="text" class="form-control" name="signatures[{{ $i }}][title]" id="signature_title_{{ $i }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="signature_image_{{ $i }}">Gambar Tanda Tangan (Opsional untuk Preview)</label>
                                        <input type="file" class="form-control-file" name="signatures[{{ $i }}][image]" id="signature_image_{{ $i }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-download"></i> Generate & Download ZIP</button>
                    <button type="button" id="preview-btn" class="btn btn-info"><i class="fas fa-eye"></i> Preview Sertifikat</button>
                </div>
            </form>
            
            {{-- FORM TERSEMBUNYI SUDAH DIHAPUS --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Pastikan semua skrip dijalankan setelah DOM siap dengan membungkusnya dalam $(document).ready()
    $(document).ready(function() {
        // Script untuk menampilkan nama file yang dipilih di form upload
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Script untuk tanggal dinamis
        $('#start_date').on('change', function() {
            let startDateValue = $(this).val();
            let endDateInput = $('#end_date');
            if (startDateValue) {
                endDateInput.prop('disabled', false);
                endDateInput.attr('min', startDateValue);
                endDateInput.val(startDateValue);
            } else {
                endDateInput.prop('disabled', true);
                endDateInput.val('');
            }
        });

        // --- LOGIKA BARU UNTUK TOMBOL PREVIEW ---
        $('#preview-btn').on('click', function(e) {
            e.preventDefault(); // Mencegah submit

            const mainForm = $('#main-form');
            const originalAction = mainForm.attr('action'); // Simpan action asli

            // Ubah action dan target untuk preview
            mainForm.attr('action', '{{ route('certificates.bulk.preview') }}');
            mainForm.attr('target', '_blank'); // Buka di tab baru

            // Submit form
            mainForm.submit();

            // Kembalikan action ke semula setelah submit
            // Diberi sedikit jeda agar submit sempat berjalan
            setTimeout(function() {
                mainForm.attr('action', originalAction);
                mainForm.attr('target', '_self'); // Kembalikan target ke tab ini
            }, 500);
        });

        // Script untuk menampilkan blok tanda tangan
        $('#signature_count').on('change', function() {
            let count = $(this).val();
            $('.signature-block').hide();
            for (let i = 0; i < count; i++) {
                $('#signature-block-' + i).show();
            }
        }).trigger('change');
    });
</script>
@endpush