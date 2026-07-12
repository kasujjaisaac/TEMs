@extends('layouts.app')

@section('content')
<div class="page-header">
	<div class="page-title">
		<h1>Dashboard <span class="gold">Overview</span></h1>
		<div class="page-subtitle">Real-time management overview with KPIs and charts</div>
	</div>
	<div class="page-nav">
		<!-- actions -->
	</div>
</div>

<div class="stat-grid">
	<div class="stat-card card">
		<div class="stat-label">Total Inventory Value</div>
		<div class="stat-value">{{ number_format($inventory_value, 2) }} {{ $currency }}</div>
	</div>
	<div class="stat-card card">
		<div class="stat-label">Low Stock Items</div>
		<div class="stat-value">{{ $low_stock_count }}</div>
	</div>
	<div class="stat-card card">
		<div class="stat-label">Active Customers</div>
		<div class="stat-value">{{ $customer_count }}</div>
	</div>
	<div class="stat-card card">
		<div class="stat-label">Active Suppliers</div>
		<div class="stat-value">{{ $supplier_count }}</div>
	</div>
	<div class="stat-card card">
		<div class="stat-label">Today's Installations</div>
		<div class="stat-value">{{ $today_installations }}</div>
	</div>
	<div class="stat-card card">
		<div class="stat-label">Upcoming Maintenance</div>
		<div class="stat-value">{{ $upcoming_maintenance }}</div>
	</div>
</div>

<div class="module-grid">
	<div class="card">
		<div class="card-title">Inventory Value Trend</div>
		<div class="chart-shell">{!! $chartSvg !!}</div>
	</div>

	<div class="card">
		<div class="card-title">Quick Actions</div>
		<div class="action-grid">
			<!-- Quick action links can be added here -->
		</div>
	</div>

	<div class="card">
		<div class="card-title">Notifications</div>
		<ul>
			<li>Low Stock Alerts: {{ $low_stock_count }} Items</li>
			<li>Products Near Reorder Level: {{ $near_reorder }} Items</li>
			<li>Warranty Expiry Alerts: {{ $warranty_alerts }} Items</li>
			<li>Customer Payment Due: {{ $credit_customer_count }} Accounts</li>
			<li>Supplier Payment Due: {{ $credit_supplier_count }} Accounts</li>
			<li>Maintenance Due: {{ $maintenance_due }} Jobs</li>
		</ul>
	</div>

	<div class="card">
		<div class="card-title">Operational Summary</div>
		<ul>
			<li>Active Customers: {{ $customer_count }}</li>
			<li>Active Suppliers: {{ $supplier_count }}</li>
			<li>Products in Catalog: {{ $product_count }}</li>
			<li>Today's Installations: {{ $today_installations }}</li>
			<li>Upcoming Maintenance: {{ $upcoming_maintenance }}</li>
		</ul>
	</div>
</div>

@endsection
