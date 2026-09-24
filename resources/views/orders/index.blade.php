@extends('layouts.app')

@section('title', ($tab === 'archived' ? 'Archived Orders' : 'Orders') . ' - Captain J POS')

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
    .nav-tabs .nav-link {
        color: #64748b;
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
    }
    .nav-tabs .nav-link.active {
        color: #dc2626;
        border-bottom-color: #dc2626;
        background: transparent;
    }
    .nav-tabs .nav-link:hover:not(.active) {
        border-bottom-color: #cbd5e1;
    }

    /* Clean payment method icon pill */
    .pay-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3em 0.65em;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .pay-badge-gcash {
        background: #e8f0ff;
        color: #1a56db;
        border: 1px solid #c0d3ff;
    }
    .pay-badge-gcash .pay-icon {
        background: #1a56db;
        color: #fff;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        flex-shrink: 0;
    }
    .pay-badge-cash {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }
    .pay-badge-cash .pay-icon {
        background: #16a34a;
        color: #fff;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold m-0 text-dark">
                <i class="fa-solid fa-receipt text-primary me-2"></i> 
                {{ $tab === 'archived' ? 'Archived Orders' : 'Recent Orders' }}
            </h3>
            <p class="text-secondary small m-0">View transactions, payment details, receipts, and order statuses.</p>
        </div>
    </div>

    <!-- Tabs Navigation (Active vs Archived) -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a href="{{ route('orders.index', ['tab' => 'active']) }}" class="nav-link {{ $tab !== 'archived' ? 'active' : '' }}">
                <i class="fa-solid fa-list-check me-2"></i> Active Orders
                <span class="badge rounded-pill bg-danger-subtle text-danger ms-2">{{ $activeCount ?? $orders->total() }}</span>
            </a>
        </li>
        @if(auth()->user()->isAdmin())
        <li class="nav-item">
            <a href="{{ route('orders.index', ['tab' => 'archived']) }}" class="nav-link {{ $tab === 'archived' ? 'active' : '' }}">
                <i class="fa-solid fa-box-archive me-2"></i> Archived Orders
                <span class="badge rounded-pill bg-secondary ms-2">{{ $archivedCount ?? 0 }}</span>
            </a>
        </li>
        @endif
    </ul>

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
                Showing <strong>{{ $orders->count() }}</strong> of <strong>{{ $orders->total() }}</strong> {{ $tab === 'archived' ? 'archived' : '' }} orders
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
                                <span class="pay-badge pay-badge-gcash">
                                    <span class="pay-icon"><i class="fa-solid fa-mobile-screen-button"></i></span>
                                    GCash
                                </span>
                            @else
                                <span class="pay-badge pay-badge-cash">
                                    <span class="pay-icon"><i class="fa-solid fa-money-bill"></i></span>
                                    Cash
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge 
                            {{ $o->status === 'completed' ? 'bg-success' : (in_array($o->status, ['voided', 'cancelled']) ? 'bg-secondary' : ($o->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger')) }} badge-status">
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

                            @if($tab !== 'archived')
                                <!-- Void Button: Available for BOTH Admin & Staff -->
                                @if($o->status === 'completed')
                                <button type="button"
                                    class="btn btn-sm btn-light text-warning border me-1"
                                    title="Void Order"
                                    onclick="confirmVoid({{ $o->id }}, '{{ route('orders.void', $o->id) }}')"
                                >
                                    <i class="fa-solid fa-rotate-left me-1"></i> Void
                                </button>
                                @endif

                                <!-- Archive Button (Replaces Delete button): Admin Only -->
                                @if(auth()->user()->isAdmin())
                                <button type="button"
                                    class="btn btn-sm btn-light text-secondary border me-1"
                                    title="Archive Order"
                                    onclick="confirmArchive({{ $o->id }}, '{{ route('orders.destroy', $o->id) }}')"
                                >
                                    <i class="fa-solid fa-box-archive me-1"></i> Archive
                                </button>
                                @endif
                            @else
                                <!-- Archived Orders Tab Actions: Admin Only -->
                                @if(auth()->user()->isAdmin())
                                <!-- Unarchive Button -->
                                <button type="button"
                                    class="btn btn-sm btn-light text-success border me-1"
                                    title="Unarchive Order"
                                    onclick="confirmUnarchive({{ $o->id }}, '{{ route('orders.restore', $o->id) }}')"
                                >
                                    <i class="fa-solid fa-box-open me-1"></i> Unarchive
                                </button>

                                <!-- Permanent Delete Button -->
                                <button type="button"
                                    class="btn btn-sm btn-light text-danger border"
                                    title="Permanently Delete Order"
                                    onclick="confirmForceDelete({{ $o->id }}, '{{ route('orders.force-delete', $o->id) }}')"
                                >
                                    <i class="fa-solid fa-trash me-1"></i> Delete
                                </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fs-2 mb-2 opacity-50"></i>
                            <p class="m-0">No {{ $tab === 'archived' ? 'archived' : '' }} orders found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $orders->appends(['tab' => $tab])->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('orderSearch').addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        document.querySelectorAll('#orderTable tr').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });

    function postForm(actionUrl, method = 'POST') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = actionUrl;
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        if (method !== 'POST') {
            const mInput = document.createElement('input');
            mInput.type = 'hidden';
            mInput.name = '_method';
            mInput.value = method;
            form.appendChild(mInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // Validation 1: Void Order
    function confirmVoid(orderId, voidUrl) {
        Swal.fire({
            title: 'Void Order #' + orderId + '?',
            html: 'This will <strong>restore the inventory stock</strong> of all items in this order and mark it as <strong>Voided</strong>.<br><br>Do you want to proceed?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-rotate-left me-1"></i> Yes, Void It',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                postForm(voidUrl, 'POST');
            }
        });
    }

    // Validation 2: Archive Order
    function confirmArchive(orderId, archiveUrl) {
        Swal.fire({
            title: 'Archive Order #' + orderId + '?',
            text: 'This order will be moved to the Archived Orders list.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#64748b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-box-archive me-1"></i> Yes, Archive',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                postForm(archiveUrl, 'DELETE');
            }
        });
    }

    // Validation 3: Unarchive Order
    function confirmUnarchive(orderId, restoreUrl) {
        Swal.fire({
            title: 'Unarchive Order #' + orderId + '?',
            text: 'This order will be restored back to the Active Orders list.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-box-open me-1"></i> Yes, Unarchive',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                postForm(restoreUrl, 'POST');
            }
        });
    }

    // Validation 4: Permanent Delete Order
    function confirmForceDelete(orderId, deleteUrl) {
        Swal.fire({
            title: 'Permanently Delete Order #' + orderId + '?',
            html: '<strong class="text-danger">Warning:</strong> This action is permanent and cannot be undone!',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Yes, Delete Permanently',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                postForm(deleteUrl, 'DELETE');
            }
        });
    }
</script>
@endpush
