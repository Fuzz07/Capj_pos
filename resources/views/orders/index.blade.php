@extends('layouts.app')

@section('title', 'Orders - Captain J POS')

@push('styles')
<style>
    .card-custom {
        background: #ffffff;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
    }
    .badge-status {
        text-transform: capitalize;
        font-size: 0.8rem;
        padding: 0.35em 0.7em;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> Recent Orders</h3>
            <p class="text-secondary small m-0">View transactions, payment details, receipts, and order statuses.</p>
        </div>
    </div>

    <!-- Filters & Search Card -->
    <div class="card card-custom p-3 mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="orderSearch" class="form-control bg-light border-start-0" placeholder="Search orders by Customer, Staff, Payment, or Status...">
                </div>
            </div>
            <div class="col-12 col-md-6 text-md-end text-muted small">
                Showing <strong>{{ $orders->count() }}</strong> of <strong>{{ $orders->total() }}</strong> orders
            </div>
        </div>
    </div>

    <!-- Orders Data Table Card -->
    <div class="card card-custom p-4">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem; min-width: 850px;">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                        <th>Cashier / Staff</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="orderTable">
                    @forelse($orders as $o)
                    <tr>
                        <td class="fw-bold text-secondary">#{{ $o->id }}</td>
                        <td class="fw-bold text-dark">{{ $o->customer_name ?: 'Walk-in' }}</td>
                        <td class="fw-bold text-primary fs-6">₱{{ number_format($o->total_amount, 2) }}</td>
                        <td>
                            @if(strtolower($o->payment_method) === 'gcash')
                                <span class="badge" style="background:#0057a3; font-weight: 600;">📱 GCash</span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">💵 Cash</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge 
                                {{ $o->status === 'completed' ? 'bg-success' : ($o->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }} badge-status">
                                {{ ucfirst($o->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $o->created_at->format('M j, Y • h:i A') }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $o->user->full_name ?? ($o->user->username ?? 'System') }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ ucfirst($o->user->role ?? 'Staff') }}</div>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('orders.show', $o->id) }}" class="btn btn-sm btn-light text-primary border me-1" title="View Receipt">
                                <i class="fa-solid fa-eye me-1"></i> View
                            </a>

                            @if(auth()->user()->isAdmin())
                            <form action="{{ route('orders.destroy', $o->id) }}" method="POST" class="d-inline confirm-delete" data-confirm-message="Are you sure you want to delete Order #{{ $o->id }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger border" title="Delete Order">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fs-2 mb-2 opacity-50"></i>
                            <p class="m-0">No orders found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('orderSearch').addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        document.querySelectorAll('#orderTable tr').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
</script>
@endpush
