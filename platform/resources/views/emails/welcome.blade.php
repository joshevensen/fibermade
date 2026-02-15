@extends('emails.layout')

@section('content')
<p style="margin: 0 0 16px 0;">Hi {{ $userName }},</p>
<p style="margin: 0 0 16px 0;">Your Creator account is set up. You can log in to manage your catalog, wholesale orders, and stores.</p>
<p style="margin: 0 0 24px 0;">Get started by signing in:</p>
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td style="border-radius: 6px; background-color: #4f46e5;">
            <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">Log in</a>
        </td>
    </tr>
</table>
<p style="margin: 24px 0 0 0; font-size: 14px; color: #6b7280;">If you have any questions, reply to this email.</p>
@endsection
