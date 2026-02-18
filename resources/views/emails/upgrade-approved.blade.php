<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Approved</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .checkmark {
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
        .plan-box {
            background: #f1f5f9;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .plan-box h3 {
            margin: 0 0 10px 0;
            color: #1e293b;
            font-size: 18px;
        }
        .plan-details {
            display: grid;
            gap: 10px;
            margin-top: 15px;
        }
        .plan-detail {
            display: flex;
            align-items: center;
            color: #475569;
            font-size: 14px;
        }
        .plan-detail svg {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            color: #10b981;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            color: #1e40af;
        }
        .button {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background: #059669;
        }
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
        }
        .admin-notes {
            background: #fef9c3;
            border-left: 4px solid #eab308;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .admin-notes strong {
            display: block;
            color: #854d0e;
            margin-bottom: 8px;
        }
        .admin-notes p {
            margin: 0;
            color: #713f12;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="checkmark">✅</div>
            <h1>Upgrade Request Approved!</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hello {{ $upgradeRequest->requested_by_name }},
            </div>

            <div class="message">
                Great news! Your upgrade request has been approved by our team. Your account has been successfully upgraded to the new plan.
            </div>

            <div class="plan-box">
                <h3>🎉 Your New Plan: {{ $upgradeRequest->requestedPlan->name }}</h3>
                <div class="plan-details">
                    <div class="plan-detail">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>{{ $upgradeRequest->requestedPlan->max_users }}</strong> Users</span>
                    </div>
                    <div class="plan-detail">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>{{ $upgradeRequest->requestedPlan->max_appointments }}</strong> Appointments per month</span>
                    </div>
                    <div class="plan-detail">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>{{ $upgradeRequest->requestedPlan->storage_limit }}GB</strong> Storage</span>
                    </div>
                    <div class="plan-detail">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>${{ $upgradeRequest->requestedPlan->price }}</strong> per month</span>
                    </div>
                </div>
            </div>

            @if($upgradeRequest->admin_notes)
            <div class="admin-notes">
                <strong>📝 Note from Admin:</strong>
                <p>{{ $upgradeRequest->admin_notes }}</p>
            </div>
            @endif

            <div class="info-box">
                <strong>📅 Subscription Details:</strong><br>
                Start Date: {{ $newSubscription->starts_at->format('M d, Y') }}<br>
                Status: Active ✓
            </div>

            <center>
                <a href="{{ config('app.url') }}/admin/subscription" class="button">
                    View Your Subscription
                </a>
            </center>

            <div class="message" style="margin-top: 30px;">
                If you have any questions or need assistance, please don't hesitate to contact our support team.
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from Booking SaaS Platform.</p>
            <p>&copy; {{ date('Y') }} Booking SaaS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
