@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page">
    <header class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-pen-to-square"></i></div>
            <div>
                <h1>Edit Role</h1>
                <p>Update role details and permission coverage for {{ $role->name }}.</p>
            </div>
        </div>
        <div class="access-actions">
            <a class="access-button secondary" href="{{ route('settings.roles.show', $role) }}"><i class="fa-solid fa-eye"></i> View</a>
            <a class="access-button secondary" href="{{ route('settings.roles') }}"><i class="fa-solid fa-table-list"></i> Roles</a>
        </div>
    </header>

    @if(session('success'))
        <div class="access-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    @include('settings.partials.role_form', [
        'action' => route('settings.roles.update', $role),
        'method' => 'PUT',
        'submitLabel' => 'Save Role',
    ])
</section>
@endsection
