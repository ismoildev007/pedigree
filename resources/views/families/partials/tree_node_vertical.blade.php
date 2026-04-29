<li>
    <div class="couple-wrapper">
        <div class="person-node gender-{{ $person->gender }}">
            @if($person->photo)
                <img src="{{ Storage::url($person->photo) }}" class="rounded-circle mb-2" style="width: 50px; height: 50px; object-fit: cover;">
            @else
                <i class="fas fa-{{ $person->gender === 'male' ? 'user' : 'user-nurse' }} fa-2x mb-2 text-secondary"></i>
            @endif
            
            <h6 class="mb-0">{{ $person->full_name }}</h6>
            <small class="text-muted d-block mb-2">
                ({{ $person->birth_year ?? '?' }} - {{ $person->death_year ?? 'Present' }})
            </small>

            <div class="d-flex gap-1 justify-content-center">
                <a href="{{ route('families.show', ['family' => $person->family_id, 'root_id' => $person->id]) }}" 
                    class="btn btn-sm btn-outline-info" title="Focus">
                    <i class="fas fa-search-plus"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary" 
                    data-bs-toggle="modal" data-bs-target="#editPersonModal"
                    data-person='@json($person)'
                    onclick='setEditData(this)'>
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" 
                    data-bs-toggle="modal" data-bs-target="#addPersonModal"
                    onclick="setParent({{ $person->id }}, '{{ addslashes($person->full_name) }}')" title="Add Child">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="btn btn-sm {{ $person->gender === 'female' ? 'btn-link text-muted' : 'btn-outline-danger' }}" 
                    data-bs-toggle="modal" data-bs-target="#addSpouseModal"
                    onclick="setSpouseTarget({{ $person->id }}, '{{ addslashes($person->full_name) }}')" title="Add Spouse">
                    <i class="fas fa-heart"></i>
                </button>
                <form action="{{ route('people.destroy', $person) }}" method="POST" onsubmit="return confirm('Delete?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        @foreach($person->spouses as $spouse)
            <div class="spouse-connector">
                <i class="fas fa-heart text-danger"></i>
            </div>
            <div class="person-node gender-{{ $spouse->gender }} spouse-node">
                @if($spouse->photo)
                    <img src="{{ Storage::url($spouse->photo) }}" class="rounded-circle mb-2" style="width: 50px; height: 50px; object-fit: cover;">
                @else
                    <i class="fas fa-{{ $spouse->gender === 'male' ? 'user' : 'user-nurse' }} fa-2x mb-2 text-muted"></i>
                @endif
                <h6 class="mb-1">{{ $spouse->full_name }}</h6>
                <small class="text-muted d-block">
                    ({{ $spouse->birth_year ?? '?' }} - {{ $spouse->death_year ?? 'Present' }})
                </small>
            </div>
        @endforeach
    </div>

    @php
        $directChildren = $person->children;
        $spouseChildren = collect();
        foreach($person->spouses as $spouse) {
            $spouseChildren = $spouseChildren->merge($spouse->children);
        }
        $allChildren = $directChildren->merge($spouseChildren)->unique('id');
    @endphp

    @if($allChildren->isNotEmpty())
        <ul>
            @foreach($allChildren as $child)
                @include('families.partials.tree_node_vertical', ['person' => $child])
            @endforeach
        </ul>
    @endif
</li>
