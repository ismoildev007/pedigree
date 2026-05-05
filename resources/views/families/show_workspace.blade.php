@extends('layouts.app')

@section('title', $family->name . ' - ' . __('Workspace'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('families.index') }}">{{ __('Families') }}</a></li>
                @if($focusedPerson)
                    <li class="breadcrumb-item"><a href="{{ route('families.showWorkspace', $family) }}">{{ $family->name }}</a></li>
                    @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item"><a href="{{ route('families.showWorkspace', ['family' => $family, 'root_id' => $crumb['id']]) }}">{{ $crumb['name'] }}</a></li>
                    @endforeach
                    <li class="breadcrumb-item active">{{ $focusedPerson->full_name }}</li>
                @else
                    <li class="breadcrumb-item active">{{ $family->name }} {{ __('Workspace') }}</li>
                @endif
            </ol>
        </nav>
        <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
            <h2 class="mb-0">{{ $family->name }}</h2>
            @include('families.partials.layout_switch')
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="autoLayout()">
                <i class="fas fa-magic me-1"></i> {{ __('Auto Layout') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="resetZoom()">
                <i class="fas fa-compress-arrows-alt me-1"></i> {{ __('Center View') }}
            </button>
        </div>
    </div>
</div>

<style>
    .workspace-container {
        width: 100%;
        height: 70vh;
        background: #f0f2f5;
        background-image: radial-gradient(#d1d5db 1px, transparent 1px);
        background-size: 20px 20px;
        position: relative;
        overflow: hidden;
        border: 2px solid #ddd;
        border-radius: 12px;
        cursor: grab;
    }
    .workspace-container:active { cursor: grabbing; }

    #workspaceCanvas {
        position: absolute;
        width: 10000px;
        height: 10000px;
        transform-origin: 0 0;
    }

    .person-card {
        position: absolute;
        width: 220px;
        background: white;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        cursor: move;
        touch-action: none;
        user-select: none;
        z-index: 10;
        border-top: 5px solid #ccc;
        transition: box-shadow 0.2s;
    }
    .person-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .person-card.male { border-top-color: #0d6efd; }
    .person-card.female { border-top-color: #d63384; }

    .card-photo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
        border: 2px solid #f8f9fa;
    }
    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #6c757d;
    }

    #connectionsLayer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 5;
    }

    .connector-line {
        fill: none;
        stroke: #94a3b8;
        stroke-width: 2;
        stroke-dasharray: 4;
        animation: dash 20s linear infinite;
    }
    @keyframes dash {
        to { stroke-dashoffset: -1000; }
    }

    .spouse-line {
        stroke: #f43f5e;
        stroke-width: 3;
        stroke-dasharray: 0;
    }

    .controls-overlay {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(255,255,255,0.9);
        padding: 10px;
        border-radius: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        gap: 10px;
        z-index: 100;
        backdrop-filter: blur(5px);
    }
</style>

<div class="workspace-container shadow-inner" id="container">
    <div id="workspaceCanvas">
        <svg id="connectionsLayer">
            <defs>
                <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                    <polygon points="0 0, 10 3.5, 0 7" fill="#94a3b8" />
                </marker>
            </defs>
        </svg>
        <div id="cardsLayer">
            @foreach($allPeople as $person)
                <div class="person-card {{ $person->gender }}" 
                     id="person-{{ $person->id }}"
                     data-id="{{ $person->id }}"
                     data-parent-id="{{ $person->parent_id }}"
                     data-spouses='@json($person->spouses->pluck("id"))'
                     style="left: {{ $person->workspace_x }}px; top: {{ $person->workspace_y }}px;">
                    
                    <div class="d-flex align-items-center mb-2">
                        @if($person->photo)
                            <img src="{{ Storage::url($person->photo) }}" class="card-photo">
                        @else
                            <div class="card-icon">
                                <i class="fas fa-{{ $person->gender == 'male' ? 'user' : 'user-nurse' }} fa-2x"></i>
                            </div>
                        @endif
                        <div class="overflow-hidden">
                            <h6 class="mb-0 text-truncate fw-bold">{{ $person->full_name }}</h6>
                            <small class="text-muted">({{ $person->birth_year ?? '?' }} - {{ $person->death_year ?? __('Pres.') }})</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between gap-1 mt-2">
                        <button type="button" class="btn btn-sm btn-light border flex-grow-1" data-bs-toggle="modal" data-bs-target="#editPersonModal" 
                                data-person='@json($person)' onclick="setEditData(this)" title="{{ __('Edit') }}">
                            <i class="fas fa-edit text-primary"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light border flex-grow-1" data-bs-toggle="modal" data-bs-target="#addPersonModal" 
                                onclick="setParent({{ $person->id }}, '{{ addslashes($person->full_name) }}')" title="{{ __('Add Child') }}">
                            <i class="fas fa-plus text-success"></i>
                        </button>
                        @if($person->gender == 'male')
                        <button type="button" class="btn btn-sm btn-light border flex-grow-1" data-bs-toggle="modal" data-bs-target="#addSpouseModal" 
                                onclick="setSpouseTarget({{ $person->id }}, '{{ addslashes($person->full_name) }}')" title="{{ __('Add Spouse') }}">
                            <i class="fas fa-heart text-danger"></i>
                        </button>
                        @endif
                        <form action="{{ route('people.destroy', $person) }}" method="POST" class="d-inline flex-grow-1" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border w-100" title="{{ __('Delete') }}">
                                <i class="fas fa-trash text-muted"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Zoom Controls Overlay -->
    <div class="controls-overlay">
        <button class="btn btn-secondary btn-sm rounded-circle" onclick="zoomOut()" style="width:36px; height:36px;"><i class="fas fa-minus"></i></button>
        <span class="align-self-center fw-bold" id="zoomLevel">100%</span>
        <button class="btn btn-secondary btn-sm rounded-circle" onclick="zoomIn()" style="width:36px; height:36px;"><i class="fas fa-plus"></i></button>
    </div>
</div>

<!-- Modals (Reused from other views) -->
@include('families.partials.modals', ['family' => $family])

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script>
    const container = document.getElementById('container');
    const canvas = document.getElementById('workspaceCanvas');
    const connectionsLayer = document.getElementById('connectionsLayer');
    const zoomText = document.getElementById('zoomLevel');
    
    let scale = 1;
    let translateX = 500;
    let translateY = 500;
    let isPanning = false;
    let startX, startY;

    // --- Panning & Zoom Logic ---
    container.addEventListener('mousedown', (e) => {
        if (e.target.id === 'container' || e.target.id === 'workspaceCanvas' || e.target.id === 'connectionsLayer') {
            isPanning = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        }
    });

    window.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => isPanning = false);

    container.addEventListener('wheel', (e) => {
        e.preventDefault();
        const factor = e.deltaY > 0 ? 0.9 : 1.1;
        applyZoom(factor, e.clientX, e.clientY);
    });

    function applyZoom(factor, focalX, focalY) {
        const newScale = scale * factor;
        if (newScale < 0.1 || newScale > 3) return;

        const rect = container.getBoundingClientRect();
        const localX = focalX - rect.left;
        const localY = focalY - rect.top;

        translateX = localX - (localX - translateX) * factor;
        translateY = localY - (localY - translateY) * factor;
        
        scale = newScale;
        updateTransform();
    }

    function zoomIn() { applyZoom(1.2, container.offsetWidth/2, container.offsetHeight/2); }
    function zoomOut() { applyZoom(0.8, container.offsetWidth/2, container.offsetHeight/2); }
    
    function resetZoom() {
        scale = 1;
        translateX = 500;
        translateY = 500;
        updateTransform();
    }

    function updateTransform() {
        canvas.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        zoomText.innerText = Math.round(scale * 100) + '%';
        drawConnections();
    }

    // --- Drag & Drop Cards ---
    interact('.person-card').draggable({
        listeners: {
            move (event) {
                const target = event.target;
                const x = (parseFloat(target.style.left) || 0) + event.dx / scale;
                const y = (parseFloat(target.style.top) || 0) + event.dy / scale;

                target.style.left = x + 'px';
                target.style.top = y + 'px';
                
                drawConnections();
            },
            end (event) {
                const target = event.target;
                savePosition(target.getAttribute('data-id'), parseInt(target.style.left), parseInt(target.style.top));
            }
        }
    });

    const debouncedSaves = {};
    function savePosition(personId, x, y) {
        if (debouncedSaves[personId]) clearTimeout(debouncedSaves[personId]);
        
        debouncedSaves[personId] = setTimeout(() => {
            fetch(`/people/${personId}/position`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ x, y })
            });
        }, 500);
    }

    // --- Connections (SVG) ---
    function drawConnections() {
        connectionsLayer.innerHTML = '';
        const cards = document.querySelectorAll('.person-card');
        const cardMap = {};
        cards.forEach(c => cardMap[c.getAttribute('data-id')] = c);

        cards.forEach(card => {
            const id = card.getAttribute('data-id');
            const parentId = card.getAttribute('data-parent-id');
            const spouses = JSON.parse(card.getAttribute('data-spouses') || '[]');

            // Draw Parent Line
            if (parentId && cardMap[parentId]) {
                drawLineBetween(cardMap[parentId], card, 'connector-line');
            }

            // Draw Spouse Lines
            spouses.forEach(sid => {
                if (cardMap[sid]) {
                    drawLineBetween(card, cardMap[sid], 'spouse-line');
                }
            });
        });
    }

    function drawLineBetween(el1, el2, className) {
        const x1 = parseInt(el1.style.left) + 110;
        const y1 = parseInt(el1.style.top) + 40;
        const x2 = parseInt(el2.style.left) + 110;
        const y2 = parseInt(el2.style.top) + 40;

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const d = `M ${x1} ${y1} C ${x1} ${y1 + (y2-y1)/2}, ${x2} ${y1 + (y2-y1)/2}, ${x2} ${y2}`;
        path.setAttribute('d', d);
        path.setAttribute('class', className);
        if (className === 'connector-line') path.setAttribute('marker-end', 'url(#arrowhead)');
        connectionsLayer.appendChild(path);
    }

    // Initial load
    window.addEventListener('load', () => {
        updateTransform();
        drawConnections();

        // If everyone is at 0,0, trigger autoLayout without confirmation on first load
        const cards = document.querySelectorAll('.person-card');
        const atZero = Array.from(cards).every(c => parseInt(c.style.left) === 0 && parseInt(c.style.top) === 0);
        if (atZero && cards.length > 0) {
            autoLayoutInternal(true);
        }
    });

    function autoLayout() {
        if (!confirm("{{ __('Apply automatic radial layout? This will override your custom positions.') }}")) return;
        autoLayoutInternal();
    }

    function autoLayoutInternal(isInitial = false) {
        const cards = document.querySelectorAll('.person-card');
        const cardMap = {};
        const peopleByParent = {};
        let roots = [];

        cards.forEach(card => {
            const id = card.getAttribute('data-id');
            const parentId = card.getAttribute('data-parent-id');
            cardMap[id] = card;
            if (!parentId) {
                roots.push(id);
            } else {
                if (!peopleByParent[parentId]) peopleByParent[parentId] = [];
                peopleByParent[parentId].push(id);
            }
        });

        // Start drawing from the center of the canvas area
        const centerX = 5000;
        const centerY = 5000;
        const levelRadius = 350; // Distance between generations

        // Position roots in a circle if multiple, or at center if one
        if (roots.length === 1) {
            positionNode(roots[0], centerX, centerY, 0, 360, 0);
        } else {
            roots.forEach((rid, i) => {
                const angle = (i / roots.length) * 2 * Math.PI;
                const rx = centerX + Math.cos(angle) * levelRadius;
                const ry = centerY + Math.sin(angle) * levelRadius;
                positionNode(rid, rx, ry, (i / roots.length) * 360 - 90, 360/roots.length, 1);
            });
        }

        function positionNode(id, x, y, startAngle, sectorSize, level) {
            const card = cardMap[id];
            if (!card) return;

            card.style.left = (x - 110) + 'px'; // 110 is half width
            card.style.top = (y - 40) + 'px';
            savePosition(id, parseInt(card.style.left), parseInt(card.style.top));

            const children = peopleByParent[id] || [];
            if (children.length === 0) return;

            const nextLevel = level + 1;
            const childSectorSize = sectorSize / children.length;
            
            children.forEach((cid, i) => {
                const childAngleDeg = startAngle + (i + 0.5) * childSectorSize;
                const childAngleRad = (childAngleDeg * Math.PI) / 180;
                
                const cx = x + Math.cos(childAngleRad) * levelRadius;
                const cy = y + Math.sin(childAngleRad) * levelRadius;
                
                positionNode(cid, cx, cy, startAngle + i * childSectorSize, childSectorSize, nextLevel);
            });
        }

        drawConnections();
        resetZoom();
    }


    // Modals helpers (reused)
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
                valueField: 'id', labelField: 'name', searchField: 'name', placeholder: "{{ __('Select Spouse') }}", maxItems: 1
            });
        }
        spouseSelect.clear(); spouseSelect.clearOptions();
        spouseSelect.addOptions([{id: '', name: "{{ __('Searching...') }}"}]);
        fetch(`/people/${id}/potential-spouses`).then(res => res.json()).then(data => {
            spouseSelect.clearOptions();
            if (data.length === 0) {
                spouseSelect.addOptions([{id: '', name: "{{ __('No suitable candidates found') }}"}]);
            } else {
                spouseSelect.addOptions(data.map(p => ({ id: p.id, name: `${p.first_name} ${p.last_name} (${p.birth_year ?? '?'})` })));
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
        document.getElementById('edit_birth_year').value = person.birth_year || '';
        document.getElementById('edit_death_year').value = person.death_year || '';
        document.getElementById('edit_description').value = person.description || '';
    }
</script>
@endsection
