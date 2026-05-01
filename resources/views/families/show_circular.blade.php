@extends('layouts.app')

@section('title', $family->name . ' - Shajara')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('families.index') }}">Families</a></li>
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
            <h2 class="mb-0">{{ $family->name }} Family Tree</h2>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#shareModal">
                <i class="fas fa-share-alt me-1"></i> Share
            </button>
            @include('families.partials.layout_switch')
        </div>
    </div>
    @if($roots->isEmpty())
        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addPersonModal">
            <i class="fas fa-plus me-1"></i> Add Root Ancestor
        </button>
    @endif
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="tree-container" id="treeContainer" style="min-height: 800px; overflow: hidden; position: relative; background: #fafafa;">
        <div id="treeCanvas" style="position: absolute; width: 10000px; height: 10000px; transform-origin: 0 0;">
            <svg id="treeLines" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; overflow: visible;"></svg>
            <div id="treeNodes" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2;"></div>
        </div>
    </div>
</div>

<style>
    .circular-node {
        position: absolute;
        width: 140px;
        transform: translate(-50%, -50%);
        background: white;
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 10;
        font-size: 0.8rem;
    }
    .circular-node.female { border-top: 3px solid #e83e8c; }
    .circular-node.male { border-top: 3px solid #0d6efd; }
    .circular-node img { width: 40px; height: 40px; }
    .spouse-container {
        position: absolute;
        width: 140px;
        background: white;
        border: 1px dashed #e83e8c;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        transform: translate(-50%, -50%);
        z-index: 9;
        font-size: 0.8rem;
    }
    .control-btns .btn { padding: 0.1rem 0.3rem; font-size: 0.7rem; margin: 1px; }
</style>

<div class="zoom-controls">
    <button type="button" class="zoom-btn" onclick="zoomIn()" title="Zoom In"><i class="fas fa-plus"></i></button>
    <button type="button" class="zoom-btn" onclick="zoomOut()" title="Zoom Out"><i class="fas fa-minus"></i></button>
    <button type="button" class="zoom-btn" onclick="resetZoom()" title="Reset Zoom"><i class="fas fa-sync-alt"></i></button>
</div>

<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('people.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="family_id" value="{{ $family->id }}">
            <input type="hidden" name="parent_id" id="modal_parent_id" value="">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_title">Add Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required value="{{ explode(' ', $family->name)[0] }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Birth Year</label>
                            <input type="number" name="birth_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Death Year (nullable)</label>
                            <input type="number" name="death_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biography/Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Member</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Person Modal (Will be handled via JS to populate) -->
<!-- ... Same as Add but for update ... Or we can reuse ... For simplicity I'll create a separate one or reuse JS -->

<!-- Edit Person Modal -->
<div class="modal fade" id="editPersonModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editPersonForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="edit_gender" class="form-select" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Birth Year</label>
                            <input type="number" name="birth_year" id="edit_birth_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Death Year (nullable)</label>
                            <input type="number" name="death_year" id="edit_death_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo (Leave empty to keep current)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biography/Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Member</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('families.share', $family) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Family Tree</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Enter the phone number of the person you want to share this shajara with. They must be a registered user.</p>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" required placeholder="e.g. 998901234567">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Share Access</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Spouse Modal -->
<div class="modal fade" id="addSpouseModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addSpouseForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="spouse_modal_title">Add Spouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Выберите супруга(у) из существующих членов семьи. Поиск отфильтрован по противоположному полу и возрасту (младше выбранного лица).</p>
                    <div class="mb-3">
                        <label class="form-label">Available Candidates</label>
                        <select name="spouse_id" id="spouse_select" class="form-select" required>
                            <option value="">Searching...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-heart"></i> Link Spouse</button>
                </div>
            </div>
        </form>
    </div>
</div>

@php
    function buildTreeData($person) {
        $directChildren = $person->children;
        $spouseChildren = collect();
        foreach($person->spouses as $spouse) {
            $spouseChildren = $spouseChildren->merge($spouse->children);
        }
        $allChildren = $directChildren->merge($spouseChildren)->unique('id');
        
        $childrenData = [];
        foreach($allChildren as $child) {
            $childrenData[] = buildTreeData($child);
        }
        
        $spousesData = [];
        foreach($person->spouses as $spouse) {
            $spousesData[] = [
                'id' => $spouse->id,
                'name' => $spouse->full_name,
                'gender' => $spouse->gender,
                'photo' => $spouse->photo ? Storage::url($spouse->photo) : null,
                'birth' => $spouse->birth_year,
                'death' => $spouse->death_year,
            ];
        }

        return [
            'id' => $person->id,
            'name' => $person->full_name,
            'gender' => $person->gender,
            'photo' => $person->photo ? Storage::url($person->photo) : null,
            'birth' => $person->birth_year,
            'death' => $person->death_year,
            'spouses' => $spousesData,
            'children' => $childrenData,
            'raw_person' => $person // For buttons
        ];
    }

    $treeRoots = [];
    foreach($roots as $root) {
        $treeRoots[] = buildTreeData($root);
    }
@endphp

@endsection

@section('scripts')
<script>
    const treeData = @json($treeRoots);
    const canvasCenter = { x: 5000, y: 5000 };
    const nodesContainer = document.getElementById('treeNodes');
    const svgLayer = document.getElementById('treeLines');

    function drawLine(x1, y1, x2, y2) {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('stroke', '#a0aab5'); // Darker grey for better visibility
        line.setAttribute('stroke-width', '2');
        svgLayer.appendChild(line);
    }

    function createNodeHtml(node, cx, cy) {
        let photoHtml = '';
        if (node.photo) {
            photoHtml = `<img src="${node.photo}" class="rounded-circle mb-1" style="object-fit: cover;">`;
        } else {
            let icon = node.gender === 'male' ? 'user' : 'user-nurse';
            photoHtml = `<i class="fas fa-${icon} fa-2x mb-1 text-secondary"></i>`;
        }
        
        // Build spouses if any
        let spousesHtml = '';
        node.spouses.forEach((sp, i) => {
            let spPhoto = sp.photo ? `<img src="${sp.photo}" class="rounded-circle mb-1" style="object-fit: cover;">` : `<i class="fas fa-${sp.gender === 'male' ? 'user' : 'user-nurse'} fa-2x mb-1 text-muted"></i>`;
            
            // Position spouse slightly offset
            spousesHtml += `
            <div class="spouse-container ${sp.gender}" style="left: ${cx + 150}px; top: ${cy + (i*80)}px;">
                ${spPhoto}
                <div class="fw-bold">${sp.name}</div>
                <div class="text-muted" style="font-size:0.7rem">(${sp.birth || '?'} - ${sp.death || 'Present'})</div>
            </div>`;
            
            // Draw heart line
            drawLine(cx, cy, cx + 150, cy + (i*80));
        });

        const html = `
        ${spousesHtml}
        <div class="circular-node ${node.gender}" style="left: ${cx}px; top: ${cy}px;">
            ${photoHtml}
            <div class="fw-bold">${node.name}</div>
            <div class="text-muted mb-1" style="font-size:0.7rem">(${node.birth || '?'} - ${node.death || 'Present'})</div>
        </div>`;
        return html;
    }

    function calculateLeaves(node) {
        if (!node.children || node.children.length === 0) {
            node.leaves = 1;
            return 1;
        }
        let leaves = 0;
        node.children.forEach(child => {
            leaves += calculateLeaves(child);
        });
        node.leaves = leaves;
        return leaves;
    }

    function renderTreeRadial(nodes, cx, cy, radiusStep, currentRadius, startAngle, endAngle) {
        if (!nodes || nodes.length === 0) return;

        const totalLeaves = nodes.reduce((sum, n) => sum + n.leaves, 0);
        let currentStartAngle = startAngle;

        nodes.forEach((node) => {
            const angleSpan = (node.leaves / totalLeaves) * (endAngle - startAngle);
            const myAngle = currentStartAngle + (angleSpan / 2);
            
            let x = cx;
            let y = cy;
            
            if (currentRadius > 0) {
                const rad = myAngle * Math.PI / 180;
                x = canvasCenter.x + currentRadius * Math.cos(rad);
                y = canvasCenter.y + currentRadius * Math.sin(rad);
            }

            // Draw line from parent to child
            if (currentRadius > 0) {
                drawLine(cx, cy, x, y);
            }

            // Create HTML
            nodesContainer.insertAdjacentHTML('beforeend', createNodeHtml(node, x, y));

            // Recurse for children
            if (node.children && node.children.length > 0) {
                // Determine next radius. If it's a root with multiple items, they stay at 0 but that's weird.
                // Usually there is 1 root (currentRadius 0) which spans 360.
                let nextRadius = currentRadius === 0 ? radiusStep : currentRadius + radiusStep;
                let nextStart = currentRadius === 0 ? 0 : currentStartAngle;
                let nextEnd = currentRadius === 0 ? 360 : currentStartAngle + angleSpan;
                
                renderTreeRadial(node.children, x, y, radiusStep, nextRadius, nextStart, nextEnd);
            }

            currentStartAngle += angleSpan;
        });
    }

    // Init Render
    if(treeData.length > 0) {
        // Prepare weights
        treeData.forEach(root => calculateLeaves(root));
        // Draw
        renderTreeRadial(treeData, canvasCenter.x, canvasCenter.y, 400, 0, 0, 360);
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
        resetZoom();
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
            scale = 0.2; // Show more of the tree on small mobile screens
        } else {
            scale = 0.8;
        }

        translateX = (containerWidth / 2) - (5000 * scale);
        translateY = (containerHeight / 2) - (5000 * scale);
        
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
