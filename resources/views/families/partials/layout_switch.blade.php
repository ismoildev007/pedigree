<div class="d-flex flex-wrap gap-1">
    <a href="{{ route('families.show', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn btn-sm {{ Route::currentRouteName() == 'families.show' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Horizontal') }}</a>
    <a href="{{ route('families.showVertical', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn btn-sm {{ Route::currentRouteName() == 'families.showVertical' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Vertical') }}</a>
    <a href="{{ route('families.showCircular', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn btn-sm {{ Route::currentRouteName() == 'families.showCircular' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Circular') }}</a>
    <a href="{{ route('families.showColumns', ['family' => $family, 'root_id' => request('root_id')]) }}" 
       class="btn btn-sm {{ Route::currentRouteName() == 'families.showColumns' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('Columns') }}</a>
</div>
