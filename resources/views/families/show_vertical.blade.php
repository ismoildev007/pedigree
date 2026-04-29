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
        <div class="d-flex align-items-center gap-3">
            <h1>{{ $family->name }} Family Tree</h1>
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
                    <h4>No members in this family yet.</h4>
                </div>
            @endif
        </div>

        <div class="zoom-controls">
            <button type="button" class="zoom-btn" onclick="zoomIn()" title="Zoom In"><i class="fas fa-plus"></i></button>
            <button type="button" class="zoom-btn" onclick="zoomOut()" title="Zoom Out"><i class="fas fa-minus"></i></button>
            <button type="button" class="zoom-btn" onclick="resetZoom()" title="Reset Zoom"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>
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

@endsection

@section('scripts')
<script>
    function setParent(id, name) {
        document.getElementById('modal_parent_id').value = id;
        document.getElementById('modal_title').innerText = 'Add Child for ' + name;
    }

    function setSpouseTarget(id, name) {
        const form = document.getElementById('addSpouseForm');
        form.action = `/people/${id}/add-spouse`;
        document.getElementById('spouse_modal_title').innerText = 'Add Spouse for ' + name;
        
        const select = document.getElementById('spouse_select');
        select.innerHTML = '<option value="">Searching...</option>';

        fetch(`/people/${id}/potential-spouses`)
            .then(res => res.json())
            .then(data => {
                select.innerHTML = '<option value="">Select Spouse</option>';
                if (data.length === 0) {
                    select.innerHTML = '<option value="">No suitable candidates found</option>';
                }
                data.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.text = `${p.first_name} ${p.last_name} (${p.birth_year ?? '?'})`;
                    select.appendChild(option);
                });
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
        const containerWidth = container.offsetWidth;
        const canvasWidth = canvas.scrollWidth;
        translateX = (containerWidth - canvasWidth) / 2;
        
        // Default zoom for mobile
        if (window.innerWidth < 768) {
            scale = 0.7;
        }
        
        updateTransform();
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
        scale += 0.2;
        updateTransform();
    }

    function zoomOut() {
        scale -= 0.2;
        updateTransform();
    }

    function resetZoom() {
        scale = 1;
        const containerWidth = container.offsetWidth;
        const canvasWidth = canvas.scrollWidth;
        translateX = (containerWidth - canvasWidth) / 2;
        translateY = 0;
        updateTransform();
    }

    // Touch Support for Mobile
    let lastTouchX, lastTouchY;
    let initialPinchDistance = 0;

    container.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            isDragging = true;
            lastTouchX = e.touches[0].clientX - translateX;
            lastTouchY = e.touches[0].clientY - translateY;
        } else if (e.touches.length === 2) {
            isDragging = false;
            initialPinchDistance = getDistance(e.touches[0], e.touches[1]);
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
                scale *= smoothedFactor;
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
