@extends('layouts.app')

@section('title', 'Certificate Projects')
@section('content-title', 'Certificate Projects')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Projects</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-project-diagram mr-2"></i>
                    All Certificate Projects
                </h3>
                <div class="card-tools">
                    <a href="{{ route('certificates.bulk.form') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create New Project
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('success') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Project Name</th>
                                <th width="15%">Event Name</th>
                                <th width="10%" class="text-center">Certificates</th>
                                <th width="10%" class="text-center">Edited</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="12%">Created</th>
                                <th width="18%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                                <tr>
                                    <td>{{ $projects->firstItem() + $loop->index }}</td>
                                    <td>
                                        <strong>{{ $project->project_name }}</strong>
                                        @if($project->template)
                                            <br><small class="text-muted">
                                                <i class="fas fa-file-alt"></i> {{ $project->template->name }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ $project->event_name }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">
                                            {{ $project->total_certificates }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($project->edited_count > 0)
                                            <span class="badge badge-warning">
                                                {{ $project->edited_count }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($project->status === 'draft')
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-edit"></i> Draft
                                            </span>
                                        @elseif($project->status === 'finalizing')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-spinner fa-spin"></i> Processing
                                            </span>
                                        @elseif($project->status === 'completed')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Completed
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $project->created_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($project->status === 'draft')
                                            <a href="{{ route('projects.edit', $project->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit Project">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif

                                        @if($project->status === 'completed' && $project->zip_path)
                                            <a href="{{ route('projects.download', $project->id) }}" 
                                               class="btn btn-sm btn-success" 
                                               title="Download ZIP">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif

                                        @if($project->status === 'finalizing')
                                            <button class="btn btn-sm btn-warning" disabled title="Processing...">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </button>
                                        @endif

                                        <button class="btn btn-sm btn-danger delete-project" 
                                                data-id="{{ $project->id }}"
                                                data-name="{{ $project->project_name }}"
                                                title="Delete Project">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No projects found. Create your first project to get started!</p>
                                        <a href="{{ route('certificates.bulk.form') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create New Project
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($projects->hasPages())
                    <div class="mt-3">
                        {{ $projects->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Delete project confirmation
    $('.delete-project').click(function() {
        const projectId = $(this).data('id');
        const projectName = $(this).data('name');
        
        if (!confirm(`Are you sure you want to delete project "${projectName}"?\n\nThis will delete all certificates and cannot be undone.`)) {
            return;
        }

        // Show loading state
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: `/projects/${projectId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Remove row with animation
                    $btn.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                    
                    // Show success message
                    toastr.success(response.message || 'Project deleted successfully');
                } else {
                    toastr.error(response.message || 'Failed to delete project');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred while deleting the project';
                toastr.error(message);
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
@endpush
