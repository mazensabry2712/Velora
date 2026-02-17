<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Report - {{ ucfirst($period) }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #007bff;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .stat-box {
            display: table-cell;
            width: 20%;
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
        }
        .stat-box h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #007bff;
        }
        .stat-box p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        table thead {
            background-color: #007bff;
            color: white;
        }
        table th,
        table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .status-confirmed {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: #333;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .status-completed {
            background-color: #17a2b8;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .summary-section {
            margin-bottom: 25px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-section h2 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $tenant->name }}</h1>
        <h2 style="margin: 10px 0;">Appointments Report</h2>
        <p><strong>Period:</strong> {{ ucfirst($period) }}</p>
        <p><strong>Generated:</strong> {{ $generated_at->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Statistics Summary -->
    <div class="stats-grid">
        <div class="stat-box">
            <h3>{{ $stats['total'] }}</h3>
            <p>Total Appointments</p>
        </div>
        <div class="stat-box">
            <h3>{{ $stats['confirmed'] }}</h3>
            <p>Confirmed</p>
        </div>
        <div class="stat-box">
            <h3>{{ $stats['pending'] }}</h3>
            <p>Pending</p>
        </div>
        <div class="stat-box">
            <h3>{{ $stats['completed'] }}</h3>
            <p>Completed</p>
        </div>
        <div class="stat-box">
            <h3>{{ $stats['cancelled'] }}</h3>
            <p>Cancelled</p>
        </div>
    </div>

    <!-- Summary Rates -->
    <div class="summary-section">
        <h2>Performance Metrics</h2>
        <p><strong>Confirmation Rate:</strong> {{ $stats['confirmation_rate'] }}%</p>
        <p><strong>Cancellation Rate:</strong> {{ $stats['cancellation_rate'] }}%</p>
    </div>

    <!-- Appointments Table -->
    <h2 style="color: #007bff; margin-bottom: 10px;">Appointments List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $appointment)
            <tr>
                <td>{{ $appointment->id }}</td>
                <td>{{ $appointment->customer->name ?? 'N/A' }}</td>
                <td>{{ $appointment->staff->name ?? 'N/A' }}</td>
                <td>{{ $appointment->appointment_date->format('d/m/Y') }}</td>
                <td>{{ $appointment->appointment_date->format('H:i') }}</td>
                <td>
                    <span class="status-badge status-{{ strtolower($appointment->status) }}">
                        {{ $appointment->status }}
                    </span>
                </td>
                <td>{{ Str::limit($appointment->notes ?? '', 30) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                    No appointments found for this period
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Daily Breakdown Chart (if available) -->
    @if(count($stats['daily_breakdown']) > 0)
    <div class="summary-section">
        <h2>Daily Breakdown</h2>
        <table style="background-color: white;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="text-align: center;">Total</th>
                    <th style="text-align: center;">Confirmed</th>
                    <th style="text-align: center;">Cancelled</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['daily_breakdown'] as $day)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                    <td style="text-align: center;">{{ $day->count }}</td>
                    <td style="text-align: center;">{{ $day->confirmed }}</td>
                    <td style="text-align: center;">{{ $day->cancelled }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>&copy; {{ now()->year }} {{ $tenant->name }}. All rights reserved.</p>
        <p>Report generated automatically by the booking system.</p>
    </div>
</body>
</html>
