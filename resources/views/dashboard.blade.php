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
                <h3 class="card-title">Daftar Sertifikat</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Nama Penerima</th>
                            <th>Acara</th>
                            <th>No. Sertifikat</th>
                            <th style="width: 150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificates as $certificate)
                            <tr>
                                {{-- Menghitung nomor urut yang benar meskipun ada paginasi --}}
                                <td>{{ $loop->iteration + ($certificates->currentPage() - 1) * $certificates->perPage() }}</td>
                                <td>{{ $certificate->recipient_name }}</td>
                                <td>{{ $certificate->event_name }}</td>
                                <td>{{ $certificate->certificate_number }}</td>
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
                            {{-- Tampilan jika tidak ada data sama sekali --}}
                            <tr>
                                <td colspan="5" class="text-center">Belum ada data sertifikat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{-- Menampilkan link paginasi --}}
                {{ $certificates->links() }}
            </div>
        </div>
            {{-- Akhir dari blok <div class="card"> untuk tabel --}}
    </section>
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