<!DOCTYPE html>
<html>
<head>
    <title>You've been invited to {{ $tenantName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #8B5E3C;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background: #8B5E3C;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .meta {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>You're Invited</h1>
    </div>
    <div class="content">
        <h2>Hi{{ $invitation->name ? ' ' . $invitation->name : '' }},</h2>

        <p>
            <strong>{{ $inviterName }}</strong> has invited you to join the
            <strong>{{ $tenantName }}</strong> workspace on uMsizi.
        </p>

        <div class="meta">
            <div><strong>Workspace:</strong> {{ $tenantName }}</div>
            <div><strong>Your role:</strong> {{ $role }}</div>
            <div><strong>Invited email:</strong> {{ $invitation->email }}</div>
        </div>

        <p>Set your password to activate your account:</p>
        <a href="{{ $acceptUrl }}" class="button">Accept Invitation</a>

        @if($expiresAt)
        <p>This invitation expires on {{ $expiresAt->format('j F Y') }}.</p>
        @endif

        <p>If the button doesn't work, paste this link into your browser:</p>
        <p style="word-break: break-all; font-size: 12px; color: #6b7280;">{{ $acceptUrl }}</p>

        <p>Best regards,<br>The uMsizi Team</p>
    </div>
    <div class="footer">
        <p>This email was sent to {{ $invitation->email }}. If you weren't expecting it, you can ignore it.</p>
    </div>
</body>
</html>
