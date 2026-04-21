<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Faculty Registration Status</title>
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
        .header { padding: 32px 40px; }
        .header-label { font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #8b93a7; margin-bottom: 6px; }
        .header-title { font-size: 22px; font-weight: 700; color: #ffffff; line-height: 1.3; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 15px; color: #374151; line-height: 1.6; margin-bottom: 20px; }
        .intro { font-size: 15px; color: #4b5563; line-height: 1.6; margin-bottom: 28px; }
        .status-box { border-radius: 8px; padding: 20px 24px; margin-bottom: 28px; border: 1px solid; }
        .status-box.approved { background-color: #f0fdf4; border-color: #bbf7d0; }
        .status-box.rejected { background-color: #fef9ee; border-color: #fde68a; }
        .status-icon { font-size: 28px; margin-bottom: 10px; }
        .status-title { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .status-title.approved { color: #15803d; }
        .status-title.rejected { color: #92400e; }
        .status-body { font-size: 14px; line-height: 1.6; }
        .status-body.approved { color: #166534; }
        .status-body.rejected { color: #78350f; }
        .cta { text-align: center; margin-bottom: 8px; }
        .cta a { display: inline-block; font-size: 14px; font-weight: 600; letter-spacing: 0.5px; padding: 13px 32px; border-radius: 6px; text-decoration: none; }
        .cta-approved { background-color: #1a1a2e; color: #ffffff !important; }
        .cta-rejected { background-color: #f97316; color: #ffffff !important; }
        .divider { height: 1px; background-color: #e5e7eb; margin: 28px 0; }
        .footer { padding: 0 40px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.7; }
        .footer a { color: #6b7280; text-decoration: underline; }

        @media only screen and (max-width: 600px) {
            .header, .body { padding-left: 24px; padding-right: 24px; }
            .footer { padding-left: 24px; padding-right: 24px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        {{-- Header --}}
        <div class="header" style="background-color: #1a1a2e;">
            <div class="header-label">{{ config('app.name') }}</div>
            <div class="header-title">Faculty Registration<br>Status Update</div>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">Hi {{ $user->name }},</p>

            @if ($status === 'approved')
                <p class="intro">
                    Great news! Your faculty registration request has been reviewed
                    by our administrators.
                </p>

                <div class="status-box approved">
                    <div class="status-icon">✅</div>
                    <div class="status-title approved">Registration Approved</div>
                    <div class="status-body approved">
                        Your account has been approved. You can now log in to
                        {{ config('app.name') }} and access all faculty features,
                        including advising research papers, managing classes, and
                        more.
                    </div>
                </div>

                <div class="cta">
                    <a href="{{ route('login') }}" class="cta-approved">Log In to Your Account &rarr;</a>
                </div>

            @else
                <p class="intro">
                    Thank you for registering as a faculty member on
                    {{ config('app.name') }}. We have an update regarding your
                    registration request.
                </p>

                <div class="status-box rejected">
                    <div class="status-icon">⚠️</div>
                    <div class="status-title rejected">Registration Not Approved</div>
                    <div class="status-body rejected">
                        Unfortunately, your faculty registration request was not
                        approved at this time. If you believe this is a mistake or
                        need further assistance, please contact your administrator.
                    </div>
                </div>

                <div class="cta">
                    <a href="{{ route('registration.pending') }}" class="cta-rejected">Contact Administrator &rarr;</a>
                </div>
            @endif

            <div class="divider"></div>

            <p style="font-size: 13px; color: #9ca3af; text-align: center;">
                If you did not register on {{ config('app.name') }}, please ignore
                this email.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br />
                University of Mindanao Research and Innovation Center
            </p>
        </div>

    </div>
</div>
</body>
</html>
