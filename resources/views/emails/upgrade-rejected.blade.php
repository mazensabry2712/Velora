<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Request Update</title>
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .message {
            color: #475569;
            margin-bottom: 30px;
            font-size: 15px;
        }
        .reason-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .reason-box h3 {
            margin: 0 0 10px 0;
            color: #991b1b;
            font-size: 16px;
            font-weight: 600;
        }
        .reason-box p {
            margin: 0;
            color: #7f1d1d;
            font-size: 14px;
            line-height: 1.6;
        }
        .plan-info {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .plan-info h4 {
            margin: 0 0 10px 0;
            color: #1e293b;
            font-size: 16px;
        }
        .plan-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: #475569;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            color: #1e40af;
        }
        .button {
            display: inline-block;
            background: #6366f1;
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background: #4f46e5;
        }
        .button-secondary {
            background: #64748b;
        }
        .button-secondary:hover {
            background: #475569;
        }
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">ℹ️</div>
            <h1>Update on Your Upgrade Request</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $upgradeRequest->requested_by_name }},
            </div>

            <div class="message">
                Thank you for your interest in upgrading your plan. After careful review, we're unable to approve your upgrade request at this time.
            </div>

            <div class="plan-info">
                <h4>Requested Upgrade Details:</h4>
                <div class="plan-row">
                    <span><strong>From:</strong></span>
                    <span>{{ $upgradeRequest->currentPlan->name ?? 'N/A' }} (${{ $upgradeRequest->currentPlan->price ?? '0' }}/mo)</span>
                </div>
                <div class="plan-row">
                    <span><strong>To:</strong></span>
                    <span>{{ $upgradeRequest->requestedPlan->name ?? 'N/A' }} (${{ $upgradeRequest->requestedPlan->price ?? '0' }}/mo)</span>
                </div>
                <div class="plan-row">
                    <span><strong>Request Date:</strong></span>
                    <span>{{ $upgradeRequest->created_at->format('M d, Y') }}</span>
                </div>
            </div>

            @if($upgradeRequest->admin_notes)
            <div class="reason-box">
                <h3>📋 Reason for Decision:</h3>
                <p>{{ $upgradeRequest->admin_notes }}</p>
            </div>
            @endif

            <div class="info-box">
                <strong>💡 What's Next?</strong><br>
                • Review the feedback provided above<br>
                • Contact our support team if you have questions<br>
                • You can submit a new upgrade request after addressing any concerns
            </div>

            <div class="actions">
                <a href="{{ config('app.url') }}/admin/subscription" class="button">
                    View Current Plan
                </a>
                <br><br>
                <a href="{{ config('app.url') }}/admin/subscription/upgrade" class="button button-secondary">
                    Explore Other Plans
                </a>
            </div>

            <div class="message" style="margin-top: 30px;">
                We appreciate your understanding. If you have any questions or would like to discuss this further, please don't hesitate to reach out to our support team.
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from Booking SaaS Platform.</p>
            <p>&copy; {{ date('Y') }} Booking SaaS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
