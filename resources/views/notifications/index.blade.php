@extends('layouts.app')

@section('title', 'Notifications - Captain J POS')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark">Notifications</h3>
            <p class="text-secondary small m-0">System alerts including low stock warnings.</p>
        </div>
        @if($notifications->where('is_read', false)->count() > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="fa-solid fa-check-double me-1"></i> Mark All as Read
            </button>
        </form>
        @endif
    </div>

    <div class="card card-custom p-4">
        @forelse($notifications as $notif)
        <div class="d-flex align-items-start gap-3 p-3 mb-2 rounded-3 border {{ $notif->is_read ? 'bg-light' : 'bg-warning-subtle border-warning' }}">
            <div class="pt-1">
                @if($notif->type === 'low_stock')
                    <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                @else
                    <i class="fa-solid fa-bell text-info fs-5"></i>
                @endif
            </div>
            <div style="flex: 1;">
                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $notif->message }}</div>
                <div class="text-muted small mt-1">{{ $notif->created_at->diffForHumans() }} &mdash; {{ $notif->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div class="flex-shrink-0">
                @if(!$notif->is_read)
                <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Mark as Read">
                        <i class="fa-solid fa-check"></i>
                    </button>
                </form>
                @else
                <span class="badge bg-secondary-subtle text-secondary small">Read</span>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-bell-slash fs-1 mb-3 opacity-50"></i>
            <p class="m-0 fw-semibold">No notifications yet.</p>
            <span class="small">Low stock alerts will appear here when inventory is running low.</span>
        </div>
        @endforelse

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
