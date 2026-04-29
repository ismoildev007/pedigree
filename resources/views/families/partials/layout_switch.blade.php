<div class="btn-group btn-group-sm">
    <a href="{{ route('families.show', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn {{ Route::currentRouteName() == 'families.show' ? 'btn-primary' : 'btn-outline-primary' }}">Horizontal</a>
    <a href="{{ route('families.showVertical', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn {{ Route::currentRouteName() == 'families.showVertical' ? 'btn-primary' : 'btn-outline-primary' }}">Vertical</a>
    <a href="{{ route('families.showCircular', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn {{ Route::currentRouteName() == 'families.showCircular' ? 'btn-primary' : 'btn-outline-primary' }}">Circular</a>
    <a href="{{ route('families.showColumns', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn {{ Route::currentRouteName() == 'families.showColumns' ? 'btn-primary' : 'btn-outline-primary' }}">Columns</a>
</div>
