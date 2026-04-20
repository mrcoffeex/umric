<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Reset Your Password</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        .wrapper { width: 100%; background-color: #f4f6f8; padding: 40px 16px; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; }
        .header { background-color: #1a1a2e; padding: 32px 40px; }
        .header-label { font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #8b93a7; margin-bottom: 6px; }
        .header-title { font-size: 22px; font-weight: 700; color: #ffffff; line-height: 1.3; }
        .body { padding: 36px 40px; }
        .intro { font-size: 15px; color: #4b5563; line-height: 1.6; margin-bottom: 28px; }
        .detail-card { background-color: #f8f9fb; border-radius: 6px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 28px; }
        .detail-row { display: flex; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { width: 130px; min-width: 130px; padding: 13px 16px; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; color: #9ca3af; background-color: #f0f2f5; }
        .detail-value { padding: 13px 16px; font-size: 14px; color: #111827; font-weight: 500; flex: 1; word-break: break-word; }
        .cta { text-align: center; margin-bottom: 28px; }
        .cta a { display: inline-block; background-color: #1a1a2e; color: #ffffff !important; font-size: 14px; font-weight: 600; letter-spacing: 0.5px; padding: 13px 32px; border-radius: 6px; text-decoration: none; }
        .warning-section { background-color: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 0 6px 6px 0; padding: 16px 20px; margin-bottom: 28px; }
        .warning-heading { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #92400e; margin-bottom: 8px; }
        .warning-body { font-size: 14px; color: #78350f; line-height: 1.6; }
        .divider { height: 1px; background-color: #e5e7eb; margin: 28px 0; }
        .url-fallback { background-color: #f8f9fb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 16px; margin-top: 16px; }
        .url-fallback p { font-size: 12px; color: #9ca3af; margin-bottom: 6px; }
        .url-fallback a { font-size: 12px; color: #4b5563; word-break: break-all; text-decoration: underline; }
        .footer { padding: 0 40px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.7; }

        @media only screen and (max-width: 600px) {
            .header, .body { padding-left: 24px; padding-right: 24px; }
            .footer { padding-left: 24px; padding-right: 24px; }
            .detail-label { width: 100px; min-width: 100px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        {{-- Header --}}
        <div class="header">
            <div class="header-label">{{ config('app.name') }}</div>
            <div class="header-title">Password<br>Reset Request</div>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="intro">
                Hi <strong>{{ $userName }}</strong>, we received a request to reset the password for your account.
                Click the button below to choose a new password.
            </p>

            {{-- Detail card --}}
            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-label">Account</div>
                    <div class="detail-value">{{ $userName }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Requested</div>
                    <div class="detail-value" style="color: #6b7280;">{{ now()->format('F j, Y \a\t g:i A') }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Expires in</div>
                    <div class="detail-value">{{ $expiresIn }} minutes</div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="cta">
                <a href="{{ $resetUrl }}">Reset Password &rarr;</a>
            </div>

            {{-- Warning --}}
            <div class="warning-section">
                <div class="warning-heading">Didn't request this?</div>
                <div class="warning-body">
                    If you didn't request a password reset, no action is needed — your account is safe.
                    This link will expire automatically in {{ $expiresIn }} minutes.
                </div>
            </div>

            <div class="divider"></div>

            {{-- URL fallback --}}
            <div class="url-fallback">
                <p>If the button above doesn't work, copy and paste this link into your browser:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                You received this email because a password reset was requested<br>
                for your account at <strong>{{ config('app.name') }}</strong>.
            </p>
        </div>

    </div>
</div>
</body>
</html>
