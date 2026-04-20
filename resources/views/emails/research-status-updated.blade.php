<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Research Paper Status Update</title>
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
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; background-color: #e0f2fe; color: #0369a1; }
        .notes-section { background-color: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 0 6px 6px 0; padding: 16px 20px; margin-bottom: 28px; }
        .notes-heading { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #92400e; margin-bottom: 8px; }
        .notes-body { font-size: 14px; color: #78350f; line-height: 1.6; }
        .cta { text-align: center; margin-bottom: 8px; }
        .cta a { display: inline-block; background-color: #1a1a2e; color: #ffffff !important; font-size: 14px; font-weight: 600; letter-spacing: 0.5px; padding: 13px 32px; border-radius: 6px; text-decoration: none; }
        .divider { height: 1px; background-color: #e5e7eb; margin: 28px 0; }
        .footer { padding: 0 40px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.7; }
        .footer a { color: #6b7280; text-decoration: underline; }

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
            <div class="header-title">Research Paper<br>Status Update</div>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="intro">
                Your research paper has received a new status update. Please review the details below.
            </p>

            {{-- Detail card --}}
            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-label">Title</div>
                    <div class="detail-value">{{ $paper->title }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Tracking ID</div>
                    <div class="detail-value" style="font-family: monospace; font-size: 13px; color: #374151;">{{ $paper->tracking_id }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Step</div>
                    <div class="detail-value">{{ $stepLabel }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Updated</div>
                    <div class="detail-value" style="color: #6b7280;">{{ now()->format('F j, Y \a\t g:i A') }}</div>
                </div>
            </div>

            @if ($notes)
            {{-- Notes block --}}
            <div class="notes-section">
                <div class="notes-heading">Reviewer Notes</div>
                <div class="notes-body">{{ $notes }}</div>
            </div>
            @endif

            {{-- CTA --}}
            <div class="cta">
                <a href="{{ route('papers.publicTracking', $paper->tracking_id) }}">
                    Track Paper Progress &rarr;
                </a>
            </div>

            <div class="divider"></div>

            <p style="font-size: 13px; color: #6b7280; line-height: 1.6;">
                If you have questions about your paper's progress, please contact your adviser or the research coordinator directly.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                You received this email because you are listed as a proponent<br>
                on a research paper at <strong>{{ config('app.name') }}</strong>.
            </p>
        </div>

    </div>
</div>
</body>
</html>
