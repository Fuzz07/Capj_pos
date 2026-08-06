@extends('layouts.app')

@section('title', 'GCash Payment - Order #' . $order->id)

@section('content')
<div class="container px-4 text-center" style="max-width: 480px;">
    <div class="card card-custom p-4">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-qrcode text-primary me-2"></i> Scan GCash QR Code</h4>
        <p class="text-secondary small mb-3">Order #{{ $order->id }} — Total Amount: <strong class="text-primary fs-5">₱{{ number_format($order->total_amount, 2) }}</strong></p>

        <div class="p-3 bg-light rounded border mb-3">
            <img src="{{ asset($qrImage) }}" alt="GCash QR Code" class="img-fluid rounded" style="max-height: 280px;" onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=GCash+Payment+Order+{{ $order->id }}';">
            <div class="fw-bold mt-2">GCash Number: <span class="text-primary">{{ $gcashNumber }}</span></div>
        </div>

        <p class="small text-muted mb-4">Please scan the QR code above with your GCash app and complete the transaction. Cashier will confirm receipt.</p>

        <div class="d-flex gap-2 justify-content-center">
            <a href="{{ route('pos.receipt', $order->id) }}" class="btn btn-primary fw-bold px-4">Done / View Receipt</a>
            <a href="{{ route('pos.index') }}" class="btn btn-light border">Back to POS</a>
        </div>
    </div>
</div>
@endsection
