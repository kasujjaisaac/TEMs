@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Products</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Stock</th></tr></thead>
        <tbody>
        @foreach($products as $p)
            <tr><td>{{ $p->id }}</td><td>{{ $p->name }}</td><td>{{ $p->current_stock }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
