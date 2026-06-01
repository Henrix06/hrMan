<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ $tenantName }}</title>
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
            background: #4f46e5;
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
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
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
        <h1>Welcome Aboard!</h1>
    </div>
    <div class="content">
        <h2>Hi {{ $userName }},</h2>
        <p>Thank you for signing up to {{ $tenantName }}! We're excited to have you on board.</p>
        <p>Your account has been created successfully and you're on the <strong>{{ $planName }}</strong> plan.</p>
        <p>You can access your workspace here:</p>
        <a href="{{ $tenantUrl }}" class="button">Go to My Workspace</a>
        <p>If you have any questions, feel free to reach out to us anytime.</p>
        <p>Best regards,<br>The {{ $tenantName }} Team</p>
    </div>
    <div class="footer">
        <p>This email was sent to {{ $user->email }}. If you didn't create this account, you can ignore this email.</p>
    </div>
</body>
</html>
