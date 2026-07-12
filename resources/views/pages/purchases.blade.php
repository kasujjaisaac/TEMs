@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Purchases</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Supplier</th><th>Total</th></tr></thead>
        <tbody>
        @foreach($purchases as $p)
            <tr><td>{{ $p->id }}</td><td>{{ $p->supplier_id }}</td><td>{{ $p->total }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
