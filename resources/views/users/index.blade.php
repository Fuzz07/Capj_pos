@extends('layouts.app')

@section('title', 'Manage System Users - CAPTAiN J POS')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark">User Management</h3>
            <p class="text-secondary small m-0">Control administrator and staff access permissions.</p>
        </div>
        <button class="btn btn-primary fw-semibold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-user-plus me-2"></i> Add New User
        </button>
    </div>

    <!-- Users Table Card -->
    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Verification</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="fw-bold text-secondary">#{{ $u->id }}</td>
                        <td class="fw-bold text-dark">{{ $u->username }}</td>
                        <td>{{ $u->full_name ?? 'N/A' }}</td>
                        <td class="text-muted">{{ $u->email ?? 'N/A' }}</td>
                        <td>
                            @if(!empty($u->email))
                                @if($u->email_verified_at)
                                    <span class="badge bg-success-subtle text-success fw-semibold p-1 px-2">
                                        <i class="fa-solid fa-circle-check me-1"></i> Verified
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning fw-semibold p-1 px-2">
                                        <i class="fa-solid fa-circle-question me-1"></i> Unverified
                                    </span>
                                @endif
                            @else
                                <span class="text-secondary small">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($u->isAdmin())
                                <span class="badge badge-admin p-1 px-2">Administrator</span>
                            @else
                                <span class="badge badge-staff p-1 px-2">Staff</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $u->created_at->format('M d, Y') }}</td>
                        <td class="text-end text-nowrap">
                            @if(!empty($u->email) && !$u->email_verified_at)
                                <form action="{{ route('users.verify', $u->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-info me-1 py-1 px-2 fw-semibold" style="font-size: 0.75rem;" title="Send Verification OTP Code to this Email">
                                        <i class="fa-solid fa-paper-plane me-1"></i>
                                        @if(empty($u->email_verification_token))
                                            Send OTP
                                        @else
                                            Resend OTP
                                        @endif
                                    </button>
                                </form>
                            @endif

                            <button class="btn btn-sm btn-light text-primary border me-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal{{ $u->id }}" 
                                    title="Edit User">
                                <i class="fa-solid fa-user-pen"></i>
                            </button>

                            @if($u->id !== auth()->id())
                            <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline confirm-delete" data-confirm-message="Are you sure you want to delete user '{{ addslashes($u->username) }}'?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger border" title="Delete User">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            @endif

                            <!-- Modal: Edit User -->
                            <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-lg text-start">
                                    <form action="{{ route('users.update', $u->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit User: {{ $u->username }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-secondary">Username</label>
                                                        <input type="text" name="username" class="form-control" value="{{ $u->username }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-secondary">Full Name</label>
                                                        <input type="text" name="full_name" class="form-control" value="{{ $u->full_name }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-secondary">Email Address</label>
                                                        <input type="email" name="email" class="form-control" value="{{ $u->email }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-secondary">Role</label>
                                                        <select name="role" class="form-select" required>
                                                            <option value="staff" {{ $u->role === 'staff' ? 'selected' : '' }}>Staff</option>
                                                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Administrator</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-secondary">New Password (leave blank to keep unchanged)</label>
                                                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
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
                        <td colspan="7" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Modal: Add New User (expanded landscape form on desktop, stacked on mobile) -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="add_user">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i> Add New User</h5>
                        <p class="small text-secondary m-0 mt-1">Create a staff or administrator account and set their access permissions.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    @if($errors->any() && old('_form') === 'add_user')
                        <div class="alert alert-danger border-0 shadow-sm small py-2 mb-4">
                            <div class="fw-bold mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Please fix the following:</div>
                            @foreach($errors->all() as $error)
                                <div>&bull; {{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="row g-4">
                        <!-- Left: Account Information -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                <i class="fa-solid fa-id-card text-primary me-2"></i> Account Information
                            </h6>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0" placeholder="e.g. staff1" value="{{ old('_form') === 'add_user' ? old('username') : '' }}" required>
                                </div>
                                <div class="form-text small">Used to log in. Must be unique &mdash; no spaces.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-signature"></i></span>
                                    <input type="text" name="full_name" class="form-control border-start-0" placeholder="e.g. Maria Santos" value="{{ old('_form') === 'add_user' ? old('full_name') : '' }}">
                                </div>
                                <div class="form-text small">Shown on receipts and in the activity log.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0" placeholder="staff@captainj.com" value="{{ old('_form') === 'add_user' ? old('email') : '' }}">
                                </div>
                                <div class="form-text small">
                                    <i class="fa-solid fa-paper-plane text-info me-1"></i>
                                    Any email address works. A 6-digit verification code is sent here automatically. Leave blank to skip verification.
                                </div>
                            </div>
                        </div>

                        <!-- Right: Access & Security -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                <i class="fa-solid fa-shield-halved text-danger me-2"></i> Access &amp; Security
                            </h6>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Role <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user-shield"></i></span>
                                    <select name="role" id="newUserRole" class="form-select border-start-0" required>
                                        <option value="staff" {{ old('_form') === 'add_user' && old('role') === 'admin' ? '' : 'selected' }}>Staff</option>
                                        <option value="admin" {{ old('_form') === 'add_user' && old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                                    </select>
                                </div>
                                <div class="border rounded-3 bg-light p-3 mt-2">
                                    <div class="small fw-semibold text-secondary mb-2">This role can access:</div>
                                    <div class="d-flex flex-wrap gap-1" id="rolePermissionList"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-key"></i></span>
                                    <input type="password" name="password" id="newUserPassword" class="form-control border-start-0 border-end-0" placeholder="••••••••" autocomplete="new-password" required>
                                    <button type="button" class="input-group-text bg-light border-start-0 text-secondary" onclick="toggleNewUserPass()" style="cursor: pointer;" title="Show or hide password">
                                        <i class="fa-solid fa-eye" id="iconNewUserPass"></i>
                                    </button>
                                </div>
                                <div class="form-text small">Minimum 6 characters. Share this with the user so they can log in.</div>

                                @include('partials.password-strength', ['input' => 'newUserPassword'])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex flex-wrap justify-content-between gap-2">
                    <span class="small text-muted"><i class="fa-solid fa-circle-info text-primary me-1"></i> Fields marked <span class="text-danger">*</span> are required.</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            <i class="fa-solid fa-user-plus me-2"></i> Create User
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleNewUserPass() {
        const input = document.getElementById('newUserPassword');
        const icon = document.getElementById('iconNewUserPass');
        if (!input || !icon) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Show what each role unlocks, mirroring the sidebar permissions
        const permissions = {
            staff: ['Profile', 'Inventory (view &amp; restock)', 'Create Order', 'Orders'],
            admin: ['Profile', 'Dashboard', 'Inventory (full control)', 'User Management', 'Create Order', 'Orders', 'Activity Logs']
        };

        const roleSelect = document.getElementById('newUserRole');
        const list = document.getElementById('rolePermissionList');

        function renderPermissions() {
            if (!roleSelect || !list) return;
            const role = roleSelect.value;
            const badgeClass = role === 'admin' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary';
            list.innerHTML = permissions[role]
                .map(p => `<span class="badge ${badgeClass} fw-semibold">${p}</span>`)
                .join('');
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', renderPermissions);
            renderPermissions();
        }

        // Reopen the Add User form when it failed validation, so errors stay visible
        @if($errors->any() && old('_form') === 'add_user')
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        @endif
    });
</script>
@endpush
