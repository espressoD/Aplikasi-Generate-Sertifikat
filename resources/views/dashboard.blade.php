@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.app')

@section('title', 'Dashboard')
@section('content-title', 'Dashboard Aplikasi Sertifikat')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalCertificates }}</h3>
                <p>Total Sertifikat Dibuat</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-signature"></i>
            </div>
            <a href="#" class="small-box-footer">Info lebih lanjut <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalEvents }}</h3>
                <p>Jumlah Acara Berbeda</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <a href="#" class="small-box-footer">Info lebih lanjut <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>Akses Cepat</h3>
                <p>Generate Sertifikat Baru</p>
            </div>
            <div class="icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <a href="{{ route('certificates.bulk.form') }}" class="small-box-footer">Mulai Generate <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    </div>
<div class="row">
    <section class="col-lg-7 connectedSortable">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-1"></i>
                    Sertifikat Dibuat per Bulan
                </h3>
            </div><div class="card-body">
                <div class="tab-content p-0">
                    <div class="chart tab-pane active" id="sales-chart" style="position: relative; height: 300px;">
                        <canvas id="myBarChart" height="300" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div></div>
        </section>
    <section class="col-lg-5 connectedSortable">
        {{-- Ganti seluruh blok <div class="card"> untuk tabel dengan ini --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Batch Sertifikat Terbaru</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Nama Acara</th>
                            <th>Jumlah Sertifikat</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th style="width: 100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificateBatches as $batch)
                            <tr>
                                <td>{{ $loop->iteration + ($certificateBatches->currentPage() - 1) * $certificateBatches->perPage() }}</td>
                                <td>{{ $batch->event_name }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $batch->completed_jobs }} sertifikat</span>
                                </td>
                                <td>
                                    @if ($batch->is_zipped)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Selesai
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Proses
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $batch->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    @if ($batch->is_zipped)
                                        @php
                                            $zipFilename = 'sertifikat-' . Str::slug($batch->event_name) . '-' . $batch->batch_id . '.zip';
                                        @endphp
                                        <a href="{{ url('/download-zip/' . $zipFilename) }}" class="btn btn-sm btn-success" title="Download ZIP">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada batch sertifikat yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $certificateBatches->links() }}
            </div>
        </div>
            {{-- Akhir dari blok <div class="card"> untuk tabel --}}
    </section>
</div>

{{-- Tabel untuk sertifikat individual --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sertifikat Individual</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Nama Penerima</th>
                            <th>Acara</th>
                            <th>No. Sertifikat</th>
                            <th>Tanggal</th>
                            <th style="width: 150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td>{{ $loop->iteration + ($certificates->currentPage() - 1) * $certificates->perPage() }}</td>
                                <td>{{ $certificate->recipient_name }}</td>
                                <td>{{ $certificate->event_name }}</td>
                                <td>{{ $certificate->certificate_number }}</td>
                                <td>{{ $certificate->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Aksi Sertifikat">
                                        <a href="{{ route('certificates.show', $certificate->id) }}" class="btn btn-sm btn-info" target="_blank" title="Lihat Sertifikat">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('certificates.download', $certificate->id) }}" class="btn btn-sm btn-success" title="Unduh Sertifikat">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada sertifikat individual yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $certificates->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var ctx = document.getElementById('myBarChart').getContext('2d');
    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Jumlah Sertifikat',
                data: {!! json_encode($chartData) !!},
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1 // memastikan skala y adalah bilangan bulat
                    }
                }]
            }
        }
    });
</script>
@endpush