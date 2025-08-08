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
            <a href="{{ route('certificates.list') }}" class="small-box-footer">Info lebih lanjut <i class="fas fa-arrow-circle-right"></i></a>
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
            <a href="{{ route('batches.list') }}" class="small-box-footer">Info lebih lanjut <i class="fas fa-arrow-circle-right"></i></a>
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
                <h3 class="card-title">5 Batch Sertifikat Terbaru</h3>
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
                                <td>{{ $loop->iteration }}</td>
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
                                <td>
                                    @if ($batch->is_zipped)
                                        @php
                                            $zipFilename = 'sertifikat-' . Str::slug($batch->event_name) . '-' . $batch->batch_id . '.zip';
                                        @endphp
                                        <a href="{{ url('/download-zip/' . $zipFilename) }}" 
                                           class="btn btn-sm btn-success" 
                                           title="Download ZIP">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @else
                                        <span class="btn btn-sm btn-secondary disabled" title="Sedang Diproses">
                                            <i class="fas fa-hourglass-half"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-boxes fa-2x text-muted mb-2"></i>
                                    <br>
                                    <span class="text-muted">Belum ada batch sertifikat yang dibuat.</span>
                                    <br>
                                    <a href="{{ route('certificates.bulk.form') }}" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-plus"></i> Buat Batch Pertama
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
            {{-- Akhir dari blok <div class="card"> untuk tabel --}}
    </section>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('myBarChart').getContext('2d');
    
    // Chart data
    var chartLabels = {!! json_encode($chartLabels) !!};
    var chartData = {!! json_encode($chartData) !!};
    
    // If no data, show placeholder
    if (chartLabels.length === 0 || chartData.length === 0) {
        chartLabels = ['Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025', 'Mei 2025', 'Jun 2025'];
        chartData = [0, 0, 0, 0, 0, 0];
        
        // Add empty state message
        var chartContainer = document.getElementById('myBarChart').parentElement;
        var emptyMessage = document.createElement('div');
        emptyMessage.className = 'chart-empty-state';
        emptyMessage.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #999; z-index: 10; pointer-events: none;';
        emptyMessage.innerHTML = '<i class="fas fa-chart-bar fa-2x mb-2"></i><br><small>Belum ada data untuk 6 bulan terakhir</small>';
        chartContainer.style.position = 'relative';
        chartContainer.appendChild(emptyMessage);
    }
    
    var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Jumlah Sertifikat',
                data: chartData,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' sertifikat';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Number.isInteger(value) ? value.toLocaleString() : '';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });
});
</script>
@endpush