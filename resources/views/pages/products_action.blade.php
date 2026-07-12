@extends('layouts.app')

@section('content')
<div class="card" style="max-width:640px;margin:16px">
    <h2>Product - {{ ucfirst($action) }}</h2>
    <form method="POST" action="/products/action">
        @csrf
        <label class="form-label">Name</label>
        <input name="name" class="input" required />
        <label class="form-label">Buying Price</label>
        <input name="buying_price" class="input" />
        <label class="form-label">Current Stock</label>
        <input name="current_stock" class="input" />
        <div style="margin-top:12px"><button class="btn" type="submit">Save</button></div>
    </form>
</div>
@endsection
