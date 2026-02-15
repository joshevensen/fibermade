@extends('emails.layout')

@section('content')
@php
    $creatorName = $order->account?->creator?->name ?? config('app.name');
    $orderDate = $order->order_date?->format('F j, Y') ?? '';
@endphp
<p style="margin: 0 0 16px 0;">Thank you for your order from {{ $creatorName }}.</p>
<p style="margin: 0 0 16px 0;">Order date: {{ $orderDate }}</p>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 24px 0; border-collapse: collapse;">
    <thead>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <th style="padding: 12px 0; text-align: left; font-size: 14px; color: #6b7280;">Item</th>
            <th style="padding: 12px 0; text-align: right; font-size: 14px; color: #6b7280;">Qty</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->orderItems ?? [] as $item)
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 12px 0; font-size: 15px;">
                {{ $item->colorway?->name ?? 'Colorway' }} — {{ $item->base?->descriptor ?? 'Base' }}
            </td>
            <td style="padding: 12px 0; text-align: right; font-size: 15px;">{{ $item->quantity }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="padding: 8px 0; font-size: 15px; color: #6b7280;">Subtotal</td>
        <td style="padding: 8px 0; text-align: right; font-size: 15px;">${{ number_format((float) ($order->subtotal_amount ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td style="padding: 8px 0; font-size: 16px; font-weight: 600;">Total</td>
        <td style="padding: 8px 0; text-align: right; font-size: 16px; font-weight: 600;">${{ number_format((float) ($order->total_amount ?? 0), 2) }}</td>
    </tr>
</table>
@endsection
