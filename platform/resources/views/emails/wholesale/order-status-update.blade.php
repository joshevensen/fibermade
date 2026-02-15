@extends('emails.layout')

@section('content')
@php
    $creatorName = $order->account?->creator?->name ?? config('app.name');
@endphp
<p style="margin: 0 0 16px 0;">Order #{{ $order->id }} has been updated.</p>
<p style="margin: 0 0 16px 0;"><strong>Status:</strong> {{ $statusLabel ?? ucfirst($order->status->value) }}</p>
<p style="margin: 0 0 16px 0;">{{ $statusMessage ?? '' }}</p>
<p style="margin: 24px 0 0 0; font-size: 14px; color: #6b7280;">If you have questions, contact {{ $creatorName }}.</p>
@endsection
