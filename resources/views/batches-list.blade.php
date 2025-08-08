@extends('layouts.app')

@section('title', 'Daftar Batch Sertifikat')
@section('content-title', 'Daftar Batch Sertifikat')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Daftar Batch</li>
@endsection

@section('content')
{{-- Statistics Cards --}}
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Batch</span>
                <span class="info-box-number">{{ number_format($totalBatches) }}</span>
                <div class="progress">
                    <div class="progress-bar bg-info" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    Batch sertifikat yang telah dibuat
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-certificate"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Sertifikat</span>
                <span class="info-box-number">{{ number_format($totalCertificatesFromBatches) }}</span>
                <div class="progress">
                    <div class="progress-bar bg-warning" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    Sertifikat dari semua batch
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Batch Selesai</span>
                <span class="info-box-number">{{ number_format($completedBatches) }}</span>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    Batch yang siap diunduh
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Batch Proses</span>
                <span class="info-box-number">{{ number_format($processingBatches) }}</span>
                <div class="progress">
                    <div class="progress-bar bg-danger" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    Batch yang sedang diproses
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Search and Filter Section --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filter & Pencarian</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="searchForm" method="GET" action="{{ route('batches.list') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search">Pencarian</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="{{ request('search') }}" 
                                           placeholder="Nama acara atau batch ID...">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fas fa-search" id="searchIcon"></i>
                                            <i class="fas fa-spinner fa-spin d-none" id="searchLoader"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="event_filter">Filter Acara</label>
                                <select class="form-control" id="event_filter" name="event_filter">
                                    <option value="">Semua Acara</option>
                                    @foreach($events as $event)
                                        <option value="{{ $event }}" {{ request('event_filter') == $event ? 'selected' : '' }}>
                                            {{ $event }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status_filter">Filter Status</label>
                                <select class="form-control" id="status_filter" name="status_filter">
                                    <option value="">Semua Status</option>
                                    <option value="completed" {{ request('status_filter') == 'completed' ? 'selected' : '' }}>Selesai</option>
                                    <option value="processing" {{ request('status_filter') == 'processing' ? 'selected' : '' }}>Proses</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="d-block">
                                    <button type="button" id="clearFilters" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <span class="ml-2 text-muted">
                                        <i class="fas fa-info-circle"></i> Pencarian otomatis
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Batches Table --}}
<div class="row">
    <div class="col-12">
        <div class="card" id="batchesCard">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Daftar Batch Sertifikat
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info" id="totalBadge">
                        {{ $batches->total() }} dari {{ number_format($totalBatches) }} total batch
                    </span>
                </div>
            </div>
            <div class="card-body table-responsive p-0" id="batchesTableContainer">
                <!-- Table content will be loaded here -->
                @include('partials.batches-table', ['batches' => $batches])
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aksi Cepat</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="{{ route('certificates.bulk.form') }}" class="btn btn-primary btn-block">
                            <i class="fas fa-plus-circle"></i> Generate Batch Baru
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-tachometer-alt"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout;
    let isLoading = false;
    
    // Function to show loading state
    function showLoading() {
        isLoading = true;
        $('#searchIcon').addClass('d-none');
        $('#searchLoader').removeClass('d-none');
        $('#batchesCard').addClass('loading');
    }
    
    // Function to hide loading state
    function hideLoading() {
        isLoading = false;
        $('#searchIcon').removeClass('d-none');
        $('#searchLoader').addClass('d-none');
        $('#batchesCard').removeClass('loading');
    }
    
    // Function to perform AJAX search
    function performSearch() {
        if (isLoading) return;
        
        showLoading();
        
        const formData = {
            search: $('#search').val(),
            event_filter: $('#event_filter').val(),
            status_filter: $('#status_filter').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '{{ route("batches.list") }}',
            method: 'GET',
            data: formData,
            success: function(response) {
                // Update table content
                $('#batchesTableContainer').html(response.table);
                
                // Update total badge with clear context
                const filteredCount = response.filteredTotal || response.total;
                const totalCount = response.total;
                const badgeText = filteredCount === totalCount ? 
                    `${filteredCount.toLocaleString()} total batch` : 
                    `${filteredCount.toLocaleString()} dari ${totalCount.toLocaleString()} total batch`;
                $('#totalBadge').text(badgeText);
                
                // Update URL without reload
                const url = new URL(window.location);
                if (formData.search) {
                    url.searchParams.set('search', formData.search);
                } else {
                    url.searchParams.delete('search');
                }
                
                if (formData.event_filter) {
                    url.searchParams.set('event_filter', formData.event_filter);
                } else {
                    url.searchParams.delete('event_filter');
                }
                
                if (formData.status_filter) {
                    url.searchParams.set('status_filter', formData.status_filter);
                } else {
                    url.searchParams.delete('status_filter');
                }
                
                window.history.pushState({}, '', url);
                
                // Re-bind pagination click events
                bindPaginationEvents();
                
                hideLoading();
            },
            error: function(xhr, status, error) {
                console.error('Search error:', error);
                hideLoading();
                
                // Show error message
                toastr.error('Terjadi kesalahan saat mencari data. Silakan coba lagi.');
            }
        });
    }
    
    // Function to bind pagination events
    function bindPaginationEvents() {
        $(document).off('click', '.pagination a').on('click', '.pagination a', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            const url = $(this).attr('href');
            if (!url || url === '#') return;
            
            showLoading();
            
            $.ajax({
                url: url,
                method: 'GET',
                data: {
                    search: $('#search').val(),
                    event_filter: $('#event_filter').val(),
                    status_filter: $('#status_filter').val()
                },
                success: function(response) {
                    $('#batchesTableContainer').html(response.table);
                    
                    // Update badge with clear context for pagination
                    const filteredCount = response.filteredTotal || response.total;
                    const totalCount = response.total;
                    const badgeText = filteredCount === totalCount ? 
                        `${filteredCount.toLocaleString()} total batch` : 
                        `${filteredCount.toLocaleString()} dari ${totalCount.toLocaleString()} total batch`;
                    $('#totalBadge').text(badgeText);
                    
                    // Update URL
                    window.history.pushState({}, '', url);
                    
                    // Re-bind pagination events
                    bindPaginationEvents();
                    
                    // Scroll to top of table
                    $('#batchesCard')[0].scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    hideLoading();
                },
                error: function(xhr, status, error) {
                    console.error('Pagination error:', error);
                    hideLoading();
                    toastr.error('Terjadi kesalahan saat memuat halaman. Silakan coba lagi.');
                }
            });
        });
    }
    
    // Search input with debounce
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            performSearch();
        }, 500); // 500ms delay
    });
    
    // Event filter dropdown
    $('#event_filter, #status_filter').on('change', function() {
        clearTimeout(searchTimeout);
        performSearch();
    });
    
    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#search').val('');
        $('#event_filter').val('');
        $('#status_filter').val('');
        performSearch();
    });
    
    // Reset filters from table (when no results found)
    $(document).on('click', '#resetFiltersFromTable', function() {
        $('#search').val('');
        $('#event_filter').val('');
        $('#status_filter').val('');
        performSearch();
    });
    
    // Focus on search input when page loads
    $('#search').focus();
    
    // Initial binding of pagination events
    bindPaginationEvents();
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        location.reload();
    });
});
</script>
@endpush

@push('styles')
<style>
/* Print styles */
@media print {
    .btn, .card-tools, .breadcrumb, .pagination {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
}

/* Enhanced table styling */
.table-head-fixed thead th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
}

/* Badge styling */
.badge-primary {
    background-color: #007bff;
}

/* Code styling for batch IDs */
code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 87.5%;
}

/* Loading states */
.card.loading {
    position: relative;
    opacity: 0.7;
    pointer-events: none;
}

.card.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 999;
}

/* Smooth transitions */
.table tbody tr {
    transition: all 0.2s ease;
}

.btn {
    transition: all 0.2s ease;
}

/* Search input group styling */
.input-group-text {
    background-color: #fff;
    border-left: none;
}

.form-control:focus + .input-group-append .input-group-text {
    border-color: #80bdff;
}

/* Enhanced pagination styling */
.pagination {
    margin-bottom: 0;
}

.page-link {
    transition: all 0.2s ease;
}

/* Loading animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.table tbody {
    animation: fadeIn 0.3s ease;
}

/* Empty state styling */
.py-4 i.fa-3x {
    opacity: 0.3;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        border-radius: 0.25rem !important;
    }
}

/* Status badges */
.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}
</style>
@endpush
