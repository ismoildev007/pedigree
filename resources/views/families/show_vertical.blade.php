@extends('layouts.app')

@section('title', $family->name . ' - ' . __('Shajara'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('families.index') }}">{{ __('Families') }}</a></li>
                @if($focusedPerson)
                    <li class="breadcrumb-item"><a href="{{ route('families.show', $family) }}">{{ $family->name }}</a></li>
                    @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item"><a href="{{ route('families.show', ['family' => $family, 'root_id' => $crumb['id']]) }}">{{ $crumb['name'] }}</a></li>
                    @endforeach
                    <li class="breadcrumb-item active">{{ $focusedPerson->full_name }}</li>
                @else
                    <li class="breadcrumb-item active">{{ $family->name }}</li>
                @endif
            </ol>
        </nav>
        <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
            <h2 class="mb-0">{{ $family->name }} {{ __('Family Tree') }}</h2>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#shareModal">
                <i class="fas fa-share-alt me-1"></i> {{ __('Share') }}
            </button>
            @include('families.partials.layout_switch')
        </div>
    </div>
    @if($roots->isEmpty())
        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addPersonModal">
            <i class="fas fa-plus me-1"></i> {{ __('Add Root Ancestor') }}
        </button>
    @endif
</div>

<style>
    .tree-vertical {
        display: inline-block;
        white-space: nowrap;
        transform-origin: 0 0;
        transition: transform 0.1s ease-out;
        user-select: none;
        padding: 50px;
    }
    .tree-vertical ul {
        display: flex;
        flex-direction: column; /* siblings stack vertically */
        list-style-type: none;
        padding-left: 50px; /* Space between parent and spine */
        margin-left: 0;
        position: relative;
    }
    .tree-vertical li {
        display: flex;
        flex-direction: row; /* Parent card on left, children ul on right */
        align-items: center; /* Center horizontally against the parent card */
        position: relative;
        padding-left: 50px; /* Space between spine and child card */
        margin: 10px 0;
    }
    .tree-vertical > ul {
        padding-left: 0;
    }
    .tree-vertical > ul > li {
        padding-left: 0;
    }
    
    /* Spine connectors */
    .tree-vertical li::before, .tree-vertical li::after {
        content: '';
        position: absolute;
        left: 0;
        border-left: 2px solid #ccc;
    }
    .tree-vertical li::before {
        top: 0;
        height: 50%;
        border-bottom: 2px solid #ccc; /* Horizontal line branching to child */
        width: 50px;
    }
    .tree-vertical li::after {
        top: 50%;
        bottom: 0px; 
    }
    
    /* Hide specific spines to form corners */
    .tree-vertical li:first-child::before {
        border-left: 0; /* No spine going up */
    }
    .tree-vertical li:last-child::after {
        display: none; /* No spine going down */
    }
    
    /* Connector entering the ul from the parent */
    .tree-vertical ul::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        width: 50px;
        border-top: 2px solid #ccc;
    }
    .tree-vertical > ul::before {
        display: none; /* No inbound connection for root */
    }
</style>

<div class="card shadow-sm border-0 mb-5">
    <div class="tree-container" id="treeContainer" style="min-height: 600px;">
        <div class="tree-vertical" id="treeCanvas">
            @if($roots->isNotEmpty())
                <ul>
                    @foreach($roots as $person)
                        @include('families.partials.tree_node_vertical', ['person' => $person])
                    @endforeach
                </ul>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-users-slash fa-4x mb-3"></i>
                    <h4>{{ __('No members in this family yet.') }}</h4>
                </div>
            @endif
        </div>

        <div class="zoom-controls">
            <button type="button" class="zoom-btn" onclick="zoomIn()" title="{{ __('Zoom In') }}"><i class="fas fa-plus"></i></button>
            <button type="button" class="zoom-btn" onclick="zoomOut()" title="{{ __('Zoom Out') }}"><i class="fas fa-minus"></i></button>
            <button type="button" class="zoom-btn" onclick="resetZoom()" title="{{ __('Reset Zoom') }}"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>
</div>

@include('families.partials.modals', ['family' => $family])

@endsection

@section('scripts')
<script>
    function setParent(id, name) {
        document.getElementById('modal_parent_id').value = id;
        document.getElementById('modal_title').innerText = "{{ __('Add Child for') }} " + name;
    }

    let spouseSelect;
    function setSpouseTarget(id, name) {
        const form = document.getElementById('addSpouseForm');
        form.action = `/people/${id}/add-spouse`;
        document.getElementById('spouse_modal_title').innerText = "{{ __('Add Spouse for') }} " + name;
        
        if (!spouseSelect) {
            spouseSelect = new TomSelect('#spouse_select', {
                valueField: 'id',
                labelField: 'name',
                searchField: 'name',
                placeholder: "{{ __('Select Spouse') }}",
                maxItems: 1
            });
        }

        spouseSelect.clear();
        spouseSelect.clearOptions();
        spouseSelect.addOptions([{id: '', name: "{{ __('Searching...') }}"}]);

        fetch(`/people/${id}/potential-spouses`)
            .then(res => res.json())
            .then(data => {
                spouseSelect.clearOptions();
                if (data.length === 0) {
                    spouseSelect.addOptions([{id: '', name: "{{ __('No suitable candidates found') }}"}]);
                } else {
                    const options = data.map(p => ({
                        id: p.id,
                        name: `${p.first_name} ${p.last_name} (${p.birth_year ?? '?'})`
                    }));
                    spouseSelect.addOptions(options);
                }
            });
    }

    function setEditData(button) {
        const person = JSON.parse(button.getAttribute('data-person'));
        const form = document.getElementById('editPersonForm');
        form.action = '/people/' + person.id;
        
        document.getElementById('edit_first_name').value = person.first_name;
        document.getElementById('edit_last_name').value = person.last_name;
        document.getElementById('edit_gender').value = person.gender;
        document.getElementById('edit_birth_year').value = person.birth_year ? person.birth_year : '';
        document.getElementById('edit_death_year').value = person.death_year ? person.death_year : '';
        document.getElementById('edit_description').value = person.description ? person.description : '';
    }

    // Zoom and Pan Logic
    const container = document.getElementById('treeContainer');
    const canvas = document.getElementById('treeCanvas');
    
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX, startY;

    function updateTransform() {
        if (scale < 0.1) scale = 0.1;
        if (scale > 3) scale = 3;
        canvas.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    }

    // Initial centering and scale
    window.addEventListener('load', () => {
        // Defer so browser finishes layout of inline-block tree
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                resetZoom();
            });
        });
    });

    container.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return; // Only left click
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => {
        isDragging = false;
    });

    // Disable wheel zoom as per user request (only allow +/- buttons)
    /*
    container.addEventListener('wheel', (e) => {
        e.preventDefault();
        // ...
    }, { passive: false });
    */

    function zoomIn() {
        const containerWidth = container.offsetWidth;
        const containerHeight = container.offsetHeight;
        applyZoom(0.2, containerWidth / 2, containerHeight / 2);
    }

    function zoomOut() {
        const containerWidth = container.offsetWidth;
        const containerHeight = container.offsetHeight;
        applyZoom(-0.2, containerWidth / 2, containerHeight / 2);
    }

    function applyZoom(delta, focalX, focalY) {
        const oldScale = scale;
        scale += delta;
        if (scale < 0.1) scale = 0.1;
        if (scale > 3) scale = 3;
        
        const zoomRatio = scale / oldScale;
        
        // Focal zoom formula: x2 = focalX - (focalX - x1) * (s2 / s1)
        translateX = focalX - (focalX - translateX) * zoomRatio;
        translateY = focalY - (focalY - translateY) * zoomRatio;
        
        updateTransform();
    }

    function resetZoom() {
        const containerWidth = container.offsetWidth;
        const containerHeight = container.offsetHeight;
        
        if (window.innerWidth < 768) {
            scale = 0.85;
        } else {
            scale = 1;
        }
        
        // Find the first root node (first li > .person-card or the first li itself)
        const firstRootLi = canvas.querySelector('ul > li');
        
        if (firstRootLi) {
            // Get root node's position relative to the canvas
            const rootRect = firstRootLi.getBoundingClientRect();
            const canvasRect = canvas.getBoundingClientRect();
            
            // Root node's offset inside the canvas (unscaled)
            const rootOffsetX = (rootRect.left - canvasRect.left) / scale;
            const rootOffsetY = (rootRect.top - canvasRect.top) / scale;
            const rootHeight = rootRect.height / scale;
            
            // Center the root node in the container
            translateX = (containerWidth / 2) - ((rootOffsetX + 80) * scale); // 80 = approx half card width
            translateY = (containerHeight / 2) - ((rootOffsetY + rootHeight / 2) * scale);
        } else {
            translateX = 20;
            translateY = 20;
        }
        
        updateTransform();
    }

    // Touch Support for Mobile
    let lastTouchX, lastTouchY;
    let initialPinchDistance = 0;
    let initialPinchCenter = { x: 0, y: 0 };

    container.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            isDragging = true;
            lastTouchX = e.touches[0].clientX - translateX;
            lastTouchY = e.touches[0].clientY - translateY;
        } else if (e.touches.length === 2) {
            isDragging = false;
            initialPinchDistance = getDistance(e.touches[0], e.touches[1]);
            const rect = container.getBoundingClientRect();
            initialPinchCenter = {
                x: ((e.touches[0].clientX + e.touches[1].clientX) / 2) - rect.left,
                y: ((e.touches[0].clientY + e.touches[1].clientY) / 2) - rect.top
            };
        }
    }, { passive: false });

    container.addEventListener('touchmove', (e) => {
        e.preventDefault();
        if (e.touches.length === 1 && isDragging) {
            translateX = e.touches[0].clientX - lastTouchX;
            translateY = e.touches[0].clientY - lastTouchY;
            updateTransform();
        } else if (e.touches.length === 2) {
            const currentDistance = getDistance(e.touches[0], e.touches[1]);
            const zoomFactor = currentDistance / initialPinchDistance;
            
            // Dampen zoom sensitivity specifically for mobile touch
            const smoothedFactor = 1 + (zoomFactor - 1) * 0.15;
            
            if (Math.abs(smoothedFactor - 1) > 0.005) {
                const oldScale = scale;
                scale *= smoothedFactor;
                if (scale < 0.1) scale = 0.1;
                if (scale > 3) scale = 3;

                const rect = container.getBoundingClientRect();
                const focalX = ((e.touches[0].clientX + e.touches[1].clientX) / 2) - rect.left;
                const focalY = ((e.touches[0].clientY + e.touches[1].clientY) / 2) - rect.top;

                const zoomRatio = scale / oldScale;
                translateX = focalX - (focalX - translateX) * zoomRatio;
                translateY = focalY - (focalY - translateY) * zoomRatio;

                initialPinchDistance = currentDistance;
                updateTransform();
            }
        }
    }, { passive: false });

    container.addEventListener('touchend', () => {
        isDragging = false;
        initialPinchDistance = 0;
    });

    function getDistance(t1, t2) {
        return Math.sqrt(Math.pow(t1.clientX - t2.clientX, 2) + Math.pow(t1.clientY - t2.clientY, 2));
    }
</script>
@endsection
