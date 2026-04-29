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

<style>
    .poster-container {
        overflow-x: auto;
        padding: 40px;
        background: #fdfdfc;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        min-height: 600px;
    }
    .poster-root-box {
        background: #2b5c46; /* aesthetic dark green from image */
        color: white;
        padding: 15px 30px;
        border-radius: 10px;
        border: 4px solid #gold;
        display: inline-block;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .poster-children-container {
        display: flex;
        gap: 20px;
        justify-content: center;
        align-items: stretch;
    }
    .column-block {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        min-width: 250px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .column-header {
        text-align: center;
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 2px solid #ccc;
    }
    .column-header img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .poster-connector-line {
        height: 2px;
        background-color: #2b5c46;
        width: 100%;
        margin-bottom: 20px;
        position: relative;
    }
    .poster-connector-line::after {
        content: '';
        position: absolute;
        width: 2px;
        height: 30px;
        background-color: #2b5c46;
        top: -30px;
        left: 50%;
    }
</style>

<div class="card shadow-sm border-0 mb-5">
    <div class="poster-container text-center">
        @if($roots->isNotEmpty())
            @foreach($roots as $rootPerson)
                <div class="mb-5" style="display: inline-block; text-align: left;">
                    
                    <!-- Root Node Banner -->
                    <div class="text-center">
                        <div class="poster-root-box">
                            <div class="d-flex align-items-center justify-content-center gap-4">
                                <!-- Root Person -->
                                <div class="text-center">
                                    @if($rootPerson->photo)
                                        <img src="{{ Storage::url($rootPerson->photo) }}" class="rounded-circle mb-2" style="width: 70px; height: 70px; object-fit: cover; border: 2px solid white;">
                                    @else
                                        <i class="fas fa-{{ $rootPerson->gender == 'male' ? 'user' : 'user-nurse' }} fa-3x mb-2 text-light"></i>
                                    @endif
                                    <h5 class="mb-0">{{ $rootPerson->full_name }}</h5>
                                </div>
                                
                                @if($rootPerson->spouses->isNotEmpty())
                                    <div class="text-white fw-bold fs-5">ва</div>
                                    <!-- Spouses -->
                                    @foreach($rootPerson->spouses as $spouse)
                                        <div class="text-center">
                                            @if($spouse->photo)
                                                <img src="{{ Storage::url($spouse->photo) }}" class="rounded-circle mb-2" style="width: 70px; height: 70px; object-fit: cover; border: 2px solid white;">
                                            @else
                                                <i class="fas fa-{{ $spouse->gender == 'male' ? 'user' : 'user-nurse' }} fa-3x mb-2 text-light"></i>
                                            @endif
                                            <h5 class="mb-0">{{ $spouse->full_name }}</h5>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @php
                        $directChildren = $rootPerson->children;
                        $spouseChildren = collect();
                        foreach($rootPerson->spouses as $opSpouse) {
                            $spouseChildren = $spouseChildren->merge($opSpouse->children);
                        }
                        $allChildren = $directChildren->merge($spouseChildren)->unique('id');
                        
                        $colors = ['#e8f5e9', '#e0f7fa', '#fce4ec', '#fff3e0', '#fff9c4', '#e3f2fd', '#f3e5f5'];
                    @endphp

                    @if($allChildren->isNotEmpty())
                        <div class="poster-connector-line"></div>
                        
                        <div class="poster-children-container">
                            @foreach($allChildren as $index => $child)
                                @php $bgColor = $colors[$index % count($colors)]; @endphp
                                <div class="column-block" style="background-color: {{ $bgColor }};">
                                    <div class="column-header">
                                        <div class="mb-2">
                                            <span class="badge rounded-pill bg-dark">{{ $index + 1 }}</span>
                                        </div>
                                        @if($child->photo)
                                            <img src="{{ Storage::url($child->photo) }}">
                                        @else
                                            <div class="d-inline-flex justify-content-center align-items-center rounded-circle mb-2" style="width: 60px; height: 60px; background: white; border: 2px solid #ccc;">
                                                <i class="fas fa-{{ $child->gender == 'male' ? 'user' : 'user-nurse' }} fa-2x text-muted"></i>
                                            </div>
                                        @endif
                                        <h5 class="fw-bold mb-0" style="color: #333;">{{ $child->first_name }}</h5>
                                        <div class="text-muted small">{{ $child->birth_year ?? '?' }} - {{ $child->death_year ?? 'Hozir' }}</div>
                                    </div>
                                    
                                    <div class="descendants-list">
                                        <div class="text-center mb-3">
                                            <strong class="border-bottom border-dark pb-1">{{ $child->first_name }} avlodi</strong>
                                        </div>
                                        @include('families.partials.column_descendants', ['person' => $child])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users-slash fa-4x mb-3"></i>
                <h4>No members in this family yet.</h4>
            </div>
        @endif
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

    // Auto-scroll to center on mobile so Root Banner is fully visible immediately
    window.addEventListener('load', () => {
        const poster = document.querySelector('.poster-container');
        if(poster && poster.scrollWidth > poster.clientWidth) {
            poster.scrollLeft = (poster.scrollWidth - poster.clientWidth) / 2;
        }
    });
</script>
@endsection
