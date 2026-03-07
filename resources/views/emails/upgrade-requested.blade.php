<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Request Received</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
        }
        .icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 32px;
            line-height: 64px;
            text-align: center;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .message {
            color: #475569;
            margin-bottom: 24px;
            font-size: 15px;
        }
        .plan-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 24px;
            margin: 24px 0;
        }
        .plan-box h3 {
            margin: 0 0 14px 0;
            color: #1e293b;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .plan-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
        }
        .plan-row:last-child {
            border-bottom: none;
        }
        .plan-row strong {
            color: #1e293b;
        }
        .badge-new {
            display: inline-block;
            background: #ede9fe;
            color: #6366f1;
            font-weight: 700;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 99px;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
            font-size: 14px;
            color: #1d4ed8;
        }
        .steps {
            margin: 0;
            padding-left: 20px;
            color: #475569;
            font-size: 14px;
        }
        .steps li {
            padding: 4px 0;
        }
        .button {
            display: inline-block;
            background: #6366f1;
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            background: #f8fafc;
            padding: 28px 30px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">📋</div>
            <h1>Upgrade Request Received!</h1>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $tenantName }},</div>

            <div class="message">
                We've received your upgrade request and our team will review it shortly.
                You'll receive an email once a decision has been made — usually within 1 business day.
            </div>

            <div class="plan-box">
                <h3>Request Summary</h3>
                <div class="plan-row">
                    <span>Current Plan</span>
                    <strong>{{ $currentPlanName }}</strong>
                </div>
                <div class="plan-row">
                    <span>Requested Plan</span>
                    <span>
                        <strong>{{ $requestedPlanName }}</strong>
                        <span class="badge-new">${{ $requestedPlanPrice }}/mo</span>
                    </span>
                </div>
                <div class="plan-row">
                    <span>Request Date</span>
                    <strong>{{ now()->format('M d, Y') }}</strong>
                </div>
            </div>

            <div class="info-box">
                <strong>⏳ What happens next?</strong>
                <ol class="steps">
                    <li>Our team reviews your request (typically within 1 business day)</li>
                    <li>You'll receive an approval or feedback email</li>
                    <li>If approved, your plan upgrades immediately with no interruption</li>
                </ol>
            </div>

            <div class="actions">
                <a href="{{ config('app.url') }}/admin/subscription" class="button">
                    View Your Subscription
                </a>
            </div>

            <div class="message" style="margin-top: 20px; font-size: 14px; color: #94a3b8;">
                If you have any questions, please contact our support team.
                We're here to help!
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
