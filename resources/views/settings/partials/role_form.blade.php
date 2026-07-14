@php
    $selectedPermissions = old('permissions', $role->permissions ?? []);
    $isActive = old('is_active', ($role->is_active ?? true) ? '1' : null);
@endphp

<form class="access-form" method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <section class="access-panel">
        <div class="access-panel-head">
            <h2>Role Details</h2>
            <span class="access-badge">{{ isset($role) && $role->is_system ? 'System Role' : 'Custom Role' }}</span>
        </div>
        <div class="security-grid">
            <div class="access-field">
                <label for="name">Role Name</label>
                <input id="name" name="name" value="{{ old('name', $role->name ?? '') }}" placeholder="Branch Manager" required>
            </div>
            <div class="access-field">
                <label>Status</label>
                <label class="access-check access-check-box">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) $isActive)>
                    Active role
                </label>
            </div>
        </div>
        <div class="access-field">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="What this role is responsible for">{{ old('description', $role->description ?? '') }}</textarea>
        </div>
    </section>

    <section class="access-panel">
        <div class="access-panel-head">
            <h2>Permission Configuration</h2>
            <span class="access-muted">Select the exact actions this role can perform</span>
        </div>
        <div class="permission-grid">
            @foreach($permissionGroups as $group => $permissions)
                <div class="permission-group">
                    <strong>{{ $group }}</strong>
                    @foreach($permissions as $key => $label)
                        <label class="access-check">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPermissions, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>

    <div class="access-footer-actions">
        <a class="access-button secondary" href="{{ route('settings.roles') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <button class="access-button" type="submit"><i class="fa-solid fa-floppy-disk"></i> {{ $submitLabel }}</button>
    </div>
</form>
