@extends('layouts.app')

@section('title', __('Families') . ' - ' . __('Shajara'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>{{ __('All Families') }}</h1>
    <div class="d-flex gap-2">
        <form action="{{ route('families.index') }}" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="{{ __('Search families...') }}" value="{{ request('search') }}">
            <button type="submit" class="btn btn-outline-secondary">{{ __('Search') }}</button>
        </form>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFamilyModal">
            <i class="fas fa-plus me-1"></i> {{ __('New Family') }}
        </button>
    </div>
</div>

<div class="row">
    @forelse($families as $family)
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ $family->name }}</h5>
                    <p class="card-text text-muted">
                        {{ $family->people_count }} {{ __('family members') }}
                    </p>
                    <a href="{{ route('families.show', $family) }}" class="btn btn-outline-primary w-100">
                        {{ __('View Tree') }}
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <h3>{{ __('No families found yet.') }}</h3>
                <p>{{ __('Start by creating your first family!') }}</p>
            </div>
        </div>
    @endforelse
</div>

<!-- Modal -->
<div class="modal fade" id="createFamilyModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('families.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create New Family') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Family Name (e.g. Usmonovlar)') }}</label>
                        <input type="text" name="name" class="form-control" id="name" required placeholder="{{ __('Enter family surname') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create Family') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
