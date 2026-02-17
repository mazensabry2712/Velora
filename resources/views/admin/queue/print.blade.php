<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة قائمة الانتظار - {{ tenant()->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            direction: rtl;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0.3cm;
        }

        .page-container {
            background: white;
            border-radius: 15px;
            padding: 0.4cm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .print-header {
            text-align: center;
            margin-bottom: 0.3cm;
            padding: 0.3cm;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            position: relative;
        }

        .print-header h1 {
            font-size: 1.5em;
            margin-bottom: 0.1cm;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .print-header .subtitle {
            font-size: 0.8em;
            opacity: 0.95;
            font-weight: 500;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 0.3cm;
        }

        .print-actions button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.3cm 0.8cm;
            border: none;
            border-radius: 10px;
            font-size: 0.9em;
            cursor: pointer;
            margin: 0 0.2cm;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }

        .print-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        .print-actions .close-btn {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            box-shadow: 0 4px 10px rgba(107, 114, 128, 0.3);
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 0.4cm;
            margin-bottom: 0.3cm;
            padding: 0.25cm;
            background: #f9fafb;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
        }

        .stat-item {
            text-align: center;
            padding: 0.15cm 0.3cm;
            background: white;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            min-width: 2cm;
        }

        .stat-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.7em;
            color: #6b7280;
            margin-top: 0.03cm;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.3cm;
            width: 100%;
        }

        .queue-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            page-break-inside: avoid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .queue-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0.25cm 0.35cm;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .queue-number-badge {
            background: white;
            border-radius: 8px;
            padding: 0.15cm 0.35cm;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.15cm;
        }

        .queue-number-badge .label {
            font-size: 0.7em;
            color: #6b7280;
            font-weight: 600;
        }

        .queue-number-badge .number {
            font-size: 1.3em;
            font-weight: bold;
            color: #667eea;
        }

        .badges {
            display: flex;
            gap: 0.2cm;
            flex-direction: column;
            align-items: flex-end;
        }

        .badge {
            padding: 0.15cm 0.4cm;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: bold;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .badge-status.waiting {
            background: #3b82f6;
            color: white;
        }

        .badge-status.serving {
            background: #10b981;
            color: white;
        }

        .badge-status.completed {
            background: #6b7280;
            color: white;
        }

        .badge-status.cancelled {
            background: #ef4444;
            color: white;
        }

        .badge-priority {
            background: #6b7280;
            color: white;
        }

        .badge-priority.vip {
            background: #f59e0b;
            color: white;
        }

        .card-body {
            padding: 0.25cm;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.25cm;
        }

        .info-box {
            background: #f9fafb;
            padding: 0.2cm;
            border-radius: 6px;
            border-right: 3px solid #667eea;
        }

        .info-box.full-width {
            grid-column: 1 / -1;
        }

        .info-icon {
            font-size: 1em;
            margin-left: 0.15cm;
        }

        .info-label {
            font-size: 0.6em;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.08cm;
        }

        .info-value {
            font-size: 0.8em;
            color: #1f2937;
            font-weight: 600;
            word-wrap: break-word;
            line-height: 1.2;
        }

        .notes-section {
            margin-top: 0.25cm;
            padding: 0.25cm;
            background: #fef3c7;
            border-radius: 6px;
            border-right: 3px solid #f59e0b;
        }

        .notes-label {
            font-size: 0.65em;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 0.08cm;
        }

        .notes-text {
            font-size: 0.7em;
            color: #78350f;
            line-height: 1.3;
        }

        .page-footer {
            margin-top: 0.3cm;
            padding-top: 0.25cm;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 0.7em;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .page-container {
                box-shadow: none;
                border-radius: 0;
                padding: 0.5cm;
            }

            .print-actions {
                display: none;
            }

            .queue-card {
                box-shadow: none;
                break-inside: avoid;
            }

            .queue-card:hover {
                transform: none;
            }

            @page {
                size: A4 portrait;
                margin: 0.4cm;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="print-actions">
            <button onclick="window.print()">
                🖨️ طباعة القائمة
            </button>
            <button onclick="window.close()" class="close-btn">
                ✖️ إغلاق
            </button>
        </div>

        <div class="print-header">
            <h1>{{ tenant()->name ?? 'Demo Clinic' }}</h1>
            <div class="subtitle">قائمة الانتظار - {{ now()->locale('ar')->isoFormat('dddd، D MMMM YYYY - الساعة h:mm A') }}</div>
        </div>

        @php
            $waitingCount = $queues->where('status', 'waiting')->count();
            $servingCount = $queues->where('status', 'serving')->count();
            $vipCount = $queues->where('is_vip', true)->count();
        @endphp

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number">{{ $queues->count() }}</span>
                <span class="stat-label">إجمالي القائمة</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $waitingCount }}</span>
                <span class="stat-label">في الانتظار</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $servingCount }}</span>
                <span class="stat-label">يتم الخدمة</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $vipCount }}</span>
                <span class="stat-label">VIP</span>
            </div>
        </div>

        <div class="cards-container">
            @foreach($queues as $queue)
                <div class="queue-card">
                    <div class="card-header">
                        <div class="queue-number-badge">
                            <span class="label">الدور</span>
                            <span class="number">{{ $queue->queue_number }}</span>
                        </div>
                        <div class="badges">
                            <span class="badge badge-status {{ $queue->status }}">
                                @if($queue->status === 'waiting')
                                    في الانتظار
                                @elseif($queue->status === 'serving')
                                    يتم الخدمة
                                @elseif($queue->status === 'completed')
                                    مكتمل
                                @else
                                    ملغي
                                @endif
                            </span>
                            <span class="badge badge-priority {{ $queue->is_vip ? 'vip' : '' }}">
                                {{ $queue->is_vip ? '⭐ VIP' : 'عادي' }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-box full-width">
                                <div class="info-label">
                                    <span class="info-icon">👤</span>
                                    اسم العميل
                                </div>
                                <div class="info-value">{{ $queue->appointment->customer->name ?? '-' }}</div>
                            </div>

                            <div class="info-box">
                                <div class="info-label">
                                    <span class="info-icon">📱</span>
                                    الهاتف
                                </div>
                                <div class="info-value">{{ $queue->appointment->customer->phone ?? '-' }}</div>
                            </div>

                            <div class="info-box">
                                <div class="info-label">
                                    <span class="info-icon">✉️</span>
                                    البريد
                                </div>
                                <div class="info-value" style="font-size: 0.7em;">{{ Str::limit($queue->appointment->customer->email ?? '-', 20) }}</div>
                            </div>

                            <div class="info-box">
                                <div class="info-label">
                                    <span class="info-icon">💼</span>
                                    الخدمة
                                </div>
                                <div class="info-value">{{ $queue->appointment->service->name ?? '-' }}</div>
                            </div>

                            <div class="info-box">
                                <div class="info-label">
                                    <span class="info-icon">👨‍⚕️</span>
                                    الموظف
                                </div>
                                <div class="info-value">{{ $queue->appointment->staff->name ?? '-' }}</div>
                            </div>
                        </div>

                        @if($queue->notes)
                            <div class="notes-section">
                                <div class="notes-label">📝 ملاحظات هامة</div>
                                <div class="notes-text">{{ $queue->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($queues->isEmpty())
            <div style="text-align: center; padding: 3cm; color: #6b7280;">
                <p style="font-size: 2em; margin-bottom: 0.5cm;">📋</p>
                <p style="font-size: 1.3em; font-weight: 600;">لا توجد عناصر في قائمة الانتظار</p>
            </div>
        @endif

        <div class="page-footer">
            <p>تم الطباعة من نظام {{ tenant()->name ?? 'إدارة المواعيد' }} | {{ now()->locale('ar')->isoFormat('D MMMM YYYY - الساعة h:mm A') }}</p>
        </div>
    </div>
</body>
</html>
