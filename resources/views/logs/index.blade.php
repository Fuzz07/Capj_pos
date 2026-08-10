@extends('layouts.app')

@section('title', 'System & User Activity Logs - CAPTAiN J')

@push('styles')
    <style>
        .badge-action {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold m-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> User Activity Logs</h4>
            <p class="text-muted small m-0">Track all user transactions, order activities, inventory updates, and logins.
            </p>
        </div>
        <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-rotate-right me-1"></i> Refresh
        </a>
    </div>

    <div class="card border-0 shadow-sm p-3">
        <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <input type="text" id="logSearch" class="form-control form-control-sm"
                placeholder="Search logs (e.g. ORDER, INVENTORY, admin)..." style="max-width: 320px;">
            <span class="text-muted small">Showing {{ $logs->count() }} of {{ $logs->total() }} entries</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-light">
                    <tr>
                        <th style="width: 170px;">Date & Time</th>
                        <th style="width: 160px;">User</th>
                        <th style="width: 170px;">Action</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted small">{{ $log->created_at->format('M j, Y • g:i:s A') }}</td>
                            <td>
                                <div class="fw-bold text-dark small">
                                    {{ $log->user->full_name ?? ($log->user->username ?? 'System') }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{{ ucfirst($log->user->role ?? 'N/A') }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $badgeClass = 'bg-secondary';
                                    if (str_contains($log->action, 'ORDER'))
                                        $badgeClass = 'bg-success';
                                    elseif (str_contains($log->action, 'INVENTORY') || str_contains($log->action, 'STOCK'))
                                        $badgeClass = 'bg-info text-dark';
                                    elseif (str_contains($log->action, 'USER'))
                                        $badgeClass = 'bg-warning text-dark';
                                    elseif (str_contains($log->action, 'LOGIN') || str_contains($log->action, 'LOGOUT'))
                                        $badgeClass = 'bg-primary';
                                @endphp
                                <span class="badge {{ $badgeClass }} badge-action">{{ $log->action }}</span>
                            </td>
                            <td class="text-dark small">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-clipboard fs-2 mb-2 opacity-50"></i>
                                <p class="m-0">No transaction logs recorded yet.</p>
                                <span class="small">Transactions will automatically appear here as users perform actions.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('logSearch').addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#logsTableBody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>
@endpush