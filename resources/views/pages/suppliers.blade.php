@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Suppliers</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Contact</th></tr></thead>
        <tbody>
        @foreach($suppliers as $s)
            <tr><td>{{ $s->id }}</td><td>{{ $s->name }}</td><td>{{ $s->contact }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
