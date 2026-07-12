@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Customers</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
        <tbody>
        @foreach($customers as $c)
            <tr><td>{{ $c->id }}</td><td>{{ $c->name }}</td><td>{{ $c->email }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
