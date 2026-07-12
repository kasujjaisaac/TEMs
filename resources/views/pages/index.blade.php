@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Welcome to Onyx Hub</h2>
    <p>Quick counts:</p>
    <ul>
        <li>Customers: {{ $counts['customers'] }}</li>
        <li>Products: {{ $counts['products'] }}</li>
        <li>Suppliers: {{ $counts['suppliers'] }}</li>
    </ul>
</div>
@endsection
