@extends('layouts.app')

@section('title', 'Manage System Users - CAPTAiN J POS')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
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
                                    <button type="submit" class="btn btn-sm btn-outline-info me-1 py-1 px-2 fw-semibold" style="font-size: 0.75rem;" title="Send Email Verification Link">
                                        <i class="fa-solid fa-paper-plane me-1"></i>
                                        @if(empty($u->email_verification_token))
                                            Send Verify
                                        @else
                                            Resend Verify
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
                                <div class="modal-dialog modal-dialog-centered text-start">
                                    <form action="{{ route('users.update', $u->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit User: {{ $u->username }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Username</label>
                                                    <input type="text" name="username" class="form-control" value="{{ $u->username }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Full Name</label>
                                                    <input type="text" name="full_name" class="form-control" value="{{ $u->full_name }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Email Address</label>
                                                    <input type="email" name="email" class="form-control" value="{{ $u->email }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Role</label>
                                                    <select name="role" class="form-select" required>
                                                        <option value="staff" {{ $u->role === 'staff' ? 'selected' : '' }}>Staff</option>
                                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Administrator</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">New Password (leave blank to keep unchanged)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
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

<!-- Modal: Add New User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. staff1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Maria Santos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="staff@captainj.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="staff" selected>Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
