<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Warning Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #dc2626; }
        .subtitle { font-size: 12px; margin-top: 5px; }
        .alert-box { padding: 12px; background-color: #fee2e2; border: 2px solid #dc2626; margin: 15px 0; }
        .alert-text { font-size: 11px; color: #991b1b; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 9px; }
        .table th { background-color: #dc2626; color: white; }
        .table tr:nth-child(even) { background-color: #fef2f2; }
        .amount { text-align: right; font-family: monospace; }
        .warning-high { background-color: #fee2e2; color: #991b1b; font-weight: bold; }
        .warning-medium { background-color: #fed7aa; color: #9a3412; font-weight: bold; }
        .warning-low { background-color: #fef3c7; color: #92400e; }
        .summary-box { margin-top: 20px; padding: 15px; border: 2px solid #dc2626; background-color: #fef2f2; }
        .summary-row { margin: 8px 0; font-size: 11px; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; color: #8775bd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">LEAVE MAX WARNING REPORT</div>
            <div class="subtitle">{{ config('app.name') }}</div>
            <div class="subtitle">Threshold: {{ $threshold }} days</div>
        </div>

        <div class="alert-box">
            <div class="alert-text">
                ⚠️ WARNING: {{ $lowBalances->count() }} employee(s) have leave balances at or below {{ $threshold }} days
            </div>
            <div style="margin-top: 5px; font-size: 9px;">
                Generated: {{ now()->format('d F Y H:i:s') }}
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 15%;">Emp Code</th>
                    <th style="width: 30%;">Employee Name</th>
                    <th style="width: 15%;">Department</th>
                    <th style="width: 12%;">Current Days</th>
                    <th style="width: 10%;">Max Limit</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowBalances as $index => $balance)
                @php
                    $severity = match(true) {
                        $balance->balance_cf <= 2 => 'high',
                        $balance->balance_cf <= 5 => 'medium',
                        default => 'low'
                    };
                    $statusText = match($severity) {
                        'high' => 'CRITICAL',
                        'medium' => 'WARNING',
                        default => 'LOW'
                    };
                @endphp
                <tr class="warning-{{ $severity }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $balance->employee->employee_code ?? 'N/A' }}</td>
                    <td>{{ strtoupper($balance->employee->surname ?? '') }}, {{ $balance->employee->firstname ?? '' }}</td>
                    <td>{{ $balance->employee->department->dept_name ?? 'N/A' }}</td>
                    <td class="amount">{{ number_format($balance->balance_cf, 2) }}</td>
                    <td class="amount">{{ number_format($threshold, 0) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $statusText }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($lowBalances->count() > 0)
        <div class="summary-box">
            <div class="summary-row">
                <strong>ACTION REQUIRED</strong>
            </div>
            <div class="summary-row" style="margin-top: 10px;">
                <strong style="color: #991b1b;">Critical (≤2 days):</strong>
                <span style="float: right;">{{ $lowBalances->filter(fn($b) => $b->balance_cf <= 2)->count() }} employees</span>
            </div>
            <div class="summary-row">
                <strong style="color: #9a3412;">Warning (3-5 days):</strong>
                <span style="float: right;">{{ $lowBalances->filter(fn($b) => $b->balance_cf > 2 && $b->balance_cf <= 5)->count() }} employees</span>
            </div>
            <div class="summary-row">
                <strong style="color: #92400e;">Low (6-{{ $threshold }} days):</strong>
                <span style="float: right;">{{ $lowBalances->filter(fn($b) => $b->balance_cf > 5)->count() }} employees</span>
            </div>
            <div class="summary-row" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #dc2626;">
                <strong>Recommended Actions:</strong>
            </div>
            <ul style="margin: 10px 0 0 20px; font-size: 9px; line-height: 1.6;">
                <li>Review and approve pending leave applications for critical employees</li>
                <li>Encourage employees with low balances to take planned leave</li>
                <li>Consider leave policy adjustments if widespread low balances persist</li>
                <li>Monitor leave accumulation rates and adjust accrual if needed</li>
            </ul>
        </div>
        @else
        <div style="text-align: center; padding: 30px; color: #059669;">
            <strong>✓ All employees have adequate leave balances</strong><br>
            <span style="font-size: 9px;">No employees below {{ $threshold }} days threshold</span>
        </div>
        @endif

        <div class="footer">
            Produced by Lorimak Africa - Payroll & HR System v1.0 | Generated: {{ now()->format('d M Y H:i') }}
        </div>
    </div>
</body>
</html>
