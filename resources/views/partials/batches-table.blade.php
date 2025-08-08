@php
    use Illuminate\Support\Str;
@endphp

<table class="table table-head-fixed table-hover">
    <thead>
        <tr>
            <th style="width: 50px">#</th>
            <th>Batch ID</th>
            <th>Nama Acara</th>
            <th>Jumlah Sertifikat</th>
            <th>Status</th>
            <th>Tanggal Dibuat</th>
            <th style="width: 150px" class="text-center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($batches as $batch)
            <tr>
                <td>{{ $loop->iteration + ($batches->currentPage() - 1) * $batches->perPage() }}</td>
                <td>
                    <code>{{ $batch->batch_id }}</code>
                </td>
                <td>
                    <strong>{{ $batch->event_name }}</strong>
                </td>
                <td>
                    <span class="badge badge-info">
                        <i class="fas fa-certificate"></i>
                        {{ number_format($batch->completed_jobs) }}
                    </span>
                </td>
                <td>
                    @if ($batch->is_zipped)
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Selesai
                        </span>
                    @else
                        <span class="badge badge-warning">
                            <i class="fas fa-clock"></i> Proses
                        </span>
                    @endif
                </td>
                <td>
                    <small class="text-muted">
                        <i class="fas fa-calendar"></i> 
                        {{ $batch->created_at->format('d M Y, H:i') }}
                    </small>
                </td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        @if ($batch->is_zipped)
                            @php
                                $zipFilename = 'sertifikat-' . Str::slug($batch->event_name) . '-' . $batch->batch_id . '.zip';
                            @endphp
                            <a href="{{ url('/download-zip/' . $zipFilename) }}" 
                               class="btn btn-sm btn-success" 
                               title="Download ZIP">
                                <i class="fas fa-download"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-info" 
                                    title="Info Batch"
                                    data-toggle="modal" 
                                    data-target="#batchModal{{ $batch->id }}">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        @else
                            <button type="button" 
                                    class="btn btn-sm btn-secondary" 
                                    disabled 
                                    title="Sedang Diproses">
                                <i class="fas fa-hourglass-half"></i>
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-info" 
                                    title="Info Batch"
                                    data-toggle="modal" 
                                    data-target="#batchModal{{ $batch->id }}">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>

            {{-- Modal for batch details --}}
            <div class="modal fade" id="batchModal{{ $batch->id }}" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-info-circle"></i> Detail Batch
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-4"><strong>Batch ID:</strong></div>
                                <div class="col-sm-8"><code>{{ $batch->batch_id }}</code></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Nama Acara:</strong></div>
                                <div class="col-sm-8">{{ $batch->event_name }}</div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Jumlah Sertifikat:</strong></div>
                                <div class="col-sm-8">{{ number_format($batch->completed_jobs) }} sertifikat</div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Status:</strong></div>
                                <div class="col-sm-8">
                                    @if ($batch->is_zipped)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Selesai
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Sedang Diproses
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Dibuat:</strong></div>
                                <div class="col-sm-8">{{ $batch->created_at->format('d M Y, H:i:s') }}</div>
                            </div>
                            @if($batch->updated_at != $batch->created_at)
                            <hr>
                            <div class="row">
                                <div class="col-sm-4"><strong>Diperbarui:</strong></div>
                                <div class="col-sm-8">{{ $batch->updated_at->format('d M Y, H:i:s') }}</div>
                            </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            @if ($batch->is_zipped)
                                @php
                                    $zipFilename = 'sertifikat-' . Str::slug($batch->event_name) . '-' . $batch->batch_id . '.zip';
                                @endphp
                                <a href="{{ url('/download-zip/' . $zipFilename) }}" 
                                   class="btn btn-success">
                                    <i class="fas fa-download"></i> Download ZIP
                                </a>
                            @endif
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <tr>
                <td colspan="7" class="text-center">
                    <div class="py-4">
                        <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada batch yang ditemukan</h5>
                        @if(request()->has('search') || request()->has('event_filter') || request()->has('status_filter'))
                            <p class="text-muted">
                                Coba ubah kata kunci pencarian atau filter yang Anda gunakan.
                            </p>
                            <button type="button" class="btn btn-primary" id="resetFiltersFromTable">
                                <i class="fas fa-undo"></i> Reset Filter
                            </button>
                        @else
                            <p class="text-muted">
                                Belum ada batch sertifikat yang dibuat.
                            </p>
                            <a href="{{ route('certificates.bulk.form') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Buat Batch Baru
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($batches->hasPages())
    <div class="card-footer clearfix">
        <div class="row">
            <div class="col-sm-12 col-md-5">
                <div class="dataTables_info">
                    Menampilkan {{ $batches->firstItem() }} sampai {{ $batches->lastItem() }} 
                    dari {{ $batches->total() }} entri
                </div>
            </div>
            <div class="col-sm-12 col-md-7">
                {{ $batches->appends(['search' => request('search'), 'event_filter' => request('event_filter'), 'status_filter' => request('status_filter')])->links() }}
            </div>
        </div>
    </div>
@endif
