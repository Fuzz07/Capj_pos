@extends('layouts.app')

@section('title', 'POS Terminal - Captain J POS')

@push('styles')
<style>
    .pos-layout-wrapper {
        align-items: flex-start;
    }
    .sticky-top-filters {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #f8fafc;
        padding-bottom: 1rem;
    }
    .category-pill {
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .category-pill:hover, .category-pill.active {
        background: #06b6d4;
        color: #ffffff;
        border-color: #06b6d4;
        box-shadow: 0 4px 6px -1px rgba(6, 182, 212, 0.3);
    }
    .item-card {
        cursor: pointer;
        border: 1px solid #f1f5f9;
        border-radius: 0.75rem;
        background: #ffffff;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);
        border-color: #cbd5e1;
    }
    .sticky-cart-sidebar {
        position: sticky;
        top: 1rem;
        max-height: calc(100vh - 2rem);
    }
    .cart-container {
        background: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        height: calc(100vh - 2rem);
        display: flex;
        flex-direction: column;
    }
    .cart-items-scroll {
        flex: 1;
        overflow-y: auto;
    }
    .gcash-qr-box {
        text-align: center;
        padding: 1rem;
        background: #e8f4fd;
        border-radius: 12px;
        border: 2px solid #0057a3;
    }
    .gcash-qr-box img {
        max-width: 260px;
        width: 100%;
        border-radius: 8px;
        border: 2px solid #0057a3;
    }

    /* Mobile & portrait tablets: cart stacks under the product grid */
    @media (max-width: 991.98px) {
        .sticky-top-filters {
            position: static;
            padding-bottom: 0.5rem;
        }
        .sticky-cart-sidebar {
            position: static;
            max-height: none;
        }
        .cart-container {
            height: auto;
        }
        .cart-items-scroll {
            max-height: 45vh;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row g-4 pos-layout-wrapper">
        <!-- Products Column (Left 8 Cols) -->
        <div class="col-12 col-lg-8">
            <!-- Search & Filters (Sticky at top of product column) -->
            <div class="sticky-top-filters">
                <div class="card border-0 shadow-sm p-3 m-0">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="Search menu item (e.g. Taro, Frappe, Fries)...">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 text-md-end text-muted small">
                            Showing <strong id="visibleCount">{{ count($items) }}</strong> menu items
                        </div>
                    </div>

                    <!-- Category Pills Horizontal Scroll -->
                    <div class="d-flex gap-2 overflow-x-auto mt-3 pb-1" id="categoryPills">
                        @foreach($categories as $cat)
                        <div class="category-pill {{ $loop->first ? 'active' : '' }}" data-category="{{ $cat }}">
                            {{ $cat }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Items Grid (Only this section scrolls) -->
            <div class="row g-3 mt-1" id="itemsGrid">
                @foreach($items as $item)
                <div class="col-6 col-sm-4 col-md-3 item-wrapper" 
                     data-name="{{ strtolower($item->name) }}" 
                     data-description="{{ strtolower($item->description ?? '') }}"
                     data-category="{{ strtolower($item->description ?? $item->name) }}">
                    <div class="item-card p-3 h-100 d-flex flex-column justify-content-between"
                         onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, {{ $item->stock_qty }})">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge {{ $item->stock_qty > 5 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} small">
                                    Stock: {{ $item->stock_qty }}
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark m-0 leading-tight mb-1" style="font-size: 0.95rem;">{{ $item->name }}</h6>
                            <p class="text-muted small m-0 line-clamp-2" style="font-size: 0.75rem;">{{ $item->description }}</p>
                        </div>
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary fs-5">₱{{ number_format($item->price, 2) }}</span>
                            <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Cart Sidebar Column (Right 4 Cols - STICKY) -->
        <div class="col-12 col-lg-4 sticky-cart-sidebar">
            <form action="{{ route('pos.order') }}" method="POST" id="checkoutForm" onsubmit="handleFormSubmit(event)">
                @csrf
                <div class="cart-container p-4">
                    <!-- Cart Header -->
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3">
                        <h5 class="fw-bold m-0"><i class="fa-solid fa-cart-shopping text-info me-2"></i> Current Order</h5>
                        <button type="button" class="btn btn-sm btn-light text-danger border" onclick="clearCart()">
                            <i class="fa-solid fa-trash me-1"></i> Clear
                        </button>
                    </div>

                    <!-- Customer & Order Type Inputs -->
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <input type="text" name="customer_name" id="customerNameInput" class="form-control form-control-sm" placeholder="Customer Name (Optional)">
                        </div>
                        <div class="col-5">
                            <select name="order_type" id="orderType" class="form-select form-select-sm" onchange="calculateCart()">
                                <option value="Dine-in">Dine-in</option>
                                <option value="Take-out">Take-out</option>
                            </select>
                        </div>
                    </div>

                    <!-- Cart Items Scroll List -->
                    <div class="cart-items-scroll mb-3 pe-1" id="cartItemsList">
                        <div class="text-center py-5 text-muted" id="emptyCartState">
                            <i class="fa-solid fa-basket-shopping fs-1 mb-2 opacity-50"></i>
                            <p class="m-0">Your cart is empty.</p>
                            <span class="small text-muted">Click items on the left to add.</span>
                        </div>
                    </div>

                    <!-- Order Summary & Checkout -->
                    <div class="pt-3 border-top">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Subtotal</span>
                            <span class="fw-semibold text-dark" id="subtotalDisplay">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Take-out Fee (₱5 / 2 pcs)</span>
                            <span class="fw-semibold text-dark" id="takeoutFeeDisplay">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fs-5 fw-bold text-dark my-2">
                            <span>Total</span>
                            <span class="text-primary" id="totalDisplay">₱0.00</span>
                        </div>

                        <!-- Payment Method Tabs (PayMongo Removed) -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Payment Method</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked onchange="togglePaymentInputs()">
                                <label class="btn btn-outline-secondary btn-sm" for="payCash"><i class="fa-solid fa-money-bill-wave me-1"></i> Cash</label>

                                <input type="radio" class="btn-check" name="payment_method" id="payGcash" value="gcash" onchange="togglePaymentInputs()">
                                <label class="btn btn-outline-secondary btn-sm" for="payGcash"><i class="fa-solid fa-qrcode me-1"></i> GCash</label>
                            </div>
                        </div>

                        <!-- Amount Paid & Change Inputs for Cash -->
                        <div id="cashInputs" class="mb-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Amount Tendered</label>
                                    <input type="number" name="amount_paid" id="amountPaidInput" step="0.01" min="0" class="form-control form-control-sm" placeholder="₱0.00" oninput="calculateCart()">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Change Due</label>
                                    <input type="text" id="changeDueInput" class="form-control form-control-sm bg-light" readonly value="₱0.00">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" id="btnCheckout" disabled>
                            <i class="fa-solid fa-check-circle me-2"></i> Complete Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- GCash Payment Modal -->
<div class="modal fade" id="gcashModal" tabindex="-1" aria-labelledby="gcashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-header-title fw-bold m-0" id="gcashModalLabel"><i class="fa-solid fa-qrcode me-2"></i> GCash Payment QR</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h4 class="fw-bold text-primary mb-3">Total Amount: <span id="modalGcashTotal">₱0.00</span></h4>
                
                <div class="gcash-qr-box mb-3">
                    <p class="small fw-semibold text-primary mb-2">Scan QR Code using GCash App</p>
                    <img src="{{ asset('images/gcash-qr.jpg') }}" alt="GCash QR Code" class="img-fluid mb-2" onerror="this.src='{{ asset('images/capj.jpg') }}'">
                    <p class="mb-0 fw-bold fs-5 text-dark">GCash #: <span class="text-primary">09536774000</span></p>
                </div>

                <div class="alert alert-info text-start small mb-3">
                    <strong>Payment Verification Checklist:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Customer scans QR code or enters GCash number above.</li>
                        <li>Customer pays exact total amount (<span id="modalGcashTotalCheck">₱0.00</span>).</li>
                        <li>Staff verifies payment screenshot or SMS notification.</li>
                    </ul>
                </div>

                <button type="button" class="btn btn-success w-100 py-2 fw-bold fs-5 shadow-sm" onclick="confirmGcashOrder()">
                    <i class="fa-solid fa-check-circle me-2"></i> Payment Received — Complete Order
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let cart = {};

    function addToCart(id, name, price, maxStock) {
        if (!cart[id]) {
            cart[id] = { id: id, name: name, price: price, qty: 1, maxStock: maxStock };
        } else {
            if (cart[id].qty + 1 > maxStock) {
                alert(`Cannot add more. Only ${maxStock} units available in stock.`);
                return;
            }
            cart[id].qty++;
        }
        renderCart();
    }

    function updateQty(id, delta) {
        if (cart[id]) {
            let newQty = cart[id].qty + delta;
            if (newQty <= 0) {
                delete cart[id];
            } else if (newQty > cart[id].maxStock) {
                alert(`Stock limit reached (${cart[id].maxStock}).`);
            } else {
                cart[id].qty = newQty;
            }
            renderCart();
        }
    }

    function removeFromCart(id) {
        delete cart[id];
        renderCart();
    }

    function clearCart() {
        cart = {};
        renderCart();
    }

    function renderCart() {
        const list = document.getElementById('cartItemsList');
        const emptyState = document.getElementById('emptyCartState');
        const keys = Object.keys(cart);

        if (keys.length === 0) {
            list.innerHTML = `
                <div class="text-center py-5 text-muted" id="emptyCartState">
                    <i class="fa-solid fa-basket-shopping fs-1 mb-2 opacity-50"></i>
                    <p class="m-0">Your cart is empty.</p>
                    <span class="small text-muted">Click items on the left to add.</span>
                </div>`;
            document.getElementById('btnCheckout').disabled = true;
            calculateCart();
            return;
        }

        let html = '';
        let index = 0;
        keys.forEach(id => {
            const item = cart[id];
            html += `
                <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-light rounded border">
                    <input type="hidden" name="items[${index}][id]" value="${item.id}">
                    <input type="hidden" name="items[${index}][qty]" value="${item.qty}">
                    <div style="flex: 1; min-width: 0;" class="me-2">
                        <div class="fw-semibold text-dark text-truncate" style="font-size: 0.85rem;">${item.name}</div>
                        <div class="small text-muted">₱${item.price.toFixed(2)} × ${item.qty}</div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-white border py-0 px-2" onclick="updateQty(${item.id}, -1)">-</button>
                        <span class="fw-bold small px-1">${item.qty}</span>
                        <button type="button" class="btn btn-sm btn-white border py-0 px-2" onclick="updateQty(${item.id}, 1)">+</button>
                        <button type="button" class="btn btn-sm text-danger ms-1" onclick="removeFromCart(${item.id})"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>
            `;
            index++;
        });

        list.innerHTML = html;
        document.getElementById('btnCheckout').disabled = false;
        calculateCart();
    }

    function getCartTotals() {
        let subtotal = 0;
        let totalQty = 0;
        Object.values(cart).forEach(item => {
            subtotal += item.price * item.qty;
            totalQty += item.qty;
        });

        const isTakeout = document.getElementById('orderType').value === 'Take-out';
        const takeoutFee = isTakeout ? (Math.ceil(totalQty / 2) * 5) : 0.00;
        const total = subtotal + takeoutFee;

        return { subtotal, takeoutFee, total, totalQty };
    }

    function calculateCart() {
        const { subtotal, takeoutFee, total } = getCartTotals();

        document.getElementById('subtotalDisplay').innerText = `₱${subtotal.toFixed(2)}`;
        document.getElementById('takeoutFeeDisplay').innerText = `₱${takeoutFee.toFixed(2)}`;
        document.getElementById('totalDisplay').innerText = `₱${total.toFixed(2)}`;

        const paid = parseFloat(document.getElementById('amountPaidInput').value) || 0;
        const change = Math.max(0, paid - total);
        document.getElementById('changeDueInput').value = `₱${change.toFixed(2)}`;
    }

    function togglePaymentInputs() {
        const isCash = document.getElementById('payCash').checked;
        const cashInputs = document.getElementById('cashInputs');
        cashInputs.style.display = isCash ? 'block' : 'none';
    }

    function handleFormSubmit(e) {
        e.preventDefault();

        const keys = Object.keys(cart);
        if (keys.length === 0) {
            Swal.fire('Empty Cart', 'Please add items to cart before proceeding.', 'warning');
            return;
        }

        const isGcash = document.getElementById('payGcash').checked;
        const { total } = getCartTotals();

        if (isGcash) {
            // Show GCash QR Modal
            document.getElementById('modalGcashTotal').innerText = `₱${total.toFixed(2)}`;
            document.getElementById('modalGcashTotalCheck').innerText = `₱${total.toFixed(2)}`;
            const gcashModal = new bootstrap.Modal(document.getElementById('gcashModal'));
            gcashModal.show();
        } else {
            // Cash payment validation
            const paid = parseFloat(document.getElementById('amountPaidInput').value) || 0;
            if (paid < total) {
                Swal.fire({
                    title: 'Insufficient Cash',
                    text: `Tendered amount (₱${paid.toFixed(2)}) is less than total amount (₱${total.toFixed(2)}).`,
                    icon: 'error',
                });
                return;
            }
            document.getElementById('checkoutForm').submit();
        }
    }

    function confirmGcashOrder() {
        // Close modal and submit
        const modalEl = document.getElementById('gcashModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        document.getElementById('checkoutForm').submit();
    }

    // Category Filter & Search Logic
    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            const category = this.dataset.category.toLowerCase();
            filterItems(category, document.getElementById('searchInput').value.toLowerCase());
        });
    });

    document.getElementById('searchInput').addEventListener('input', function() {
        const activeCat = document.querySelector('.category-pill.active').dataset.category.toLowerCase();
        filterItems(activeCat, this.value.toLowerCase());
    });

    function filterItems(category, query) {
        let count = 0;
        document.querySelectorAll('.item-wrapper').forEach(wrapper => {
            const name = wrapper.dataset.name;
            const desc = wrapper.dataset.description;
            const cat = wrapper.dataset.category;

            const matchesCategory = (category === 'all') || cat.includes(category) || name.includes(category);
            const matchesQuery = !query || name.includes(query) || desc.includes(query);

            if (matchesCategory && matchesQuery) {
                wrapper.style.display = 'block';
                count++;
            } else {
                wrapper.style.display = 'none';
            }
        });

        document.getElementById('visibleCount').innerText = count;
    }
</script>
@endpush
