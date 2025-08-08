<table class="table table-head-fixed table-hover">
    <thead>
        <tr>
            <th style="width: 50px">#</th>
            <th>Nama Penerima</th>
            <th>Acara</th>
            <th>No. Sertifikat</th>
            <th>Tanggal Dibuat</th>
            <th style="width: 150px" class="text-center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($certificates as $certificate)
            <tr>
                <td>{{ $loop->iteration + ($certificates->currentPage() - 1) * $certificates->perPage() }}</td>
                <td>
                    <strong>{{ $certificate->recipient_name }}</strong>
                </td>
                <td>
                    <span class="badge badge-primary">{{ $certificate->event_name }}</span>
                </td>
                <td>
                    <code>{{ $certificate->certificate_number }}</code>
                </td>
                <td>
                    <small class="text-muted">
                        <i class="fas fa-calendar"></i> 
                        {{ $certificate->created_at->format('d M Y, H:i') }}
                    </small>
                </td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <a href="{{ route('certificates.show', $certificate->id) }}" 
           class="btn btn-sm btn-info" 
           target="_blank" 
           title="Lihat Sertifikat">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('certificates.download', $certificate->id) }}" 
           class="btn btn-sm btn-success" 
           title="Unduh Sertifikat">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">
                    <div class="py-4">
                        <i class="fas fa-certificate fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada sertifikat yang ditemukan</h5>
                        @if(request()->has('search') || request()->has('event_filter'))
                            <p class="text-muted">
                                Coba ubah kata kunci pencarian atau filter yang Anda gunakan.
                            </p>
                            <button type="button" class="btn btn-primary" id="resetFiltersFromTable">
                                <i class="fas fa-undo"></i> Reset Filter
                            </button>
                        @else
                            <p class="text-muted">
                                Belum ada sertifikat individual yang dibuat.
                            </p>
                            <a href="{{ route('certificates.bulk.form') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Buat Sertifikat Baru
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($certificates->hasPages())
    <div class="card-footer clearfix">
        <div class="row">
            <div class="col-sm-12 col-md-5">
                <div class="dataTables_info">
                    Menampilkan {{ $certificates->firstItem() }} sampai {{ $certificates->lastItem() }} 
                    dari {{ $certificates->total() }} entri
                </div>
            </div>
            <div class="col-sm-12 col-md-7">
                {{ $certificates->appends(['search' => request('search'), 'event_filter' => request('event_filter')])->links() }}
            </div>
        </div>
    </div>
@endif
