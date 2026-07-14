@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page">
    <header class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-plus"></i></div>
            <div>
                <h1>Create Role</h1>
                <p>Define a new system role, assign access rights, and make it available for user accounts.</p>
            </div>
        </div>
        <a class="access-button secondary" href="{{ route('settings.roles') }}"><i class="fa-solid fa-table-list"></i> Roles</a>
    </header>

    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    @include('settings.partials.role_form', [
        'action' => route('settings.roles.store'),
        'method' => 'POST',
        'submitLabel' => 'Create Role',
    ])
</section>
@endsection
