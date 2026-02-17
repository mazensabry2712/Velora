<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
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
            font-size: 28px;
        }
        .company-info {
            margin-top: 10px;
            font-size: 14px;
        }
        .invoice-details {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-details .left,
        .invoice-details .right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .invoice-details h3 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 16px;
        }
        .invoice-details p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table thead {
            background-color: #007bff;
            color: white;
        }
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .totals table {
            margin-bottom: 0;
        }
        .totals table td {
            border: none;
            padding: 8px;
        }
        .totals table tr:last-child {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #007bff;
        }
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .status.paid {
            background-color: #28a745;
            color: white;
        }
        .status.pending {
            background-color: #ffc107;
            color: #333;
        }
        .status.cancelled {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $tenant->name }}</h1>
        <div class="company-info">
            <p><strong>Domain:</strong> {{ $tenant->domain }}</p>
            <p><strong>Generated:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="invoice-details">
        <div class="left">
            <h3>Invoice To:</h3>
            <p><strong>{{ $invoice->customer->name }}</strong></p>
            <p>{{ $invoice->customer->email }}</p>
            @if($invoice->customer->phone)
            <p>{{ $invoice->customer->phone }}</p>
            @endif
        </div>
        <div class="right" style="text-align: right;">
            <h3>Invoice Details:</h3>
            <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $invoice->created_at->format('d/m/Y') }}</p>
            @if($invoice->due_date)
            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
            @endif
            <p><strong>Status:</strong>
                <span class="status {{ strtolower($invoice->status) }}">{{ $invoice->status }}</span>
            </p>
        </div>
    </div>

    <!-- Appointment Info -->
    @if($invoice->appointment)
    <div style="background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #007bff;">Appointment Information</h3>
        <p><strong>Date & Time:</strong> {{ $invoice->appointment->appointment_date->format('d/m/Y H:i') }}</p>
        <p><strong>Staff:</strong> {{ $invoice->appointment->staff->name ?? 'N/A' }}</p>
        @if($invoice->appointment->notes)
        <p><strong>Notes:</strong> {{ $invoice->appointment->notes }}</p>
        @endif
    </div>
    @endif

    <!-- Invoice Items -->
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotal = $invoice->total_amount - ($invoice->tax_amount ?? 0) + ($invoice->discount ?? 0);
            @endphp
            <tr>
                <td>
                    <strong>Service Charge</strong><br>
                    <small>Appointment service on {{ $invoice->created_at->format('d/m/Y') }}</small>
                </td>
                <td style="text-align: center;">1</td>
                <td style="text-align: right;">{{ number_format($subtotal, 2) }}</td>
                <td style="text-align: right;">{{ number_format($subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right;">{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if($invoice->discount > 0)
            <tr>
                <td>Discount:</td>
                <td style="text-align: right; color: #28a745;">-{{ number_format($invoice->discount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->tax_amount > 0)
            <tr>
                <td>Tax:</td>
                <td style="text-align: right;">{{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Total:</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Payment Info -->
    <div style="clear: both; margin-top: 30px;">
        @if($invoice->payment_method)
        <p><strong>Payment Method:</strong> {{ $invoice->payment_method }}</p>
        @endif
        @if($invoice->paid_at)
        <p><strong>Paid At:</strong> {{ $invoice->paid_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
    </div>
</body>
</html>
