@extends('emails.layout')

@section('content')
<p style="margin: 0 0 16px 0;">Hi {{ $userName }},</p>
<p style="margin: 0 0 16px 0;">Your Creator subscription has ended. You have <strong>{{ $daysRemaining }} days</strong> left to reactivate and keep your catalog, orders, and settings. After that, your account and data will be permanently deleted.</p>
<p style="margin: 0 0 24px 0;">Reactivate anytime before then to pick up where you left off.</p>
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td style="border-radius: 6px; background-color: #4f46e5;">
            <a href="{{ $reactivateUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">Reactivate subscription</a>
        </td>
    </tr>
</table>
<p style="margin: 24px 0 0 0; font-size: 14px; color: #6b7280;">If you don't want to continue, you can ignore this email. Your data will be deleted after the retention period.</p>
@endsection
