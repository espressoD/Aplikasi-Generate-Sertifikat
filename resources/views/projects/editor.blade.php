@extends('layouts.app')

@section('title', 'Edit Project - ' . $project->project_name)
@section('content-title', 'Edit Certificate Project')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
    <li class="breadcrumb-item active">{{ $project->project_name }}</li>
@endsection

@push('styles')
<!-- CRITICAL: Load Fabric.js in HEAD before any canvas code -->
<script src="{{ asset('js/fabric.min.js') }}"></script>
<!-- Floating Toolbar CSS -->
<link rel="stylesheet" href="{{ asset('css/floating-toolbar.css') }}">
<style>
    .certificate-canvas-wrapper {
        position: relative;
        background: #f4f6f9;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .canvas-container {
        margin: 0 auto;
        background: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    
    .certificate-info {
        background: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
        border-left: 4px solid #007bff;
    }
    
    .certificate-number-badge {
        font-size: 1.5rem;
        font-weight: bold;
        color: #007bff;
    }
    
    .navigation-controls {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .save-indicator {
        display: none;
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 0.9rem;
    }
    
    .save-indicator.saving {
        background: #fff3cd;
        color: #856404;
        display: inline-block;
    }
    
    .save-indicator.saved {
        background: #d4edda;
        color: #155724;
        display: inline-block;
    }
    
    .save-indicator.error {
        background: #f8d7da;
        color: #721c24;
        display: inline-block;
    }
    
    .thumbnail-sidebar {
        position: sticky;
        top: 70px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    
    .thumbnail-item {
        position: relative;
        cursor: pointer;
        padding: 10px;
        margin-bottom: 10px;
        border: 2px solid #dee2e6;
        border-radius: 5px;
        transition: border-color 0.2s, background-color 0.2s;
        background: white;
    }
    
    .thumbnail-item:hover {
        border-color: #007bff;
    }
    
    /* 🆕 MULTI-PAGE: Page navigation styles */
    .page-navigation {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
        display: none; /* Hidden by default, shown only for multi-page */
    }
    
    .page-navigation.active {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .page-tabs {
        display: flex;
        gap: 5px;
        flex: 1;
    }
    
    .page-tab {
        padding: 8px 16px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.9rem;
    }
    
    .page-tab:hover {
        background: #e9ecef;
    }
    
    .page-tab.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    
    .page-info {
        color: #6c757d;
        font-size: 0.9rem;
        margin-left: 15px;
        background: #f8f9fa;
    }
    
    .thumbnail-item.active {
        border-color: #007bff;
        background: #e7f3ff;
    }
    
    .thumbnail-item img {
        width: 100%;
        height: auto;
        border-radius: 3px;
    }
    
    .edited-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #ffc107;
        color: white;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
    }
    
    .canvas-controls {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }
    
    .canvas-controls .btn {
        margin-right: 5px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Left Sidebar - Thumbnails -->
    <div class="col-md-2">
        <div class="thumbnail-sidebar">
            <h6 class="text-muted mb-3">
                <i class="fas fa-images"></i> Certificates
                <span class="badge badge-primary float-right">{{ $project->certificates->count() }}</span>
            </h6>
            <div id="thumbnail-list">
                @foreach($project->certificates as $cert)
                    <div class="thumbnail-item" 
                         data-certificate-id="{{ $cert->id }}"
                         data-page-number="{{ $loop->iteration }}">
                        @if($cert->is_edited)
                            <span class="edited-badge">EDITED</span>
                        @endif
                        <div class="text-center mb-2">
                            <strong>{{ $loop->iteration }}</strong>
                        </div>
                        <div class="thumbnail-preview" style="height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <small class="text-muted">{{ Str::limit($cert->recipient_name, 15) }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Main Canvas Area -->
    <div class="col-md-8">
        <div class="navigation-controls">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <button id="prev-certificate" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <button id="next-certificate" class="btn btn-outline-primary btn-sm">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="col-md-4 text-center">
                    <span class="certificate-number-badge">
                        <span id="current-page">1</span> / <span id="total-pages">{{ $project->certificates->count() }}</span>
                    </span>
                </div>
                <div class="col-md-4 text-right">
                    <span class="save-indicator" id="save-indicator">
                        <i class="fas fa-check-circle"></i> Saved
                    </span>
                    <button id="save-current" class="btn btn-success btn-sm">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>

        <div id="canvas-wrapper" class="certificate-canvas-wrapper">
            <div class="certificate-info">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-1" id="recipient-name">Loading...</h5>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-certificate"></i> <span id="certificate-number">-</span>
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <span class="badge badge-info" id="edit-status">Not Edited</span>
                    </div>
                </div>
            </div>
            
            <!-- 🆕 MULTI-PAGE: Page Navigation (hidden for single-page) -->
            <div class="page-navigation" id="page-navigation">
                <div class="page-tabs" id="page-tabs">
                    <!-- Page tabs will be generated dynamically -->
                </div>
                <div class="page-info">
                    <i class="fas fa-file-alt"></i> <span id="current-page-info">Page 1 of 1</span>
                </div>
            </div>
            
            <!-- Canvas Control Buttons -->
            <div class="canvas-controls mb-3">
                <button id="add-text-btn" class="btn btn-default btn-sm">
                    <i class="fas fa-font"></i> Add Text
                </button>
                
                <div class="btn-group ml-2">
                    <button id="undo-btn" class="btn btn-light btn-sm" title="Undo (Ctrl+Z)" disabled>
                        <i class="fas fa-undo"></i>
                    </button>
                    <button id="redo-btn" class="btn btn-light btn-sm" title="Redo (Ctrl+Y)" disabled>
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
                
                <button id="delete-element-btn" class="btn btn-danger btn-sm float-right" disabled>
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
            
            <!-- Floating Toolbar -->
            @include('partials.floating-toolbar')
            
            <canvas id="certificate-canvas"></canvas>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Tip:</strong> Double-click any text to edit. Changes are auto-saved. Use navigation buttons or thumbnails to switch between certificates.
        </div>
    </div>
    
    <!-- Right Sidebar - Actions -->
    <div class="col-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Project Actions</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Project Status</small>
                    <span class="badge badge-secondary badge-lg">
                        <i class="fas fa-edit"></i> Draft
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Certificates Edited</small>
                    <h4 class="mb-0">
                        <span id="edited-count">{{ $project->edited_count }}</span> 
                        <small class="text-muted">/ {{ $project->total_certificates }}</small>
                    </h4>
                </div>

                <hr>

                <button id="finalize-project" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-check-double"></i> Finalize & Generate PDFs
                </button>

                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Keyboard Shortcuts</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                    <li class="mb-2">
                        <kbd>←</kbd> <kbd>→</kbd> Navigate
                    </li>
                    <li class="mb-2">
                        <kbd>Ctrl</kbd> + <kbd>S</kbd> Save
                    </li>
                    <li class="mb-2">
                        <kbd>Ctrl</kbd> + <kbd>Z</kbd> Undo
                    </li>
                    <li class="mb-2">
                        <kbd>Ctrl</kbd> + <kbd>Y</kbd> Redo
                    </li>
                    <li class="mb-2">
                        <kbd>Del</kbd> Delete
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Project data from server
const PROJECT_DATA = {
    id: {{ $project->id }},
    name: {!! json_encode($project->project_name) !!},
    certificates: {!! json_encode($project->certificates->map(function($cert) {
        return [
            'id' => $cert->id,
            'recipient_name' => $cert->recipient_name,
            'certificate_number' => $cert->certificate_number,
            'canvas_state' => $cert->canvas_state,
            'canvas_pages' => $cert->canvas_pages, // 🔧 MULTI-PAGE: Pass canvas_pages
            'is_edited' => $cert->is_edited,
            'page_order' => $cert->page_order,
        ];
    })->values()) !!}
};

let canvas;
let currentCertificateIndex = 0;
let hasUnsavedChanges = false;
let autoSaveTimeout;
let undoRedoManager; // Undo/Redo functionality
window.alignWithinGroupMode = false; // Align within group mode
let currentPageIndex = 0; // 🆕 MULTI-PAGE: Track current page
let totalPages = 1; // 🆕 MULTI-PAGE: Track total pages
let isSaving = false; // 🔧 BUG FIX: Track save state to prevent race condition
let saveQueue = []; // 🔧 BUG FIX: Queue for pending save operations
window.lastNormalAlign = 'left'; // Last alignment for normal mode
window.lastGroupAlign = 'left'; // Last alignment for group mode
window.isCtrlPressed = false; // Track Ctrl key for temporary snap disable
window.smartGuides = { enabled: true, snapToObjects: true, tolerance: 8, guides: [], temporarilyDisabled: false };

// ========================================
// UNDO/REDO MANAGER
// ========================================
class UndoRedoManager {
    constructor(canvas, maxHistorySize = 50) {
        this.canvas = canvas;
        this.undoStack = [];
        this.redoStack = [];
        this.maxHistorySize = maxHistorySize;
        this.isUndoRedoAction = false; // Flag to prevent history push during undo/redo
        
        this.initEventListeners();
    }
    
    initEventListeners() {
        // Track canvas modifications
        this.canvas.on('object:added', () => this.saveState());
        this.canvas.on('object:modified', () => this.saveState());
        this.canvas.on('object:removed', () => this.saveState());
    }
    
    saveState() {
        // Don't save state if we're currently undoing/redoing
        if (this.isUndoRedoAction) return;
        
        // Serialize canvas state
        const state = JSON.stringify(this.canvas.toJSON());
        
        // Don't save if state is identical to last state
        if (this.undoStack.length > 0) {
            const lastState = this.undoStack[this.undoStack.length - 1];
            if (lastState === state) return;
        }
        
        // Add to undo stack
        this.undoStack.push(state);
        
        // Clear redo stack on new action
        this.redoStack = [];
        
        // Limit stack size
        if (this.undoStack.length > this.maxHistorySize) {
            this.undoStack.shift();
        }
        
        // Update button states
        this.updateButtons();
    }
    
    undo() {
        if (this.undoStack.length <= 1) return; // Keep at least one state (initial)
        
        // Current state goes to redo stack
        const currentState = this.undoStack.pop();
        this.redoStack.push(currentState);
        
        // Load previous state
        const previousState = this.undoStack[this.undoStack.length - 1];
        this.loadState(previousState);
        
        this.updateButtons();
    }
    
    redo() {
        if (this.redoStack.length === 0) return;
        
        // Get next state
        const nextState = this.redoStack.pop();
        this.undoStack.push(nextState);
        
        // Load state
        this.loadState(nextState);
        
        this.updateButtons();
    }
    
    loadState(stateJson) {
        this.isUndoRedoAction = true;
        
        const state = JSON.parse(stateJson);
        
        // Load state without clearing to prevent flicker
        this.canvas.loadFromJSON(state, () => {
            this.canvas.renderAll();
            this.isUndoRedoAction = false;
        });
    }
    
    updateButtons() {
        // Update undo button
        if (this.undoStack.length > 1) {
            $('#undo-btn').prop('disabled', false);
        } else {
            $('#undo-btn').prop('disabled', true);
        }

        // Update redo button
        if (this.redoStack.length > 0) {
            $('#redo-btn').prop('disabled', false);
        } else {
            $('#redo-btn').prop('disabled', true);
        }
    }
    
    // Reset stacks when loading new certificate
    reset() {
        this.undoStack = [];
        this.redoStack = [];
        this.updateButtons();
    }
}

// ========================================
// FLOATING TOOLBAR FUNCTIONS
// ========================================

// Helper functions for text detection and common properties
function isTextObject(obj) {
    return obj && (obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text');
}

function getTextObjectsFromSelection(selection) {
    const textObjects = [];
    
    if (selection.type === 'activeSelection') {
        selection.getObjects().forEach(obj => {
            if (isTextObject(obj)) {
                textObjects.push(obj);
            } else if (obj.type === 'group') {
                obj.getObjects().forEach(child => {
                    if (isTextObject(child)) textObjects.push(child);
                });
            }
        });
    } else if (selection.type === 'group') {
        selection.getObjects().forEach(obj => {
            if (isTextObject(obj)) textObjects.push(obj);
        });
    } else if (isTextObject(selection)) {
        textObjects.push(selection);
    }
    
    return textObjects;
}

function getCommonPropertyValue(objects, property) {
    if (objects.length === 0) return null;
    const firstValue = objects[0][property];
    const allSame = objects.every(obj => obj[property] === firstValue);
    return allSame ? firstValue : null;
}

function getCommonBooleanProperty(objects, property, trueValue) {
    if (objects.length === 0) return null;
    const firstValue = objects[0][property] === trueValue;
    const allSame = objects.every(obj => (obj[property] === trueValue) === firstValue);
    return allSame ? firstValue : null;
}

// Color swatches
const commonColors = [
    '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00',
    '#FF00FF', '#00FFFF', '#808080', '#C0C0C0', '#800000', '#008000',
    '#000080', '#808000', '#800080', '#008080', '#FFA500', '#A52A2A'
];

function buildColorSwatches() {
    const swatchContainer = $('#floating-color-swatches');
    if (!swatchContainer.length) return;
    
    swatchContainer.empty();
    commonColors.forEach(color => {
        const swatch = $(`<div class="color-swatch" data-color="${color}" style="background: ${color};" title="${color}"></div>`);
        swatch.click(function() {
            applyFloatingFormat('fill', color);
        });
        swatchContainer.append(swatch);
    });
}

// Color conversion helpers
function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

function rgbToHex(r, g, b) {
    return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

// Helper function to get object bounds
function getObjectBounds(obj) {
    const rect = obj.getBoundingRect(true, true);
    const left = rect.left;
    const top = rect.top;
    const right = rect.left + rect.width;
    const bottom = rect.top + rect.height;
    const cx = left + rect.width / 2;
    const cy = top + rect.height / 2;
    return { left, top, right, bottom, cx, cy, width: rect.width, height: rect.height };
}

// Collect snap lines from canvas edges and other objects
function collectSnapLines(canvas, excludeObj) {
    const v = [0, canvas.width / 2, canvas.width];
    const h = [0, canvas.height / 2, canvas.height];
    const objects = canvas.getObjects() || [];
    objects.forEach(o => {
        if (o === excludeObj) return;
        if (o.visible === false || o.selectable === false) return;
        const b = getObjectBounds(o);
        v.push(b.left, b.cx, b.right);
        h.push(b.top, b.cy, b.bottom);
    });
    return { v: Array.from(new Set(v)), h: Array.from(new Set(h)) };
}

// Compute snap adjustment based on tolerance
function computeSnapAdjustment(canvas, target) {
    const zoom = canvas.getZoom ? canvas.getZoom() : 1;
    const tol = (window.smartGuides && window.smartGuides.tolerance ? window.smartGuides.tolerance : 8) / (zoom || 1);
    const lines = collectSnapLines(canvas, target);
    const b = getObjectBounds(target);

    const objV = [ {val: b.left, key:'left'}, {val: b.cx, key:'cx'}, {val: b.right, key:'right'} ];
    const objH = [ {val: b.top, key:'top'}, {val: b.cy, key:'cy'}, {val: b.bottom, key:'bottom'} ];

    let bestV = { dist: tol + 1, line: null, key: null };
    let bestH = { dist: tol + 1, line: null, key: null };

    lines.v.forEach(L => {
        objV.forEach(p => {
            const d = Math.abs(p.val - L);
            if (d < bestV.dist && d <= tol) {
                bestV = { dist: d, line: L, key: p.key };
            }
        });
    });
    lines.h.forEach(L => {
        objH.forEach(p => {
            const d = Math.abs(p.val - L);
            if (d < bestH.dist && d <= tol) {
                bestH = { dist: d, line: L, key: p.key };
            }
        });
    });

    let dx = 0, dy = 0;
    if (bestV.line !== null) {
        if (bestV.key === 'left') dx = bestV.line - b.left;
        else if (bestV.key === 'cx') dx = bestV.line - b.cx;
        else if (bestV.key === 'right') dx = bestV.line - b.right;
    }
    if (bestH.line !== null) {
        if (bestH.key === 'top') dy = bestH.line - b.top;
        else if (bestH.key === 'cy') dy = bestH.line - b.cy;
        else if (bestH.key === 'bottom') dy = bestH.line - b.bottom;
    }

    return { dx, dy, vLineX: bestV.line, hLineY: bestH.line };
}

// Handle normal alignment (to selection bounds or canvas bounds)
function handleNormalAlignment(activeObject, direction) {
    if (!activeObject) return;
    
    if (activeObject.type === 'activeSelection') {
        // Multi-selection: align objects relative to selection bounds
        const items = activeObject.getObjects();
        if (!items || items.length === 0) return;
        
        const selectionBounds = getObjectBounds(activeObject);
        
        items.forEach(obj => {
            const objBounds = getObjectBounds(obj);
            let dx = 0, dy = 0;
            
            switch(direction) {
                case 'left':
                    dx = (selectionBounds.left - objBounds.left);
                    break;
                case 'center':
                    dx = (selectionBounds.cx - objBounds.cx);
                    break;
                case 'right':
                    dx = (selectionBounds.right - objBounds.right);
                    break;
                case 'top':
                    dy = (selectionBounds.top - objBounds.top);
                    break;
                case 'middle':
                    dy = (selectionBounds.cy - objBounds.cy);
                    break;
                case 'bottom':
                    dy = (selectionBounds.bottom - objBounds.bottom);
                    break;
            }
            
            obj.left += dx;
            obj.top += dy;
            obj.setCoords();
        });
        
        // Update active selection coordinates
        activeObject.setCoords();
        canvas.requestRenderAll();
    } else {
        // Single object: align to canvas bounds
        const objBounds = getObjectBounds(activeObject);
        let dx = 0, dy = 0;
        
        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;
        const canvasBounds = {
            left: 0,
            right: canvasWidth,
            top: 0,
            bottom: canvasHeight,
            cx: canvasWidth / 2,
            cy: canvasHeight / 2
        };
        
        switch(direction) {
            case 'left':
                dx = (canvasBounds.left - objBounds.left);
                break;
            case 'center':
                dx = (canvasBounds.cx - objBounds.cx);
                break;
            case 'right':
                dx = (canvasBounds.right - objBounds.right);
                break;
            case 'top':
                dy = (canvasBounds.top - objBounds.top);
                break;
            case 'middle':
                dy = (canvasBounds.cy - objBounds.cy);
                break;
            case 'bottom':
                dy = (canvasBounds.bottom - objBounds.bottom);
                break;
        }
        
        activeObject.left += dx;
        activeObject.top += dy;
        activeObject.setCoords();
    }
}

// Handle object alignment within group
function handleObjectAlignment(group, direction) {
    if (!group || group.type !== 'group') return;
    
    const groupChildren = group.getObjects();
    if (!groupChildren || groupChildren.length === 0) return;
    
    // Get group's internal bounds (center-based coordinate system)
    const groupWidth = group.width;
    const groupHeight = group.height;
    const groupBounds = {
        left: -groupWidth / 2,
        right: groupWidth / 2,
        top: -groupHeight / 2,
        bottom: groupHeight / 2,
        cx: 0,
        cy: 0
    };
    
    // Align each child relative to group's coordinate system
    groupChildren.forEach(child => {
        const childB = getObjectBounds(child);
        let dx = 0, dy = 0;
        
        switch(direction) {
            case 'left':
                dx = (groupBounds.left - childB.left);
                break;
            case 'center':
                dx = (groupBounds.cx - childB.cx);
                break;
            case 'right':
                dx = (groupBounds.right - childB.right);
                break;
            case 'top':
                dy = (groupBounds.top - childB.top);
                break;
            case 'middle':
                dy = (groupBounds.cy - childB.cy);
                break;
            case 'bottom':
                dy = (groupBounds.bottom - childB.bottom);
                break;
        }
        
        child.left += dx;
        child.top += dy;
        child.setCoords();
    });
    
    // Update group coordinates
    group.addWithUpdate();
}

function initFloatingToolbar() {
    // Show/hide toolbar on selection
    canvas.on('selection:created', updateFloatingToolbar);
    canvas.on('selection:updated', updateFloatingToolbar);
    canvas.on('selection:cleared', hideFloatingToolbar);
    canvas.on('object:moving', updateFloatingToolbarPosition);
    canvas.on('object:scaling', updateFloatingToolbarPosition);
    canvas.on('object:rotating', updateFloatingToolbarPosition);
    canvas.on('object:modified', updateFloatingToolbar);
    
    // Build color swatches
    buildColorSwatches();
    
    // Close popover when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.floating-popover, .pill-item.pill-select').length) {
            $('.floating-popover').removeClass('active');
        }
    });
    
    // Font family dropdown
    $('#floating-font-family').click(function(e) {
        e.stopPropagation();
        
        // Close other popovers
        $('.floating-popover').not('#font-family-popover').removeClass('active');
        
        // Toggle this popover
        const popover = $('#font-family-popover');
        if (popover.length === 0) {
            createFontFamilyPopover($(this));
        } else {
            popover.toggleClass('active');
        }
    });
    
    // Font size dropdown
    $('#floating-font-size').click(function(e) {
        e.stopPropagation();
        
        // Close other popovers
        $('.floating-popover').not('#font-size-popover').removeClass('active');
        
        // Toggle this popover
        const popover = $('#font-size-popover');
        if (popover.length === 0) {
            createFontSizePopover($(this));
        } else {
            popover.toggleClass('active');
        }
    });
    
    // Line height dropdown
    $('#floating-line-height').click(function(e) {
        e.stopPropagation();
        
        // Close other popovers
        $('.floating-popover').not('#lineheight-popover').removeClass('active');
        
        // Toggle this popover
        const popover = $('#lineheight-popover');
        if (popover.length === 0) {
            createLineHeightPopover($(this));
        } else {
            popover.toggleClass('active');
        }
    });
    
    // Color picker trigger
    $('#floating-color-trigger').click(function(e) {
        e.stopPropagation();
        $('#floating-font-color').toggleClass('open');
    });
    
    // Color picker - native input
    $('#floating-color-input').on('change', function() {
        const color = $(this).val();
        applyFloatingFormat('fill', color);
        $('#floating-color-trigger .color-dot').css('background', color);
        updateFloatingToolbar();
    });
    
    // Color swatches click
    $(document).on('click', '.color-swatch', function() {
        const color = $(this).data('color');
        applyFloatingFormat('fill', color);
        $('#floating-color-trigger .color-dot').css('background', color);
        $('#floating-font-color').removeClass('open');
    });
    
    // Advanced color toggle
    $('#floating-color-advanced-toggle').click(function(e) {
        e.stopPropagation();
        $('#floating-color-advanced').toggleClass('open');
        $(this).text($('#floating-color-advanced').hasClass('open') ? 'Sembunyikan' : 'Format Lanjutan');
    });
    
    // Color format tabs
    $('.color-tab').click(function() {
        $('.color-tab').removeClass('active');
        $(this).addClass('active');
        const format = $(this).data('format');
        updateColorDisplay(format);
    });
    
    // Color display input
    $('#floating-color-display').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            const format = $('.color-tab.active').data('format');
            const value = $(this).val();
            applyColorFromFormat(format, value);
        }
    });
    
    // Close color dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#floating-font-color').length) {
            $('#floating-font-color').removeClass('open');
        }
    });
    
    // Text style buttons
    $('#floating-bold').click(function() {
        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;
        
        const textObjects = getTextObjectsFromSelection(activeObject);
        if (textObjects.length === 0) return;
        
        const isBold = getCommonBooleanProperty(textObjects, 'fontWeight', 'bold');
        const newWeight = isBold ? 'normal' : 'bold';
        
        textObjects.forEach(obj => obj.set('fontWeight', newWeight));
        canvas.renderAll();
        hasUnsavedChanges = true;
        triggerAutoSave();
        updateFloatingToolbar();
    });
    
    $('#floating-italic').click(function() {
        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;
        
        const textObjects = getTextObjectsFromSelection(activeObject);
        if (textObjects.length === 0) return;
        
        const isItalic = getCommonBooleanProperty(textObjects, 'fontStyle', 'italic');
        const newStyle = isItalic ? 'normal' : 'italic';
        
        textObjects.forEach(obj => obj.set('fontStyle', newStyle));
        canvas.renderAll();
        hasUnsavedChanges = true;
        triggerAutoSave();
        updateFloatingToolbar();
    });
    
    $('#floating-underline').click(function() {
        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;
        
        const textObjects = getTextObjectsFromSelection(activeObject);
        if (textObjects.length === 0) return;
        
        const isUnderlined = getCommonBooleanProperty(textObjects, 'underline', true);
        const newUnderline = !isUnderlined;
        
        textObjects.forEach(obj => obj.set('underline', newUnderline));
        canvas.renderAll();
        hasUnsavedChanges = true;
        triggerAutoSave();
        updateFloatingToolbar();
    });
    
    // Text alignment buttons
    $('.floating-align-button').click(function() {
        const align = $(this).data('align');
        const activeObject = canvas.getActiveObject();
        if (!activeObject) return;
        
        const isGroup = activeObject.type === 'group' && activeObject.isCustomGroup;
        const isSingleText = activeObject.type === 'text' || activeObject.type === 'i-text' || activeObject.type === 'textbox';
        const alignWithinActive = !!window.alignWithinGroupMode;
        
        if (isGroup && alignWithinActive) {
            // Align objects within group
            handleObjectAlignment(activeObject, align);
            window.lastGroupAlign = align;
        } else if (isSingleText && alignWithinActive) {
            // For single text with align within: use textAlign property
            activeObject.set('textAlign', align);
        } else {
            // Normal alignment: align objects to selection bounds or canvas bounds
            handleNormalAlignment(activeObject, align);
            window.lastNormalAlign = align;
        }
        
        canvas.renderAll();
        hasUnsavedChanges = true;
        triggerAutoSave();
        updateFloatingToolbar();
    });
    
    // Group button
    $('#float-group').click(function() {
        const activeObject = canvas.getActiveObject();
        if (activeObject && activeObject.type === 'activeSelection') {
            const group = activeObject.toGroup();
            group.isCustomGroup = true; // Mark as custom group
            canvas.setActiveObject(group);
            canvas.requestRenderAll();
            hasUnsavedChanges = true;
            triggerAutoSave();
            updateFloatingToolbar();
        }
    });
    
    // Ungroup button
    $('#float-ungroup').click(function() {
        const activeObject = canvas.getActiveObject();
        if (activeObject && activeObject.type === 'group') {
            activeObject.toActiveSelection();
            canvas.requestRenderAll();
            hasUnsavedChanges = true;
            triggerAutoSave();
            updateFloatingToolbar();
        }
    });
    
    // Align within toggle
    $('#align-within-group-checkbox').on('change', function() {
        const isEnabled = $(this).is(':checked');
        window.alignWithinGroupMode = isEnabled;
        
        if (isEnabled) {
            $('#align-mode-toggle').addClass('active');
        } else {
            $('#align-mode-toggle').removeClass('active');
        }
        
        updateFloatingToolbar();
    });
}

function updateFloatingToolbar() {
    const activeObject = canvas.getActiveObject();
    
    if (!activeObject) {
        hideFloatingToolbar();
        return;
    }
    
    const textObjects = getTextObjectsFromSelection(activeObject);
    
    // Show toolbar only if there are text objects
    if (textObjects.length === 0) {
        hideFloatingToolbar();
        return;
    }
    
    // Update info label
    if (textObjects.length === 1) {
        $('#floating-toolbar-info').text('1 objek teks dipilih');
    } else {
        $('#floating-toolbar-info').text(textObjects.length + ' objek teks dipilih');
    }
    
    // Get common properties
    const commonFont = getCommonPropertyValue(textObjects, 'fontFamily');
    const commonSize = getCommonPropertyValue(textObjects, 'fontSize');
    const commonLineHeight = getCommonPropertyValue(textObjects, 'lineHeight');
    const commonColor = getCommonPropertyValue(textObjects, 'fill');
    const commonBold = getCommonBooleanProperty(textObjects, 'fontWeight', 'bold');
    const commonItalic = getCommonBooleanProperty(textObjects, 'fontStyle', 'italic');
    const commonUnderline = getCommonBooleanProperty(textObjects, 'underline', true);
    const commonAlign = getCommonPropertyValue(textObjects, 'textAlign');
    
    // Font family
    if (commonFont === null) {
        $('#floating-font-family .value').html('<em>Mixed</em>');
    } else {
        $('#floating-font-family .value').text(commonFont || 'Arial');
    }
    
    // Font size
    if (commonSize === null) {
        $('#floating-font-size .value').html('<em>Mixed</em>');
    } else {
        $('#floating-font-size .value').text(Math.round(commonSize || 16));
    }
    
    // Line height
    if (commonLineHeight === null) {
        $('#floating-line-height .value').html('<em>Mixed</em>');
    } else {
        const lh = parseFloat(commonLineHeight) || 1.16;
        $('#floating-line-height .value').text(lh.toFixed(2));
    }
    
    // Color
    if (commonColor === null) {
        $('#floating-color-input').val('#000000');
        $('#floating-color-trigger .color-dot').css('background', '#000000');
    } else {
        const color = commonColor || '#000000';
        $('#floating-color-input').val(color);
        $('#floating-color-trigger .color-dot').css('background', color);
    }
    // Selalu set background tombol warna ke putih
    $('#floating-color-trigger').css('background', '#fff');
    
    // Bold
    if (commonBold === null) {
        $('#floating-bold').addClass('mixed').removeClass('active');
    } else if (commonBold) {
        $('#floating-bold').addClass('active').removeClass('mixed');
    } else {
        $('#floating-bold').removeClass('active mixed');
    }
    
    // Italic
    if (commonItalic === null) {
        $('#floating-italic').addClass('mixed').removeClass('active');
    } else if (commonItalic) {
        $('#floating-italic').addClass('active').removeClass('mixed');
    } else {
        $('#floating-italic').removeClass('active mixed');
    }
    
    // Underline
    if (commonUnderline === null) {
        $('#floating-underline').addClass('mixed').removeClass('active');
    } else if (commonUnderline) {
        $('#floating-underline').addClass('active').removeClass('mixed');
    } else {
        $('#floating-underline').removeClass('active mixed');
    }
    
    // Text alignment
    $('.floating-align-button').removeClass('active mixed');
    
    // Check if align within mode is active
    const isCustomGroup = activeObject.type === 'group' && activeObject.isCustomGroup;
    const isSingleTextObj = activeObject.type === 'text' || activeObject.type === 'i-text' || activeObject.type === 'textbox';
    const alignWithinActive = !!window.alignWithinGroupMode;
    
    if (isCustomGroup && alignWithinActive) {
        // Use group align state
        const currentGroupAlign = window.lastGroupAlign || 'left';
        $(`.floating-align-button[data-align="${currentGroupAlign}"]`).addClass('active');
    } else if (isSingleTextObj && alignWithinActive) {
        // Use textAlign property for single text
        const currentTextAlign = activeObject.textAlign || 'left';
        $(`.floating-align-button[data-align="${currentTextAlign}"]`).addClass('active');
    } else {
        // Use normal align state
        const currentNormalAlign = window.lastNormalAlign || 'left';
        $(`.floating-align-button[data-align="${currentNormalAlign}"]`).addClass('active');
    }
    
    // Show/hide group buttons and align toggle
    const isMultiSelect = activeObject.type === 'activeSelection';
    const isGroup = activeObject.type === 'group';
    const isSingleText = activeObject.type === 'text' || activeObject.type === 'i-text' || activeObject.type === 'textbox';
    
    if (isMultiSelect) {
        $('#floating-group-row').addClass('active');
        $('#float-group').show();
        $('#float-ungroup').hide();
        $('#align-mode-toggle').hide();
        $('#align-within-group-checkbox').prop('checked', false);
    } else if (isGroup) {
        $('#floating-group-row').addClass('active');
        $('#float-group').hide();
        $('#float-ungroup').show();
        // Only show align toggle for custom groups
        if (activeObject.isCustomGroup) {
            $('#align-mode-toggle').show();
            $('#align-within-group-checkbox')
                .prop('checked', !!window.alignWithinGroupMode)
                .prop('disabled', false);
            $('#align-mode-toggle').toggleClass('active', !!window.alignWithinGroupMode);
        } else {
            $('#align-mode-toggle').hide();
        }
    } else if (isSingleText) {
        // Show align toggle for single text objects
        $('#floating-group-row').addClass('active');
        $('#float-group').hide();
        $('#float-ungroup').hide();
        $('#align-mode-toggle').show();
        $('#align-within-group-checkbox')
            .prop('checked', !!window.alignWithinGroupMode)
            .prop('disabled', false);
        $('#align-mode-toggle').toggleClass('active', !!window.alignWithinGroupMode);
    } else {
        $('#floating-group-row').removeClass('active');
        $('#align-mode-toggle').hide();
    }
    
    // Position and show toolbar
    updateFloatingToolbarPosition();
    $('#floating-toolbar').show();
}

function updateFloatingToolbarPosition() {
    const activeObject = canvas.getActiveObject();
    if (!activeObject) return;
    
    const container = $('#floating-toolbar');
    if (!container.length) return;
    
    // Get bounding box of selected object
    const bounds = activeObject.getBoundingRect(true, true);
    const containerWrapper = $('.certificate-canvas-wrapper');
    const containerWidth = containerWrapper.width();
    const containerHeight = containerWrapper.height();
    
    // Show temporarily to measure
    const wasHidden = container.css('display') === 'none';
    if (wasHidden) {
        container.css({ visibility: 'hidden', display: 'block' });
    }
    
    const panelWidth = container.outerWidth();
    const panelHeight = container.outerHeight();
    const margin = 12;
    
    // Get canvas offset within wrapper
    const canvasContainer = $('.canvas-container');
    const canvasOffsetLeft = canvasContainer.position().left;
    const canvasOffsetTop = canvasContainer.position().top;
    
    // Center horizontally on selected object
    let left = canvasOffsetLeft + bounds.left + (bounds.width / 2) - (panelWidth / 2);
    const maxLeft = Math.max(margin, containerWidth - panelWidth - margin);
    left = Math.min(Math.max(left, margin), maxLeft);
    
    // Position above or below selected object
    const absoluteTop = canvasOffsetTop + bounds.top;
    const absoluteBottom = canvasOffsetTop + bounds.top + bounds.height;
    
    const desiredAbove = absoluteTop - panelHeight - margin;
    const desiredBelow = absoluteBottom + margin;
    const spaceAbove = absoluteTop;
    const spaceBelow = containerHeight - absoluteBottom;
    
    let top = desiredAbove;
    if (desiredAbove < margin) {
        if (desiredBelow + panelHeight <= containerHeight - margin) {
            top = desiredBelow;
        } else if (spaceBelow >= spaceAbove) {
            top = Math.min(Math.max(desiredBelow, margin), containerHeight - panelHeight - margin);
        } else {
            top = Math.min(Math.max(desiredAbove, margin), containerHeight - panelHeight - margin);
        }
    }
    
    const maxTop = Math.max(margin, containerHeight - panelHeight - margin);
    top = Math.min(Math.max(top, margin), maxTop);
    
    // Determine placement
    let placement = 'overlap';
    if (top + panelHeight <= absoluteTop - 4) {
        placement = 'above';
    } else if (top >= absoluteBottom + 4) {
        placement = 'below';
    }
    
    container
        .css({ left: left + 'px', top: top + 'px', visibility: 'visible' })
        .data('placement', placement)
        .attr('data-placement', placement)
        .show();
}

function hideFloatingToolbar() {
    $('#floating-toolbar').hide();
    $('.floating-popover').remove();
}

// Color format conversion helpers
function updateColorDisplay(format) {
    const color = $('#floating-color-input').val();
    let displayValue = color;
    
    if (format === 'rgb') {
        const rgb = hexToRgb(color);
        if (rgb) {
            displayValue = `${rgb.r}, ${rgb.g}, ${rgb.b}`;
        }
    } else if (format === 'hsl') {
        const rgb = hexToRgb(color);
        if (rgb) {
            const hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
            if (hsl) {
                displayValue = `${Math.round(hsl.h)}, ${Math.round(hsl.s)}%, ${Math.round(hsl.l)}%`;
            }
        }
    }
    
    $('#floating-color-display').val(displayValue);
}

function rgbToHsl(r, g, b) {
    r /= 255;
    g /= 255;
    b /= 255;
    
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    
    if (max === min) {
        h = s = 0;
    } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        
        h /= 6;
    }
    
    return {
        h: h * 360,
        s: s * 100,
        l: l * 100
    };
}

function hslToRgb(h, s, l) {
    h /= 360;
    s /= 100;
    l /= 100;
    
    let r, g, b;
    
    if (s === 0) {
        r = g = b = l;
    } else {
        const hue2rgb = (p, q, t) => {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1/6) return p + (q - p) * 6 * t;
            if (t < 1/2) return q;
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
            return p;
        };
        
        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        
        r = hue2rgb(p, q, h + 1/3);
        g = hue2rgb(p, q, h);
        b = hue2rgb(p, q, h - 1/3);
    }
    
    return {
        r: Math.round(r * 255),
        g: Math.round(g * 255),
        b: Math.round(b * 255)
    };
}

function applyColorFromFormat(format, value) {
    let hex = '#000000';
    
    if (format === 'hex') {
        hex = value.startsWith('#') ? value : '#' + value;
    } else if (format === 'rgb') {
        const parts = value.split(',').map(s => parseInt(s.trim()));
        if (parts.length === 3) {
            hex = rgbToHex(parts[0], parts[1], parts[2]);
        }
    } else if (format === 'hsl') {
        const parts = value.split(',').map(s => parseFloat(s.trim()));
        if (parts.length === 3) {
            const rgb = hslToRgb(parts[0], parts[1], parts[2]);
            hex = rgbToHex(rgb.r, rgb.g, rgb.b);
        }
    }
    
    applyFloatingFormat('fill', hex);
}

// Create Font Family Popover
function createFontFamilyPopover(anchor) {
    const fonts = [
        'Arial', 'Helvetica', 'Times New Roman', 'Georgia', 
        'Verdana', 'Tahoma', 'Trebuchet MS', 'Comic Sans MS', 
        'Impact', 'Calibri'
    ];
    
    const popover = $(`
        <div id="font-family-popover" class="floating-popover active">
            <div class="popover-title">FONT FAMILY</div>
            <input type="text" class="search-input" placeholder="Cari font..." />
            <div class="option-grid font-list"></div>
        </div>
    `);
    
    anchor.append(popover);
    
    const currentFont = anchor.find('.value').text().trim();
    
    function renderFonts(filterText = '') {
        // Get current font from canvas selection
        const activeObject = canvas.getActiveObject();
        let currentFont = '';
        if (activeObject) {
            const textObjects = getTextObjectsFromSelection(activeObject);
            if (textObjects.length > 0) {
                currentFont = textObjects[0].fontFamily || '';
            }
        }
        const filtered = fonts.filter(f => 
            f.toLowerCase().includes(filterText.toLowerCase())
        );
        const html = filtered.map(font => `
            <div class="option-button font-option ${font === currentFont ? 'active' : ''}" data-font="${font}">
                <span class="sample" style="font-family: ${font};">${font}</span>
            </div>
        `).join('');
        popover.find('.option-grid').html(html);
        // Handler: update font live and re-render popover
        popover.find('.font-option').click(function(e) {
            e.stopPropagation();
            const selectedFont = $(this).data('font');
            applyFloatingFormat('fontFamily', selectedFont);
            // Update tombol utama
            anchor.find('.value').text(selectedFont);
            // Re-render popover to update active class
            renderFonts(filterText);
        });
    }
    renderFonts();
    popover.find('.search-input').on('input', function() {
        renderFonts($(this).val());
    });
    popover.click(function(e) {
        e.stopPropagation();
    });
}

// Create Font Size Popover
function createFontSizePopover(anchor) {
    const sizes = [8, 10, 12, 14, 16, 18, 20, 24, 28, 32, 36, 48, 64, 72, 96];
    
    const popover = $(`
        <div id="font-size-popover" class="floating-popover active">
            <div class="popover-title">UKURAN FONT</div>
            <div class="option-grid"></div>
            <div class="input-row">
                <div class="input-group">
                    <button class="spin-button" data-action="decrement">−</button>
                    <input type="number" id="custom-size-input" value="24" min="1" max="999" />
                    <button class="spin-button" data-action="increment">+</button>
                </div>
            </div>
        </div>
    `);
    
    anchor.append(popover);
    
    function renderSizes() {
        // Get current size from canvas selection
        const activeObject = canvas.getActiveObject();
        let currentSize = 24;
        if (activeObject) {
            const textObjects = getTextObjectsFromSelection(activeObject);
            if (textObjects.length > 0) {
                currentSize = parseInt(textObjects[0].fontSize) || 24;
            }
        }
        const sizeButtons = sizes.map(size => `
            <div class="option-button ${size === currentSize ? 'active' : ''}" data-size="${size}">${size}px</div>
        `).join('');
        popover.find('.option-grid').html(sizeButtons);
        popover.find('#custom-size-input').val(currentSize);
        popover.find('.option-button').click(function(e) {
            e.stopPropagation();
            const size = parseInt($(this).data('size'));
            applyFloatingFormat('fontSize', size);
            anchor.find('.value').text(size + ' px');
            renderSizes();
        });
    }
    renderSizes();
    popover.find('.spin-button').click(function(e) {
        e.stopPropagation();
        const input = $('#custom-size-input');
        let val = parseInt(input.val()) || 24;
        if ($(this).data('action') === 'increment') {
            val = Math.min(999, val + 1);
        } else {
            val = Math.max(1, val - 1);
        }
        input.val(val);
        applyFloatingFormat('fontSize', val);
        anchor.find('.value').text(val + ' px');
        renderSizes();
    });
    popover.find('#custom-size-input').on('change', function() {
        const val = parseInt($(this).val()) || 24;
        applyFloatingFormat('fontSize', val);
        anchor.find('.value').text(val + ' px');
        renderSizes();
    });
    popover.click(function(e) {
        e.stopPropagation();
    });
}

// Create Line Height Popover
function createLineHeightPopover(anchor) {
    const lineHeights = [0.8, 1, 1.2, 1.5, 1.75, 2, 2.5, 3];
    
    const popover = $(`
        <div id="lineheight-popover" class="floating-popover active">
            <div class="popover-title">SPASI BARIS</div>
            <div class="option-grid"></div>
            <div class="input-row">
                <div class="input-group">
                    <button class="spin-button" data-action="decrement">−</button>
                    <input type="number" id="custom-lineheight-input" value="1.20" step="0.1" min="0.1" max="10" />
                    <button class="spin-button" data-action="increment">+</button>
                </div>
            </div>
        </div>
    `);
    
    anchor.append(popover);
    
    function renderLineHeights() {
        // Get current lineHeight from canvas selection
        const activeObject = canvas.getActiveObject();
        let currentLH = 1.2;
        if (activeObject) {
            const textObjects = getTextObjectsFromSelection(activeObject);
            if (textObjects.length > 0) {
                currentLH = parseFloat(textObjects[0].lineHeight) || 1.2;
            }
        }
        const lhButtons = lineHeights.map(lh => `
            <div class="option-button ${Math.abs(lh - currentLH) < 0.01 ? 'active' : ''}" data-lineheight="${lh}">${lh.toFixed(1)}</div>
        `).join('');
        popover.find('.option-grid').html(lhButtons);
        popover.find('#custom-lineheight-input').val(currentLH.toFixed(2));
        popover.find('.option-button').click(function(e) {
            e.stopPropagation();
            const lh = parseFloat($(this).data('lineheight'));
            applyFloatingFormat('lineHeight', lh);
            anchor.find('.value').text(lh.toFixed(2));
            renderLineHeights();
        });
    }
    renderLineHeights();
    popover.find('.spin-button').click(function(e) {
        e.stopPropagation();
        const input = $('#custom-lineheight-input');
        let val = parseFloat(input.val()) || 1.2;
        if ($(this).data('action') === 'increment') {
            val = Math.min(10, val + 0.1);
        } else {
            val = Math.max(0.1, val - 0.1);
        }
        input.val(val.toFixed(2));
        applyFloatingFormat('lineHeight', val);
        anchor.find('.value').text(val.toFixed(2));
        renderLineHeights();
    });
    popover.find('#custom-lineheight-input').on('change', function() {
        const val = parseFloat($(this).val()) || 1.2;
        applyFloatingFormat('lineHeight', val);
        anchor.find('.value').text(val.toFixed(2));
        renderLineHeights();
    });
    popover.click(function(e) {
        e.stopPropagation();
    });
}

function applyFloatingFormat(property, value) {
    const activeObject = canvas.getActiveObject();
    if (!activeObject) return;
    
    const textObjects = getTextObjectsFromSelection(activeObject);
    if (textObjects.length === 0) {
        // If not a text selection, try to apply to active object directly
        activeObject.set(property, value);
    } else {
        // Apply to all text objects in selection
        textObjects.forEach(obj => {
            obj.set(property, value);
        });
    }
    
    canvas.renderAll();
    hasUnsavedChanges = true;
    triggerAutoSave();
    updateFloatingToolbar();
}

// Initialize on document ready
$(document).ready(function() {
    initCanvas();
    loadCertificate(0);
    bindEvents();
    
    // Prevent accidental page leave with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

function initCanvas() {
    canvas = new fabric.Canvas('certificate-canvas', {
        width: 1123,
        height: 794,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });
    
    // Initialize undo/redo manager
    undoRedoManager = new UndoRedoManager(canvas);
    
    // Initialize floating toolbar
    initFloatingToolbar();
    
    // Track modifications
    canvas.on('object:modified', function() {
        hasUnsavedChanges = true;
        triggerAutoSave();
    });
    
    canvas.on('text:changed', function() {
        hasUnsavedChanges = true;
        triggerAutoSave();
    });
}

// 🆕 MULTI-PAGE: Helper functions for converting canvas properties
function convertToNumber(val) {
    if (val === null || val === undefined || val === '') return null;
    const num = parseFloat(val);
    return isNaN(num) ? val : num;
}

function convertToBoolean(val) {
    if (val === null || val === undefined || val === '') return false;
    if (typeof val === 'boolean') return val;
    return val === 'true' || val === true || val === 1 || val === '1';
}

function fixObjectProperties(obj) {
    // Convert numeric properties
    const numericProps = ['left', 'top', 'width', 'height', 'scaleX', 'scaleY', 'angle', 
                         'fontSize', 'strokeWidth', 'opacity', 'skewX', 'skewY',
                         'x1', 'y1', 'x2', 'y2', 'rx', 'ry', 'radius'];
    numericProps.forEach(prop => {
        if (obj[prop] !== undefined) {
            obj[prop] = convertToNumber(obj[prop]);
        }
    });
    
    // Convert boolean properties
    const boolProps = ['flipX', 'flipY', 'visible', 'isPlaceholder', 'hasControls', 
                      'hasBorders', 'selectable', 'evented', 'underline', 'linethrough', 'overline'];
    boolProps.forEach(prop => {
        if (obj[prop] !== undefined) {
            obj[prop] = convertToBoolean(obj[prop]);
        }
    });
}

function loadCertificate(index) {
    if (index < 0 || index >= PROJECT_DATA.certificates.length) {
        return;
    }
    
    // 🔧 BUG FIX: Block navigation if save is in progress
    if (isSaving) {
        console.warn('Save in progress, navigation blocked. Queuing navigation request...');
        // Queue the navigation request to execute after save completes
        saveQueue.push(() => loadCertificate(index));
        return;
    }
    
    currentCertificateIndex = index;
    const cert = PROJECT_DATA.certificates[index];
    currentPageIndex = 0; // 🔧 MULTI-PAGE: Reset to first page when loading certificate
    
    console.log('Loading certificate:', cert.id, cert.recipient_name);
    console.log('Certificate data:', {
        has_canvas_pages: !!cert.canvas_pages,
        canvas_pages_type: typeof cert.canvas_pages,
        canvas_pages_isArray: Array.isArray(cert.canvas_pages),
        canvas_pages_length: cert.canvas_pages ? cert.canvas_pages.length : 0,
        has_canvas_state: !!cert.canvas_state,
        canvas_state_type: typeof cert.canvas_state
    });
    
    // Clear canvas
    canvas.clear();
    canvas.backgroundColor = '#ffffff';
    
    // 🆕 MULTI-PAGE: Detect format and load appropriate data
    let canvasStateToLoad = null;
    let pageBackgroundImage = null;
    let pageBackgroundColor = null;
    
    if (cert.canvas_pages && Array.isArray(cert.canvas_pages) && cert.canvas_pages.length > 0) {
        // Multi-page certificate - load first page
        console.log('Loading multi-page certificate, total pages:', cert.canvas_pages.length);
        
        // 🔧 VERIFY: Check participant data in each page
        cert.canvas_pages.forEach((page, idx) => {
            console.log(`Page ${idx + 1} metadata:`, {
                participantName: page.participantName || 'NOT SET',
                certificateNumber: page.certificateNumber || 'NOT SET',
                hasState: !!page.state,
                objectCount: page.state?.objects?.length || 0
            });
        });
        
        const firstPage = cert.canvas_pages[0];
        canvasStateToLoad = firstPage.state;
        pageBackgroundImage = firstPage.bgImage;
        pageBackgroundColor = firstPage.bgColor;
    } else if (cert.canvas_state) {
        // Legacy single-page certificate
        console.log('Loading single-page certificate (legacy format)');
        // Parse if string, otherwise use as-is
        if (typeof cert.canvas_state === 'string') {
            try {
                canvasStateToLoad = JSON.parse(cert.canvas_state);
            } catch (e) {
                console.error('Failed to parse canvas_state:', e);
                canvasStateToLoad = null;
            }
        } else {
            canvasStateToLoad = cert.canvas_state;
        }
    }
    
    // 🔧 Load page background first (for multi-page)
    if (pageBackgroundImage) {
        fabric.Image.fromURL(pageBackgroundImage, function(img) {
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                scaleX: canvas.width / img.width,
                scaleY: canvas.height / img.height
            });
        });
    } else if (pageBackgroundColor) {
        canvas.backgroundColor = pageBackgroundColor;
    }
    
    // Load canvas state
    if (canvasStateToLoad && canvasStateToLoad.objects) {
        console.log('Canvas state objects count:', canvasStateToLoad.objects.length);
        
        // � DEBUG: Check for signature blocks in canvas objects
        let signatureBlockCount = 0;
        canvasStateToLoad.objects.forEach((obj, idx) => {
            if (obj.type === 'group' && obj.isSignatureBlock) {
                signatureBlockCount++;
                console.log(`🖊️ Signature Block #${obj.signatureIndex || 0} found at object[${idx}]:`, {
                    hasObjects: !!obj.objects,
                    objectsCount: obj.objects?.length || 0,
                    signatureIndex: obj.signatureIndex
                });
                
                // Check child objects for signature fields
                if (obj.objects) {
                    obj.objects.forEach((child, childIdx) => {
                        // Log ALL child objects, not just those with signatureField
                        console.log(`  └─ Child[${childIdx}] (${child.type}):`, {
                            signatureField: child.signatureField || 'NOT SET',
                            text: (child.type === 'textbox' || child.type === 'i-text' || child.type === 'text') ? (child.text || 'EMPTY') : 'N/A',
                            src: child.type === 'image' ? (child.src ? `${child.src.substring(0, 50)}...` : 'NO_SRC') : 'N/A',
                            isPlaceholder: child.isPlaceholder || false,
                            placeholderType: child.placeholderType || 'N/A'
                        });
                    });
                }
            }
        });
        console.log(`Total signature blocks found: ${signatureBlockCount}`);
        
        // �🔧 DEBUG: Check for backgroundImage
        console.log('Has backgroundImage:', !!canvasStateToLoad.backgroundImage);
        if (canvasStateToLoad.backgroundImage) {
            console.log('Background type:', typeof canvasStateToLoad.backgroundImage);
            if (typeof canvasStateToLoad.backgroundImage === 'object') {
                console.log('Background src:', canvasStateToLoad.backgroundImage.src || 'NO SRC');
            } else {
                console.log('Background value:', canvasStateToLoad.backgroundImage);
            }
        }
        
        // Fix: Convert string properties to numbers (Fabric.js requirement)
        const fixedCanvasState = JSON.parse(JSON.stringify(canvasStateToLoad));
        
        // Fix background image if exists
        if (fixedCanvasState.backgroundImage) {
            fixObjectProperties(fixedCanvasState.backgroundImage);
        }
        
        // Fix all canvas objects
        fixedCanvasState.objects.forEach(obj => {
            fixObjectProperties(obj);
            
            // Handle nested objects (groups)
            if (obj.objects && Array.isArray(obj.objects)) {
                obj.objects.forEach(childObj => {
                    fixObjectProperties(childObj);
                });
            }
        });
        
        canvas.loadFromJSON(fixedCanvasState, function() {
            console.log('Canvas loaded, objects on canvas:', canvas.getObjects().length);
            canvas.renderAll();
            
            // Reset undo/redo stacks for new certificate
            if (undoRedoManager) {
                undoRedoManager.reset();
                // Save initial state
                undoRedoManager.saveState();
            }
            
            // Force render after short delay to ensure all images are loaded
            setTimeout(function() {
                canvas.renderAll();
            }, 100);
        }, function(o, object) {
            console.log('Loading object:', object.type);
        });
    } else {
        console.warn('No canvas state found for certificate', cert.id);
    }
    
    // Update UI
    $('#recipient-name').text(cert.recipient_name);
    $('#certificate-number').text(cert.certificate_number);
    $('#current-page').text(index + 1);
    $('#edit-status').removeClass('badge-info badge-warning')
        .addClass(cert.is_edited ? 'badge-warning' : 'badge-info')
        .html(cert.is_edited ? '<i class="fas fa-edit"></i> Edited' : 'Not Edited');
    
    // 🆕 MULTI-PAGE: Initialize page navigation
    initializePageNavigation(cert);
    
    // Update thumbnails
    $('.thumbnail-item').removeClass('active');
    $(`.thumbnail-item[data-page-number="${index + 1}"]`).addClass('active');
    
    // Update navigation buttons
    $('#prev-certificate').prop('disabled', index === 0);
    $('#next-certificate').prop('disabled', index === PROJECT_DATA.certificates.length - 1);
    
    hasUnsavedChanges = false;
    hideSaveIndicator();
}

function saveCertificate() {
    // 🔧 BUG FIX: Prevent concurrent saves causing data corruption
    if (isSaving) {
        console.warn('Save already in progress, skipping duplicate request');
        return;
    }
    
    isSaving = true; // Lock save operation
    
    const cert = PROJECT_DATA.certificates[currentCertificateIndex];
    const canvasState = canvas.toJSON(['isPlaceholder', 'placeholderType', 'isSignatureBlock', 'signatureIndex', 'signatureField', 'isCustomGroup']);
    
    // Ensure backgroundImage and backgroundColor are included
    if (canvas.backgroundImage) {
        canvasState.backgroundImage = canvas.backgroundImage.toObject();
    }
    if (canvas.backgroundColor) {
        canvasState.backgroundColor = canvas.backgroundColor;
    }
    
    console.log('Saving certificate #' + cert.id + ' (' + cert.recipient_name + ')'); // 🔧 Added participant name for debugging
    console.log('Saving canvas state with background:', canvasState.backgroundImage ? 'Yes' : 'No');
    console.log('Canvas state objects:', canvasState.objects?.length || 0);
    
    showSaveIndicator('saving');
    
    // 🆕 MULTI-PAGE: Check if certificate has multi-page data (from template)
    let canvasPages = null;
    if (cert.canvas_pages && Array.isArray(cert.canvas_pages)) {
        // Update current page with current canvas state before saving
        canvasPages = [...cert.canvas_pages];
        canvasPages[currentPageIndex] = {
            ...canvasPages[currentPageIndex],
            state: canvasState
        };
        console.log(`Updating multi-page certificate, saving page ${currentPageIndex + 1}/${canvasPages.length}`);
    }
    
    $.ajax({
        url: '{{ route("projects.certificates.update") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        contentType: 'application/json',
        data: JSON.stringify({
            certificate_id: cert.id,
            canvas_state: canvasState, // Backward compatibility
            canvas_pages: canvasPages // 🆕 MULTI-PAGE: Send pages if exists
        }),
        success: function(response) {
            if (response.success) {
                hasUnsavedChanges = false;
                showSaveIndicator('saved');
                
                console.log('Certificate saved successfully:', {
                    id: cert.id,
                    recipient: cert.recipient_name, // 🔧 Added participant verification
                    is_edited: response.is_edited
                });
                
                // Update local data
                PROJECT_DATA.certificates[currentCertificateIndex].is_edited = response.is_edited;
                PROJECT_DATA.certificates[currentCertificateIndex].canvas_state = canvasState;
                
                // 🔧 MULTI-PAGE: Update canvas_pages in PROJECT_DATA
                if (canvasPages) {
                    PROJECT_DATA.certificates[currentCertificateIndex].canvas_pages = canvasPages;
                }
                
                // Update edit status badge
                $('#edit-status').removeClass('badge-info').addClass('badge-warning')
                    .html('<i class="fas fa-edit"></i> Edited');
                
                // Update thumbnail
                $(`.thumbnail-item[data-certificate-id="${cert.id}"]`).find('.edited-badge').remove();
                $(`.thumbnail-item[data-certificate-id="${cert.id}"]`).prepend('<span class="edited-badge">EDITED</span>');
                
                // Update edited count
                updateEditedCount();
                
                // 🔧 BUG FIX: Release save lock and process queue
                isSaving = false;
                processQueuedActions();
                
            } else {
                showSaveIndicator('error');
                toastr.error(response.message || 'Failed to save');
                
                // 🔧 BUG FIX: Release save lock on error
                isSaving = false;
                processQueuedActions();
            }
        },
        error: function(xhr) {
            showSaveIndicator('error');
            toastr.error('Error saving certificate');
            console.error(xhr);
            
            // 🔧 BUG FIX: Release save lock on error
            isSaving = false;
            processQueuedActions();
        }
    });
}

function triggerAutoSave() {
    // Show indicator immediately
    showSaveIndicator('saving');
    
    // Clear previous timeout
    clearTimeout(autoSaveTimeout);
    
    // Set new timeout for auto-save
    autoSaveTimeout = setTimeout(function() {
        if (hasUnsavedChanges) {
            saveCertificate();
        }
    }, 2000); // Auto-save after 2 seconds of inactivity
}

// 🔧 BUG FIX: Process queued actions after save completes
function processQueuedActions() {
    console.log('Processing queued actions, queue length:', saveQueue.length);
    
    if (saveQueue.length > 0) {
        // Execute the first queued action
        const nextAction = saveQueue.shift();
        console.log('Executing queued action:', nextAction.name);
        nextAction();
    }
}

// 🆕 MULTI-PAGE: Initialize page navigation UI
function initializePageNavigation(cert) {
    // Check if certificate has multiple pages
    if (cert.canvas_pages && Array.isArray(cert.canvas_pages) && cert.canvas_pages.length > 1) {
        totalPages = cert.canvas_pages.length;
        
        // Show page navigation
        $('#page-navigation').addClass('active');
        
        // Generate page tabs
        const $pageTabs = $('#page-tabs');
        $pageTabs.empty();
        
        for (let i = 0; i < totalPages; i++) {
            const $tab = $(`<div class="page-tab ${i === currentPageIndex ? 'active' : ''}" data-page-index="${i}">
                <i class="fas fa-file-alt"></i> Page ${i + 1}
            </div>`);
            
            $tab.on('click', function() {
                switchToPage($(this).data('page-index'));
            });
            
            $pageTabs.append($tab);
        }
        
        // Update page info
        $('#current-page-info').text(`Page ${currentPageIndex + 1} of ${totalPages}`);
    } else {
        // Hide page navigation for single-page certificates
        $('#page-navigation').removeClass('active');
        currentPageIndex = 0;
        totalPages = 1;
    }
}

// 🆕 MULTI-PAGE: Switch to specific page
function switchToPage(pageIndex) {
    if (pageIndex === currentPageIndex) return;
    
    // 🔧 BUG FIX: Block page switching if save is in progress
    if (isSaving) {
        console.warn('Save in progress, page switch blocked. Queuing switch request...');
        saveQueue.push(() => switchToPage(pageIndex));
        return;
    }
    
    const cert = PROJECT_DATA.certificates[currentCertificateIndex];
    
    // Validate page index
    if (!cert.canvas_pages || pageIndex < 0 || pageIndex >= cert.canvas_pages.length) {
        console.error('Invalid page index:', pageIndex);
        return;
    }
    
    // 🔧 BUG FIX: Wait for save to complete before switching
    if (hasUnsavedChanges) {
        console.log(`Saving page ${currentPageIndex + 1} before switching to page ${pageIndex + 1}...`);
        
        saveCurrentPageState().then(() => {
            hasUnsavedChanges = false;
            performPageSwitch(pageIndex, cert);
        }).catch(error => {
            console.error('Error saving page state:', error);
            toastr.error('Failed to save current page');
        });
    } else {
        performPageSwitch(pageIndex, cert);
    }
}

// 🔧 BUG FIX: Separate page switch logic to avoid code duplication
function performPageSwitch(pageIndex, cert) {
    currentPageIndex = pageIndex;
    const pageData = cert.canvas_pages[pageIndex];
    
    console.log(`Switching to page ${pageIndex + 1}/${cert.canvas_pages.length} for cert #${cert.id} (${cert.recipient_name})`);
    
    // Clear canvas
    canvas.clear();
    
    // Load page background
    if (pageData.bgImage) {
        fabric.Image.fromURL(pageData.bgImage, function(img) {
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                scaleX: canvas.width / img.width,
                scaleY: canvas.height / img.height
            });
        });
    } else if (pageData.bgColor) {
        canvas.backgroundColor = pageData.bgColor;
    } else {
        canvas.backgroundColor = '#ffffff';
    }
    
    // Load page canvas state
    if (pageData.state && pageData.state.objects) {
        const fixedState = JSON.parse(JSON.stringify(pageData.state));
        
        // Apply same property conversions as loadCertificate
        fixedState.objects.forEach(obj => fixObjectProperties(obj));
        
        canvas.loadFromJSON(fixedState, function() {
            canvas.renderAll();
            console.log(`Page ${pageIndex + 1} loaded, objects:`, canvas.getObjects().length);
        });
    }
    
    // Update UI
    $('.page-tab').removeClass('active');
    $(`.page-tab[data-page-index="${pageIndex}"]`).addClass('active');
    $('#current-page-info').text(`Page ${pageIndex + 1} of ${totalPages}`);
    
    // Reset undo/redo for new page
    if (undoRedoManager) {
        undoRedoManager.reset();
        undoRedoManager.saveState();
    }
    
    hasUnsavedChanges = false;
}

// 🆕 MULTI-PAGE: Save current page state (used before switching pages)
function saveCurrentPageState() {
    // 🔧 BUG FIX: Prevent concurrent saves
    if (isSaving) {
        console.warn('Save already in progress, skipping page state save');
        return Promise.resolve(); // Return resolved promise for chaining
    }
    
    isSaving = true; // Lock save operation
    
    const cert = PROJECT_DATA.certificates[currentCertificateIndex];
    
    if (!cert.canvas_pages || !Array.isArray(cert.canvas_pages)) {
        isSaving = false;
        return Promise.resolve();
    }
    
    const canvasState = canvas.toJSON(['isPlaceholder', 'placeholderType', 'isSignatureBlock', 
                                       'signatureIndex', 'signatureField', 'isCustomGroup']);
    
    // Ensure backgroundImage and backgroundColor are included
    if (canvas.backgroundImage) {
        canvasState.backgroundImage = canvas.backgroundImage.toObject();
    }
    if (canvas.backgroundColor) {
        canvasState.backgroundColor = canvas.backgroundColor;
    }
    
    // Update current page in canvas_pages array (in memory)
    cert.canvas_pages[currentPageIndex].state = canvasState;
    
    // Also update PROJECT_DATA to persist changes
    PROJECT_DATA.certificates[currentCertificateIndex].canvas_pages[currentPageIndex].state = canvasState;
    
    console.log(`Saving page ${currentPageIndex + 1} state for cert #${cert.id} (${cert.recipient_name})`); // 🔧 Added participant verification
    
    // 🔧 Save to server immediately
    showSaveIndicator('saving');
    
    return $.ajax({
        url: '{{ route("projects.certificates.update") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        contentType: 'application/json',
        data: JSON.stringify({
            certificate_id: cert.id,
            canvas_state: canvasState, // Backward compatibility
            canvas_pages: cert.canvas_pages // Send all pages
        }),
        success: function(response) {
            if (response.success) {
                showSaveIndicator('saved');
                console.log(`Page ${currentPageIndex + 1} saved to server for cert #${cert.id} (${cert.recipient_name})`);
                PROJECT_DATA.certificates[currentCertificateIndex].is_edited = response.is_edited;
                
                // 🔧 BUG FIX: Release save lock and process queue
                isSaving = false;
                processQueuedActions();
            } else {
                showSaveIndicator('error');
                console.error('Save failed:', response.message);
                
                // 🔧 BUG FIX: Release save lock on error
                isSaving = false;
                processQueuedActions();
            }
        },
        error: function(xhr) {
            showSaveIndicator('error');
            console.error('Save error:', xhr);
            
            // 🔧 BUG FIX: Release save lock on error
            isSaving = false;
            processQueuedActions();
        }
    });
}

function showSaveIndicator(state) {
    const $indicator = $('#save-indicator');
    $indicator.removeClass('saving saved error').addClass(state).show();
    
    if (state === 'saving') {
        $indicator.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    } else if (state === 'saved') {
        $indicator.html('<i class="fas fa-check-circle"></i> Saved');
        setTimeout(hideSaveIndicator, 3000);
    } else if (state === 'error') {
        $indicator.html('<i class="fas fa-exclamation-circle"></i> Error');
    }
}

function hideSaveIndicator() {
    $('#save-indicator').fadeOut(300, function() {
        $(this).removeClass('saving saved error').hide();
    });
}

function updateEditedCount() {
    const editedCount = PROJECT_DATA.certificates.filter(c => c.is_edited).length;
    $('#edited-count').text(editedCount);
}

function bindEvents() {
    // Navigation buttons
    $('#prev-certificate').click(function() {
        if (hasUnsavedChanges) {
            saveCertificate();
        }
        loadCertificate(currentCertificateIndex - 1);
    });
    
    $('#next-certificate').click(function() {
        if (hasUnsavedChanges) {
            saveCertificate();
        }
        loadCertificate(currentCertificateIndex + 1);
    });
    
    // Save button
    $('#save-current').click(function() {
        saveCertificate();
    });
    
    // Thumbnail clicks
    $('.thumbnail-item').click(function() {
        const pageNumber = $(this).data('page-number');
        if (hasUnsavedChanges) {
            saveCertificate();
        }
        loadCertificate(pageNumber - 1);
    });
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Check if user is editing text
        const activeObject = canvas.getActiveObject();
        const isEditingText = activeObject && 
            (activeObject.type === 'textbox' || activeObject.type === 'i-text') && 
            activeObject.isEditing;
        
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveCertificate();
        }
        
        // Ctrl+Z to undo (when not editing text)
        if (e.ctrlKey && e.key === 'z' && !e.shiftKey && !isEditingText) {
            e.preventDefault();
            $('#undo-btn').click();
        }
        
        // Ctrl+Y or Ctrl+Shift+Z to redo (when not editing text)
        if (e.ctrlKey && (e.key === 'y' || (e.shiftKey && e.key === 'z')) && !isEditingText) {
            e.preventDefault();
            $('#redo-btn').click();
        }
        
        // Delete key to remove selected object (when not editing text)
        if (e.key === 'Delete' && !isEditingText && activeObject) {
            e.preventDefault();
            $('#delete-element-btn').click();
        }
        
        // Arrow keys: nudging if object selected, navigation if not
        if (!isEditingText) {
            if (activeObject) {
                // Nudging selected object
                let dx = 0, dy = 0;
                const step = e.shiftKey ? 10 : 1;
                if (e.key === 'ArrowLeft') dx = -step;
                if (e.key === 'ArrowRight') dx = step;
                if (e.key === 'ArrowUp') dy = -step;
                if (e.key === 'ArrowDown') dy = step;
                if (dx !== 0 || dy !== 0) {
                    e.preventDefault();
                    activeObject.left += dx;
                    activeObject.top += dy;
                    activeObject.setCoords();
                    canvas.renderAll();
                    hasUnsavedChanges = true;
                    triggerAutoSave && triggerAutoSave();
                }
            } else {
                // Navigation if no selection
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    $('#prev-certificate').click();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    $('#next-certificate').click();
                }
            }
        }
    });
    
    // Canvas control buttons
    $('#add-text-btn').click(function() {
        const text = new fabric.IText('New Text', {
            left: canvas.width / 2 - 50,
            top: canvas.height / 2,
            fontFamily: 'Arial',
            fontSize: 24,
            fill: '#000000'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        canvas.renderAll();
        hasUnsavedChanges = true;
    });
    
    $('#undo-btn').click(function() {
        if (undoRedoManager) {
            undoRedoManager.undo();
            hasUnsavedChanges = true;
        }
    });
    
    $('#redo-btn').click(function() {
        if (undoRedoManager) {
            undoRedoManager.redo();
            hasUnsavedChanges = true;
        }
    });
    
    $('#delete-element-btn').click(function() {
        const activeObject = canvas.getActiveObject();
        if (activeObject) {
            canvas.remove(activeObject);
            canvas.renderAll();
            hasUnsavedChanges = true;
        }
    });
    
    // Update delete button state based on selection
    canvas.on('selection:created', function() {
        $('#delete-element-btn').prop('disabled', false);
    });
    
    canvas.on('selection:updated', function() {
        $('#delete-element-btn').prop('disabled', false);
    });
    
    canvas.on('selection:cleared', function() {
        $('#delete-element-btn').prop('disabled', true);
    });
    
    // ========================================
    // SMART ALIGNMENT GUIDES
    // ========================================
    canvas.on('object:moving', function(e) {
        if (!window.smartGuides || !window.smartGuides.enabled) return;
        
        // Check if temporarily disabled by Ctrl key
        if (window.smartGuides.temporarilyDisabled || window.isCtrlPressed) {
            window.smartGuides.guides = [];
            return;
        }
        
        const target = e.target;
        if (!target) return;
        if (target.selectable === false || target.visible === false) return;

        const snap = computeSnapAdjustment(canvas, target);
        window.smartGuides.guides = [];

        if (snap && (snap.dx || snap.dy)) {
            if (snap.dx) target.left += snap.dx;
            if (snap.dy) target.top += snap.dy;
            target.setCoords();
        }

        if (snap && snap.vLineX !== null && snap.vLineX !== undefined) {
            window.smartGuides.guides.push({ type: 'v', x: snap.vLineX });
        }
        if (snap && snap.hLineY !== null && snap.hLineY !== undefined) {
            window.smartGuides.guides.push({ type: 'h', y: snap.hLineY });
        }
    });

    // Clear guide overlay BEFORE Fabric draws selection/controls
    canvas.on('before:render', function() {
        const cfg = window.smartGuides;
        if (!cfg || !cfg.enabled) return;
        const ctxTop = canvas.contextTop;
        canvas.clearContext(ctxTop);
    });

    // Draw guides on top context
    canvas.on('after:render', function() {
        const cfg = window.smartGuides;
        if (!cfg || !cfg.enabled) return;
        const ctxTop = canvas.contextTop;
        if (!cfg.guides || cfg.guides.length === 0) return;
        ctxTop.save();
        ctxTop.strokeStyle = 'rgba(0, 123, 255, 0.9)';
        ctxTop.lineWidth = 1;
        ctxTop.setLineDash([6, 4]);
        cfg.guides.forEach(g => {
            if (g.type === 'v') {
                ctxTop.beginPath();
                ctxTop.moveTo(g.x, 0);
                ctxTop.lineTo(g.x, canvas.height);
                ctxTop.stroke();
            } else if (g.type === 'h') {
                ctxTop.beginPath();
                ctxTop.moveTo(0, g.y);
                ctxTop.lineTo(canvas.width, g.y);
                ctxTop.stroke();
            }
        });
        ctxTop.restore();
    });

    // Clear guides when done
    canvas.on('mouse:up', function() {
        if (window.smartGuides) {
            window.smartGuides.guides = [];
            canvas.renderAll();
        }
    });
    
    // ========================================
    // KEYBOARD HANDLERS - Ctrl key for fine control
    // ========================================
    $(document).on('keydown', function(e) {
        // Track Ctrl key for temporary snap disable
        if (e.ctrlKey || e.metaKey) {
            if (!window.isCtrlPressed) {
                window.isCtrlPressed = true;
                const activeObj = canvas.getActiveObject();
                if (activeObj && activeObj.selectable !== false) {
                    canvas.defaultCursor = 'move';
                    canvas.hoverCursor = 'move';
                }
            }
        }
    });

    $(document).on('keyup', function(e) {
        // Reset Ctrl key state
        if (!e.ctrlKey && !e.metaKey) {
            if (window.isCtrlPressed) {
                window.isCtrlPressed = false;
                canvas.defaultCursor = 'default';
                canvas.hoverCursor = 'move';
            }
        }
    });
    
    // Finalize project
    $('#finalize-project').click(function() {
        if (!confirm('Are you sure you want to finalize this project?\n\nThis will generate PDFs for all certificates and you won\'t be able to edit them anymore.')) {
            return;
        }
        
        if (hasUnsavedChanges) {
            saveCertificate();
        }
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Finalizing...');
        
        $.ajax({
            url: `/projects/{{ $project->id }}/finalize`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Start progress polling
                    startProgressPolling();
                    
                } else {
                    toastr.error(response.message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to start finalization');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Progress polling
    let progressInterval = null;
    
    function startProgressPolling() {
        // Show progress modal
        showProgressModal();
        
        // Poll every 2 seconds
        progressInterval = setInterval(function() {
            $.ajax({
                url: `/projects/{{ $project->id }}/progress`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateProgressUI(response);
                        
                        // Stop polling if completed
                        if (response.project_status === 'completed') {
                            clearInterval(progressInterval);
                            setTimeout(function() {
                                window.location.href = '{{ route("projects.index") }}';
                            }, 2000);
                        }
                    }
                },
                error: function() {
                    console.error('Failed to fetch progress');
                }
            });
        }, 2000);
    }
    
    function showProgressModal() {
        // Create modal if not exists
        if ($('#progress-modal').length === 0) {
            $('body').append(`
                <div class="modal fade" id="progress-modal" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-cog fa-spin"></i> Generating PDFs...
                                </h5>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <div id="progress-message" class="mb-2">Initializing...</div>
                                    <div id="progress-percentage" class="h3 text-primary">0%</div>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                         role="progressbar" style="width: 0%">
                                        <span id="progress-text">0 / 0</span>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <small id="progress-step" class="text-muted">Please wait...</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        $('#progress-modal').modal('show');
    }
    
    function updateProgressUI(data) {
        const { progress, percentage, project_status } = data;
        
        // Update progress bar
        $('#progress-bar').css('width', percentage + '%');
        $('#progress-text').text(`${progress.completed} / ${progress.total}`);
        $('#progress-percentage').text(percentage + '%');
        $('#progress-step').text(progress.current_step || 'Processing...');
        
        // Update message
        if (project_status === 'completed') {
            $('#progress-message').text('All PDFs generated successfully!');
            $('#progress-bar').removeClass('progress-bar-animated')
                             .removeClass('bg-primary')
                             .addClass('bg-success');
            $('.modal-title').html('<i class="fas fa-check-circle"></i> Completed!');
            $('.modal-header').removeClass('bg-primary').addClass('bg-success');
        } else {
            $('#progress-message').text(`Generating certificate PDFs...`);
        }
    }
} // End of bindEvents()
</script>
@endpush
