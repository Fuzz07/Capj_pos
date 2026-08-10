@extends('layouts.app')

@section('title', 'Account Settings - Captain J POS')

@push('styles')
<style>
    .pw-meter-track {
        height: 6px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
    }
    .pw-meter-fill {
        height: 100%;
        width: 0;
        border-radius: 4px;
        transition: width 0.25s ease, background-color 0.25s ease;
    }
    .pw-strength-weak { background-color: #dc3545; }
    .pw-strength-moderate { background-color: #f0ad00; }
    .pw-strength-strong { background-color: #18b318; }
    .pw-text-weak { color: #dc3545; }
    .pw-text-moderate { color: #b47600; }
    .pw-text-strong { color: #158a15; }
    .pw-check {
        font-size: 0.72rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .pw-check.met {
        color: #158a15;
    }
    .pw-check i {
        width: 12px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-2" style="max-width: 1100px;">
    <!-- Page Title Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark d-flex align-items-center">
                <i class="fa-solid fa-user-gear text-primary me-2 fs-3"></i> Account Profile
            </h3>
            <p class="text-secondary small m-0">Manage your personal account details, contact email, and security credentials.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 fs-6 rounded-pill">
            <i class="fa-solid fa-user-shield me-1"></i> {{ ucfirst($user->role) }} Account
        </span>
    </div>

    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        
        <div class="row g-4">
            <!-- Left Landscape Column: Personal & Account Details -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 h-100 shadow-sm border-0">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 52px; height: 52px;">
                            {{ strtoupper(substr($user->username, 0, 1)) }}
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-dark">{{ $user->full_name ?: $user->username }}</h5>
                            <p class="small text-muted m-0">Role: <span class="badge bg-dark-subtle text-dark ms-1">{{ strtoupper($user->role) }}</span></p>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-id-card text-primary me-2"></i> Personal Details</h5>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Username (Read-Only)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" value="{{ $user->username }}" readonly disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-signature"></i></span>
                            <input type="text" name="full_name" class="form-control border-start-0" value="{{ old('full_name', $user->full_name) }}" placeholder="e.g. Maria Santos">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" value="{{ old('email', $user->email) }}" placeholder="staff@captainj.com">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Landscape Column: Security & Password Management -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 h-100 shadow-sm border-0">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-3 border-bottom">
                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 42px; height: 42px;">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Security & Password</h5>
                            <p class="small text-muted m-0">Leave empty if you don't wish to change your password.</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-key"></i></span>
                            <input type="password" name="current_password" id="current_password" class="form-control border-start-0 border-end-0" placeholder="••••••••">
                            <button type="button" class="input-group-text bg-light border-start-0 text-secondary" onclick="togglePass('current_password', 'iconCurrent')" style="cursor: pointer;">
                                <i class="fa-solid fa-eye" id="iconCurrent"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-shield-halved"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control border-start-0 border-end-0" placeholder="••••••••" autocomplete="new-password">
                            <button type="button" class="input-group-text bg-light border-start-0 text-secondary" onclick="togglePass('new_password', 'iconNew')" style="cursor: pointer;">
                                <i class="fa-solid fa-eye" id="iconNew"></i>
                            </button>
                        </div>

                        <!-- Password Strength Suggestion -->
                        <div id="pwStrengthBox" class="mt-2 d-none">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small fw-semibold text-secondary">Password Strength</span>
                                <span class="small fw-bold" id="pwStrengthLabel">—</span>
                            </div>
                            <div class="pw-meter-track mb-2">
                                <div class="pw-meter-fill" id="pwStrengthFill"></div>
                            </div>
                            <div class="row g-1" id="pwChecklist">
                                <div class="col-6"><div class="pw-check" data-rule="length"><i class="fa-regular fa-circle"></i> At least 8 characters</div></div>
                                <div class="col-6"><div class="pw-check" data-rule="case"><i class="fa-regular fa-circle"></i> Upper &amp; lowercase</div></div>
                                <div class="col-6"><div class="pw-check" data-rule="number"><i class="fa-regular fa-circle"></i> At least 1 number</div></div>
                                <div class="col-6"><div class="pw-check" data-rule="symbol"><i class="fa-regular fa-circle"></i> A symbol (!&#64;#$)</div></div>
                            </div>
                            <p class="small text-muted mt-2 mb-0" id="pwSuggestion"></p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-circle-check"></i></span>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control border-start-0 border-end-0" placeholder="••••••••" autocomplete="new-password">
                            <button type="button" class="input-group-text bg-light border-start-0 text-secondary" onclick="togglePass('new_password_confirmation', 'iconConfirm')" style="cursor: pointer;">
                                <i class="fa-solid fa-eye" id="iconConfirm"></i>
                            </button>
                        </div>
                        <p class="small mt-2 mb-0 d-none" id="pwMatchNote"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Landscape Action Footer Bar -->
        <div class="mt-4 card card-custom p-3 shadow-sm border-0 d-flex flex-row flex-wrap align-items-center justify-content-between gap-2">
            <span class="small text-muted"><i class="fa-solid fa-circle-info text-primary me-1"></i> Make sure to review all changes before saving.</span>
            <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                <i class="fa-solid fa-floppy-disk me-2"></i> Save Account Changes
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Live password strength suggestion: Weak / Moderate / Strong
(function () {
    const input = document.getElementById('new_password');
    const confirmInput = document.getElementById('new_password_confirmation');
    const box = document.getElementById('pwStrengthBox');
    const fill = document.getElementById('pwStrengthFill');
    const label = document.getElementById('pwStrengthLabel');
    const suggestion = document.getElementById('pwSuggestion');
    const matchNote = document.getElementById('pwMatchNote');
    if (!input || !box) return;

    const rules = {
        length: pw => pw.length >= 8,
        case: pw => /[a-z]/.test(pw) && /[A-Z]/.test(pw),
        number: pw => /[0-9]/.test(pw),
        symbol: pw => /[^A-Za-z0-9]/.test(pw)
    };

    const tips = {
        length: 'make it at least 8 characters',
        case: 'mix uppercase and lowercase letters',
        number: 'add a number',
        symbol: 'add a symbol like ! @ # $'
    };

    function evaluate() {
        const pw = input.value;

        if (pw === '') {
            box.classList.add('d-none');
            checkMatch();
            return;
        }
        box.classList.remove('d-none');

        let passed = 0;
        const missing = [];
        document.querySelectorAll('#pwChecklist .pw-check').forEach(el => {
            const rule = el.dataset.rule;
            const ok = rules[rule](pw);
            const icon = el.querySelector('i');
            el.classList.toggle('met', ok);
            icon.className = ok ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
            if (ok) { passed++; } else { missing.push(tips[rule]); }
        });

        // Very short passwords are always Weak regardless of variety
        let level;
        if (pw.length < 6 || passed <= 2) {
            level = { name: 'Weak', pct: 33, cls: 'pw-strength-weak', text: 'pw-text-weak' };
        } else if (passed === 3) {
            level = { name: 'Moderate', pct: 66, cls: 'pw-strength-moderate', text: 'pw-text-moderate' };
        } else {
            level = { name: 'Strong', pct: 100, cls: 'pw-strength-strong', text: 'pw-text-strong' };
        }

        fill.style.width = level.pct + '%';
        fill.className = 'pw-meter-fill ' + level.cls;
        label.textContent = level.name;
        label.className = 'small fw-bold ' + level.text;

        if (missing.length === 0) {
            suggestion.textContent = 'Great! This is a strong password.';
        } else {
            suggestion.textContent = 'Suggestion: ' + missing.join(', ') + '.';
        }

        checkMatch();
    }

    function checkMatch() {
        if (!confirmInput || !matchNote) return;
        if (confirmInput.value === '' || input.value === '') {
            matchNote.classList.add('d-none');
            return;
        }
        matchNote.classList.remove('d-none');
        if (confirmInput.value === input.value) {
            matchNote.textContent = 'Passwords match.';
            matchNote.className = 'small mt-2 mb-0 pw-text-strong fw-semibold';
        } else {
            matchNote.textContent = 'Passwords do not match yet.';
            matchNote.className = 'small mt-2 mb-0 pw-text-weak fw-semibold';
        }
    }

    input.addEventListener('input', evaluate);
    if (confirmInput) confirmInput.addEventListener('input', checkMatch);
})();
</script>
@endpush
