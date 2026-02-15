@extends('emails.layout')

@section('content')
@php
    $storeName = $store->name ?? 'A store';
    $ownerName = $store->owner_name ?? null;
@endphp
<p style="margin: 0 0 16px 0;">Great news! {{ $storeName }} has accepted your invite and is now connected on {{ config('app.name') }}.</p>
@if ($ownerName)
    <p style="margin: 0 0 16px 0;">Store owner: {{ $ownerName }}</p>
@endif
<p style="margin: 0 0 24px 0;">View your stores below.</p>
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td style="border-radius: 6px; background-color: #4f46e5;">
            <a href="{{ $storesUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">View your stores</a>
        </td>
    </tr>
</table>
@endsection
