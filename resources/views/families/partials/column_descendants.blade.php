@php
    $direct = $person->children;
    $spouseC = collect();
    foreach($person->spouses as $sp) {
        $spouseC = $spouseC->merge($sp->children);
    }
    $all = $direct->merge($spouseC)->unique('id');
@endphp

@if($all->isNotEmpty())
    <ul class="list-unstyled ms-2 border-start border-2 border-secondary ps-2 mb-0" style="margin-top: 5px;">
        @foreach($all as $descendant)
            <li class="mb-1" style="font-size: 0.85rem;">
                <span class="text-muted"><i class="fas fa-long-arrow-alt-right" style="font-size: 0.7em;"></i></span>
                @if($descendant->gender == 'male') 
                    <i class="fas fa-user fa-sm text-primary"></i> 
                @else 
                    <i class="fas fa-user-nurse fa-sm" style="color: #e83e8c"></i> 
                @endif
                <span class="fw-bold">{{ $descendant->first_name }}</span>
                @if($descendant->birth_year) 
                    <small class="text-muted">({{ $descendant->birth_year }})</small> 
                @endif
                
                @include('families.partials.column_descendants', ['person' => $descendant])
            </li>
        @endforeach
    </ul>
@endif
