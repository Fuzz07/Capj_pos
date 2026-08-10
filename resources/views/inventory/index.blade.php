@extends('layouts.app')

@section('title', 'Inventory Management - Captain J POS')

@section('content')
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold m-0 text-dark">Inventory Management</h3>
                <p class="text-secondary small m-0">Track product stock levels, pricing, and active menu items.</p>
            </div>
            @if(auth()->user()->isAdmin())
                <button class="btn btn-primary fw-semibold px-3 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#addItemModal">
                    <i class="fa-solid fa-plus me-2"></i> Add New Product
                </button>
            @endif
        </div>
        @php
            $outOfStockCount = $items->filter(fn($i) => $i->stock_qty <= 0)->count();
            $lowStockCount = $items->filter(fn($i) => $i->stock_qty >= 1 && $i->stock_qty <= 5)->count();
        @endphp

        @if($outOfStockCount > 0 || $lowStockCount > 0)
            <div class="alert border-0 shadow-sm p-3 mb-4 rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background-color: #fff1f2; border: 1px solid #fecdd3 !important; color: #9f1239;">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #ffe4e6; color: #e11d48;">
                        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0" style="color: #9f1239;">Inventory Stock Alert Attention Required!</h6>
                        <p class="small m-0" style="color: #be123c;">
                            @if($outOfStockCount > 0)
                                <span class="badge bg-danger text-white me-1"><i class="fa-solid fa-circle-xmark me-1"></i> {{ $outOfStockCount }} Out of Stock (0 remaining)</span>
                            @endif
                            @if($lowStockCount > 0)
                                <span class="badge me-1" style="background-color: #fecdd3; color: #9f1239; font-weight: 700;"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ $lowStockCount }} Low Stock (1-5 left)</span>
                            @endif
                            &mdash; Review highlighted items below and click +Stock to replenish.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters & Search Card (Normal Sized) -->
        <div class="card card-custom p-3 mb-4" style="max-width: 650px;">
            <form action="{{ route('inventory.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i
                                class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="inventorySearchInput" name="search"
                            class="form-control bg-light border-start-0" value="{{ request('search') }}"
                            placeholder="Search inventory by name or description...">
                    </div>
                </div>
            </form>
        </div>

        <!-- Inventory Table Card -->
        <div class="card card-custom p-4">
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem; min-width: 800px;">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock Qty</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $isOut = ($item->stock_qty <= 0);
                                $isLow = ($item->stock_qty >= 1 && $item->stock_qty <= 5);
                            @endphp
                            <tr style="{{ $isOut ? 'background-color: #ffe4e6 !important;' : ($isLow ? 'background-color: #fff1f2 !important;' : '') }}">
                                <td class="fw-bold text-secondary">#{{ $item->id }}</td>
                                <td class="fw-bold text-dark">
                                    {{ $item->name }}
                                    @if($isOut)
                                        <span class="badge bg-danger text-white ms-1"><i class="fa-solid fa-circle-xmark me-1"></i> OUT OF STOCK</span>
                                    @elseif($isLow)
                                        <span class="badge ms-1" style="background-color: #fecdd3; color: #9f1239; font-weight: 700;"><i class="fa-solid fa-circle-exclamation me-1"></i> LOW STOCK</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $item->description ?? 'N/A' }}</td>
                                <td class="fw-bold text-primary">₱{{ number_format($item->price, 2) }}</td>
                                <td>
                                    @if($isOut)
                                        <span class="badge bg-danger text-white fw-bold fs-6 shadow-sm">
                                            <i class="fa-solid fa-circle-xmark me-1"></i> 0 (Out of Stock)
                                        </span>
                                    @elseif($isLow)
                                        <span class="badge fw-bold fs-6 shadow-sm" style="background-color: #fecdd3; color: #9f1239;">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $item->stock_qty }} (Low Stock)
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success fw-bold fs-6">
                                            {{ $item->stock_qty }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <!-- Add Stock Button -->
                                    <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal"
                                        data-bs-target="#addStockModal{{ $item->id }}" title="Add Stock">
                                        <i class="fa-solid fa-boxes-packing"></i> +Stock
                                    </button>

                                    <!-- Edit Button -->
                                    @if(auth()->user()->isAdmin())
                                        <button class="btn btn-sm btn-light text-primary border me-1" data-bs-toggle="modal"
                                            data-bs-target="#editItemModal{{ $item->id }}" title="Edit Item">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST"
                                            class="d-inline confirm-delete"
                                            data-confirm-message="Delete item '{{ addslashes($item->name) }}'?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger border"
                                                title="Delete Item">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Modal: Add Stock -->
                                    <div class="modal fade" id="addStockModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered modal-sm text-start">
                                            <form action="{{ route('inventory.add-stock', $item->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Add Stock: {{ $item->name }}</h6>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <label class="form-label small fw-semibold text-secondary">Quantity to
                                                            Add</label>
                                                        <input type="number" name="add_qty" class="form-control" min="1"
                                                            value="10" required>
                                                    </div>
                                                    <div class="modal-footer py-2">
                                                        <button type="submit" class="btn btn-success btn-sm w-100">Confirm
                                                            Add</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Modal: Edit Item -->
                                    <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <form action="{{ route('inventory.update', $item->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit Item #{{ $item->id }}</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Product
                                                                Name</label>
                                                            <input type="text" name="name" class="form-control"
                                                                value="{{ $item->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label
                                                                class="form-label small fw-semibold text-secondary">Description
                                                                / Category</label>
                                                            <textarea name="description" class="form-control"
                                                                rows="2">{{ $item->description }}</textarea>
                                                        </div>
                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label small fw-semibold text-secondary">Price
                                                                    (₱)</label>
                                                                <input type="number" step="0.01" min="0" name="price"
                                                                    class="form-control" value="{{ $item->price }}" required>
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label small fw-semibold text-secondary">Stock
                                                                    Quantity</label>
                                                                <input type="number" min="0" name="stock_qty"
                                                                    class="form-control" value="{{ $item->stock_qty }}"
                                                                    required>
                                                            </div>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="checkbox" name="is_active" class="form-check-input"
                                                                id="active{{ $item->id }}" value="1" {{ $item->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label small text-secondary"
                                                                for="active{{ $item->id }}">Active for POS Ordering</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light border"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No inventory items matched your filter
                                    criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Modal: Add New Product -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('inventory.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus text-primary me-2"></i> Add New Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Product Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Taro Milktea Large"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Description / Category</label>
                            <textarea name="description" class="form-control" rows="2"
                                placeholder="e.g. Creamy Milktea - Taro Large"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Price (₱)</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control"
                                    placeholder="95.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Initial Stock Qty</label>
                                <input type="number" min="0" name="stock_qty" class="form-control" value="25" required>
                            </div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="newIsActive" value="1"
                                checked>
                            <label class="form-check-label small text-secondary" for="newIsActive">Active for POS
                                Ordering</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('inventorySearchInput');
            if (searchInput) {
                // Focus on the input and move the cursor to the end
                const val = searchInput.value;
                searchInput.focus();
                searchInput.setSelectionRange(val.length, val.length);

                let timeout = null;
                searchInput.addEventListener('input', function () {
                    clearTimeout(timeout);
                    timeout = setTimeout(function () {
                        searchInput.form.submit();
                    }, 400); // 400ms debounce
                });
            }
        });
    </script>
@endpush