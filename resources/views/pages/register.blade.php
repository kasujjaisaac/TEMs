@extends('layouts.app')

@section('content')
<div class="card" style="max-width:420px;margin:24px">
    <h2>Register</h2>
    <form method="POST" action="/register">
        @csrf
        <label class="form-label">Name</label>
        <input name="name" class="input" value="{{ old('name') }}" />
        <label class="form-label">Email</label>
        <input name="email" type="email" class="input" value="{{ old('email') }}" />
        <label class="form-label">Password</label>
        <input name="password" type="password" class="input" />
        <label class="form-label">Confirm Password</label>
        <input name="password_confirmation" type="password" class="input" />
        <div style="margin-top:12px">
            <button class="btn" type="submit">Register</button>
        </div>
    </form>
</div>
@endsection
