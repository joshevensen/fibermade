@extends('emails.layout')

@section('content')
@php
    $storeName = $metadata['store_name'] ?? null;
    $ownerName = $metadata['owner_name'] ?? null;
@endphp
<p style="margin: 0 0 16px 0;">{{ $creatorName }} invited you to connect as a store on {{ config('app.name') }}.</p>
@if ($storeName || $ownerName)
    <p style="margin: 0 0 16px 0;">
        @if ($storeName)Store: {{ $storeName }}@endif
        @if ($storeName && $ownerName)<br>@endif
        @if ($ownerName)Owner: {{ $ownerName }}@endif
    </p>
@endif
<p style="margin: 0 0 24px 0;">Accept the invite to get started.</p>
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td style="border-radius: 6px; background-color: #4f46e5;">
            <a href="{{ $acceptUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">Accept invite</a>
        </td>
    </tr>
</table>
<p style="margin: 24px 0 0 0; font-size: 14px; color: #6b7280;">If you didn’t expect this invite, you can ignore this email.</p>
@endsection
