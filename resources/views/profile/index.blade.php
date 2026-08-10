@extends('layouts.app')

@section('title', 'Account Settings - Captain J POS')

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
                            <input type="password" name="new_password" id="new_password" class="form-control border-start-0 border-end-0" placeholder="••••••••">
                            <button type="button" class="input-group-text bg-light border-start-0 text-secondary" onclick="togglePass('new_password', 'iconNew')" style="cursor: pointer;">
                                <i class="fa-solid fa-eye" id="iconNew"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-circle-check"></i></span>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control border-start-0 border-end-0" placeholder="••••••••">
                            <button type="button" class="input-group-text bg-light border-start-0 text-secondary" onclick="togglePass('new_password_confirmation', 'iconConfirm')" style="cursor: pointer;">
                                <i class="fa-solid fa-eye" id="iconConfirm"></i>
                            </button>
                        </div>
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
</script>
@endpush
