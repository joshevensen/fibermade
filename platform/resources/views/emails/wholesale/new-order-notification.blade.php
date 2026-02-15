@extends('emails.layout')

@section('content')
@php
    $storeName = $order->orderable?->name ?? 'A store';
    $itemCount = $order->orderItems?->sum('quantity') ?? 0;
    $total = $order->total_amount ?? $order->subtotal_amount ?? 0;
@endphp
<p style="margin: 0 0 16px 0;">You have received a new wholesale order from {{ $storeName }}.</p>
<p style="margin: 0 0 16px 0;">{{ $itemCount }} skein(s) — ${{ number_format((float) $total, 2) }} total</p>
<p style="margin: 0 0 24px 0;">View and manage the order below.</p>
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td style="border-radius: 6px; background-color: #4f46e5;">
            <a href="{{ $orderUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">View order</a>
        </td>
    </tr>
</table>
@endsection
