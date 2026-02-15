<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('app.name') }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #374151; background-color: #f3f4f6;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
    <tr>
        <td style="padding: 32px 24px;" align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                {{-- Header --}}
                <tr>
                    <td style="padding: 32px 32px 24px 32px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                        @php
                            $creatorForward = $creatorForward ?? false;
                            $creatorName = $creatorName ?? null;
                            $logoUrl = rtrim(config('app.url'), '/') . '/logo.png';
                        @endphp
                        @if ($creatorForward && $creatorName)
                            <p style="margin: 0 0 8px 0; font-size: 14px; color: #6b7280;">From {{ $creatorName }}</p>
                            <a href="{{ config('app.url') }}" style="display: inline-block;">
                                <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" width="120" height="40" style="display: block; max-width: 120px; height: auto;" />
                            </a>
                        @else
                            <a href="{{ config('app.url') }}" style="display: inline-block;">
                                <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" width="160" height="53" style="display: block; max-width: 160px; height: auto;" />
                            </a>
                        @endif
                    </td>
                </tr>
                {{-- Main content --}}
                <tr>
                    <td style="padding: 32px;">
                        @yield('content')
                    </td>
                </tr>
                {{-- Footer --}}
                <tr>
                    <td style="padding: 24px 32px 32px 32px; text-align: center; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280;">
                        @if ($creatorForward && $creatorName)
                            <p style="margin: 0 0 4px 0;">Sent via <a href="{{ config('app.url') }}" style="color: #4f46e5; text-decoration: none;">Fibermade</a></p>
                            <p style="margin: 0;">Fibermade — A commerce platform for the fiber community</p>
                        @else
                            <p style="margin: 0 0 4px 0;"><a href="{{ config('app.url') }}" style="color: #4f46e5; text-decoration: none;">Fibermade</a></p>
                            <p style="margin: 0;">Fibermade — A commerce platform for the fiber community</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
