@extends('layouts.app')

@section('title', 'System Settings - CAPTAiN J POS')

@push('styles')
<style>
    .card-custom {
        background: #ffffff;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .settings-section-title {
        font-weight: 800;
        color: #0f172a;
        font-size: 1rem;
        margin: 0;
    }
    .settings-section-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex-shrink: 0;
    }
    .setting-hint {
        font-size: 0.75rem;
        color: #64748b;
    }
    .mail-status-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.4rem 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 0.85rem;
    }
    .mail-status-row:last-child { border-bottom: none; }
    .mail-status-row .label { color: #64748b; font-weight: 600; }
    .mail-status-row .value { color: #0f172a; font-weight: 600; text-align: right; word-break: break-all; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4" style="max-width: 1200px;">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="fa-solid fa-gear text-primary me-2"></i> System Settings</h3>
            <p class="text-secondary small m-0">Configure shop details, pricing rules, stock alerts, security and email.</p>
        </div>
        <span class="badge bg-danger-subtle text-danger fw-semibold px-3 py-2 rounded-pill">
            <i class="fa-solid fa-user-shield me-1"></i> Administrator only
        </span>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm small py-2 mb-4">
            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Please fix the following:</div>
            @foreach($errors->all() as $error)
                <div>&bull; {{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('mail_hint'))
        <div class="alert alert-info border-0 shadow-sm small mb-4">
            <div class="fw-bold mb-1"><i class="fa-solid fa-lightbulb me-1"></i> How to fix this</div>
            {{ session('mail_hint') }}
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf

        <div class="row g-4">
            <!-- Shop Information -->
            <div class="col-12 col-lg-6">
                <div class="card card-custom p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div class="settings-section-icon" style="background:#f10000;"><i class="fa-solid fa-store"></i></div>
                        <div>
                            <p class="settings-section-title">Shop Information</p>
                            <span class="setting-hint">Printed at the top of every receipt.</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Shop Name <span class="text-danger">*</span></label>
                        <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name', $settings['shop_name']) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tagline</label>
                        <input type="text" name="shop_tagline" class="form-control" value="{{ old('shop_tagline', $settings['shop_tagline']) }}" placeholder="POUR IT, SAVOR IT, LOVE IT">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Address</label>
                        <input type="text" name="shop_address" class="form-control" value="{{ old('shop_address', $settings['shop_address']) }}" placeholder="e.g. Poblacion, Bayawan City">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Contact Number</label>
                        <input type="text" name="shop_contact" class="form-control" value="{{ old('shop_contact', $settings['shop_contact']) }}" placeholder="e.g. 0953 677 4000">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Receipt Footer Message</label>
                        <input type="text" name="receipt_footer" class="form-control" value="{{ old('receipt_footer', $settings['receipt_footer']) }}" placeholder="Thank you for your order!">
                    </div>
                </div>
            </div>

            <!-- Sales & Payments -->
            <div class="col-12 col-lg-6">
                <div class="card card-custom p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div class="settings-section-icon" style="background:#16a34a;"><i class="fa-solid fa-cash-register"></i></div>
                        <div>
                            <p class="settings-section-title">Sales &amp; Payments</p>
                            <span class="setting-hint">Take-out charging rule and GCash details.</span>
                        </div>
                    </div>

                    <label class="form-label small fw-semibold text-secondary">Take-out Fee Rule <span class="text-danger">*</span></label>
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light">₱</span>
                                <input type="number" step="0.01" min="0" name="takeout_fee_amount" class="form-control" value="{{ old('takeout_fee_amount', $settings['takeout_fee_amount']) }}" required>
                            </div>
                        </div>
                        <div class="col-2 text-center small text-muted fw-semibold">for every</div>
                        <div class="col-5">
                            <div class="input-group">
                                <input type="number" min="1" name="takeout_fee_per_items" class="form-control" value="{{ old('takeout_fee_per_items', $settings['takeout_fee_per_items']) }}" required>
                                <span class="input-group-text bg-light">items</span>
                            </div>
                        </div>
                    </div>
                    <div class="setting-hint mb-4">
                        Currently: <strong>₱{{ number_format((float) $settings['takeout_fee_amount'], 2) }}</strong> charged for every
                        <strong>{{ $settings['takeout_fee_per_items'] }}</strong> item(s) on a take-out order, rounded up.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">GCash Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-mobile-screen"></i></span>
                            <input type="text" name="gcash_number" class="form-control" value="{{ old('gcash_number', $settings['gcash_number']) }}" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="setting-hint mt-1">Shown on the GCash payment screen at checkout.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">GCash Account Name</label>
                        <input type="text" name="gcash_name" class="form-control" value="{{ old('gcash_name', $settings['gcash_name']) }}" placeholder="Account holder name">
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="col-12 col-lg-6">
                <div class="card card-custom p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div class="settings-section-icon" style="background:#f39c12;"><i class="fa-solid fa-boxes-stacked"></i></div>
                        <div>
                            <p class="settings-section-title">Inventory Alerts</p>
                            <span class="setting-hint">When to warn about running low.</span>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Low Stock Threshold <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="0" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" required>
                            <span class="input-group-text bg-light">pcs or fewer</span>
                        </div>
                        <div class="setting-hint mt-2">
                            Products at or below this quantity are flagged <span class="badge bg-warning-subtle text-warning">Low Stock</span>
                            on the Inventory page and trigger a notification after a sale.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="col-12 col-lg-6">
                <div class="card card-custom p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div class="settings-section-icon" style="background:#7c3aed;"><i class="fa-solid fa-shield-halved"></i></div>
                        <div>
                            <p class="settings-section-title">Login &amp; Verification Security</p>
                            <span class="setting-hint">Brute-force protection and OTP lifetime.</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Max Login Attempts <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="20" name="login_max_attempts" class="form-control" value="{{ old('login_max_attempts', $settings['login_max_attempts']) }}" required>
                            <div class="setting-hint mt-1">Failed tries before lockout.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Lockout Duration <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" name="login_lockout_minutes" class="form-control" value="{{ old('login_lockout_minutes', $settings['login_lockout_minutes']) }}" required>
                                <span class="input-group-text bg-light">min</span>
                            </div>
                            <div class="setting-hint mt-1">How long they must wait.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Verification Code (OTP) Validity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" name="otp_expiry_minutes" class="form-control" value="{{ old('otp_expiry_minutes', $settings['otp_expiry_minutes']) }}" required>
                                <span class="input-group-text bg-light">minutes</span>
                            </div>
                            <div class="setting-hint mt-1">How long an emailed verification code stays usable.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save bar -->
        <div class="card card-custom p-3 mt-4 d-flex flex-row flex-wrap align-items-center justify-content-between gap-2">
            <span class="small text-muted"><i class="fa-solid fa-circle-info text-primary me-1"></i> Changes apply immediately across the whole system.</span>
            <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                <i class="fa-solid fa-floppy-disk me-2"></i> Save Settings
            </button>
        </div>
    </form>

    <!-- Email / OTP delivery -->
    <div class="card card-custom p-4 mt-4 mb-4">
        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
            <div class="settings-section-icon" style="background:#2563eb;"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <div>
                <p class="settings-section-title">Email &amp; OTP Delivery</p>
                <span class="setting-hint">Status of the mail service used to send verification codes.</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                @php
                    $delivers = !in_array($mail['mailer'], ['log', 'array'], true) || $mail['gmail_api'];
                @endphp

                <div class="alert {{ $delivers ? 'alert-success' : 'alert-warning' }} border-0 small py-2 mb-3">
                    <i class="fa-solid {{ $delivers ? 'fa-circle-check' : 'fa-triangle-exclamation' }} me-1"></i>
                    @if($delivers)
                        A delivering mail service is configured. Send a test below to confirm it works.
                    @else
                        <strong>No delivering mail service.</strong> MAIL_MAILER is
                        <code>{{ $mail['mailer'] }}</code>, which only writes to the log file.
                        Verification emails will not arrive until this is changed in <code>.env</code>.
                    @endif
                </div>

                <div class="mail-status-row"><span class="label">Mailer</span><span class="value">{{ $mail['mailer'] }}</span></div>
                @if($mail['mailer'] === 'smtp')
                    <div class="mail-status-row"><span class="label">SMTP Host</span><span class="value">{{ $mail['host'] ?: 'not set' }}</span></div>
                    <div class="mail-status-row"><span class="label">Port</span><span class="value">{{ $mail['port'] ?: 'not set' }}</span></div>
                    <div class="mail-status-row"><span class="label">Encryption</span><span class="value">{{ $mail['encryption'] ?: 'none' }}</span></div>
                    <div class="mail-status-row"><span class="label">Username</span><span class="value">{{ $mail['username'] ?: 'not set' }}</span></div>
                    <div class="mail-status-row">
                        <span class="label">Password</span>
                        <span class="value">{{ $mail['password_set'] ? 'set' : 'NOT SET' }}</span>
                    </div>
                @endif
                <div class="mail-status-row"><span class="label">From Address</span><span class="value">{{ $mail['from'] ?: 'not set' }}</span></div>
                <div class="mail-status-row">
                    <span class="label">Gmail API (OAuth)</span>
                    <span class="value">{{ $mail['gmail_api'] ? 'configured' : 'not configured' }}</span>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded-3 bg-light p-3 h-100 d-flex flex-column">
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-paper-plane text-primary me-2"></i> Send a Test Email</h6>
                    <p class="setting-hint mb-3">
                        Sends a real message through the same path used for staff verification codes.
                        If it fails you will get the exact reason and how to fix it.
                    </p>

                    <form action="{{ route('settings.test-mail') }}" method="POST" class="mt-auto">
                        @csrf
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="test_email" class="form-control" placeholder="you@gmail.com"
                                   value="{{ old('test_email', $mail['from']) }}" required>
                            <button type="submit" class="btn btn-primary fw-semibold px-3">Send Test</button>
                        </div>
                    </form>

                    <div class="setting-hint mt-3">
                        <strong>Tip:</strong> Gmail rejects normal account passwords over SMTP. Use a 16-character
                        App Password from
                        <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a>
                        (requires 2-Step Verification), and run <code>php artisan config:clear</code> after editing <code>.env</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
