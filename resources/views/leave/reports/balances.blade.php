<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Balances Report - {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #059669; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #059669; }
        .subtitle { font-size: 12px; margin-top: 5px; }
        .info-box { margin: 15px 0; padding: 10px; background-color: #f0fdf4; border: 1px solid #bbf7d0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 9px; }
        .table th { background-color: #059669; color: white; }
        .table tr:nth-child(even) { background-color: #f0fdf4; }
        .amount { text-align: right; font-family: monospace; }
        .status-critical { color: #dc2626; font-weight: bold; }
        .status-warning { color: #ea580c; font-weight: bold; }
        .status-healthy { color: #059669; font-weight: bold; }
        .summary-box { margin-top: 20px; padding: 15px; border: 2px solid #059669; background-color: #ecfdf5; }
        .summary-row { margin: 8px 0; font-size: 11px; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; color: #8775bd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">LEAVE BALANCES REPORT</div>
            <div class="subtitle">{{ config('app.name') }}</div>
            <div class="subtitle">Year: {{ $year }}</div>
        </div>

        <div class="info-box">
            <strong>Report Information</strong><br>
            Generated: {{ now()->format('d F Y H:i') }}<br>
            Payroll: {{ $payroll->payroll_name }}<br>
            Report Year: {{ $year }}<br>
            Total Employees: {{ $balances->count() }}
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 15%;">Emp Code</th>
                    <th style="width: 25%;">Employee Name</th>
                    <th style="width: 15%;">Department</th>
                    <th style="width: 10%;">Entitlement</th>
                    <th style="width: 10%;">Balance C/F</th>
                    <th style="width: 10%;">Days Taken</th>
                    <th style="width: 7%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalEntitlement = 0;
                    $totalBalance = 0;
                    $totalTaken = 0;
                    $criticalCount = 0;
                    $warningCount = 0;
                @endphp

                @foreach($balances as $index => $balance)
                @php
                    $totalEntitlement += $balance->employee->leave_entitlement ?? 0;
                    $totalBalance += $balance->balance_cf;
                    $totalTaken += $balance->days_taken;

                    if ($balance->utilization_percentage >= 90) {
                        $criticalCount++;
                    } elseif ($balance->utilization_percentage >= 75) {
                        $warningCount++;
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $balance->employee->emp_system_id ?? 'N/A' }}</td>
                    <td>{{ $balance->employee->full_name ?? 'Unknown' }}</td>
                    <td>{{ $balance->employee->department->dept_name ?? 'N/A' }}</td>
                    <td class="amount">{{ number_format($balance->employee->leave_entitlement ?? 0, 2) }}</td>
                    <td class="amount">{{ number_format($balance->balance_cf, 2) }}</td>
                    <td class="amount">{{ number_format($balance->days_taken, 2) }}</td>
                    <td class="amount status-{{ $balance->utilization_status }}">
                        {{ number_format($balance->utilization_percentage, 0) }}%
                    </td>
                </tr>
                @endforeach

                <tr style="font-weight: bold; background-color: #d1fae5;">
                    <td colspan="4">TOTALS</td>
                    <td class="amount">{{ number_format($totalEntitlement, 2) }}</td>
                    <td class="amount">{{ number_format($totalBalance, 2) }}</td>
                    <td class="amount">{{ number_format($totalTaken, 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-row">
                <strong>SUMMARY STATISTICS</strong>
            </div>
            <div class="summary-row">
                Total Employees: <span style="float: right;">{{ $balances->count() }}</span>
            </div>
            <div class="summary-row">
                Total Leave Entitlement: <span style="float: right;">{{ number_format($totalEntitlement, 2) }} days</span>
            </div>
            <div class="summary-row">
                Total Balance Remaining: <span style="float: right;">{{ number_format($totalBalance, 2) }} days</span>
            </div>
            <div class="summary-row">
                Total Days Taken: <span style="float: right;">{{ number_format($totalTaken, 2) }} days</span>
            </div>
            <div class="summary-row">
                Average Utilization: <span style="float: right;">{{ $totalEntitlement > 0 ? number_format((($totalEntitlement - $totalBalance) / $totalEntitlement) * 100, 1) : 0 }}%</span>
            </div>
            <div class="summary-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #059669;">
                <span class="status-critical">Critical (≥90%): {{ $criticalCount }} employees</span>
            </div>
            <div class="summary-row">
                <span class="status-warning">Warning (75-89%): {{ $warningCount }} employees</span>
            </div>
            <div class="summary-row">
                <span class="status-healthy">Healthy (<75%): {{ $balances->count() - $criticalCount - $warningCount }} employees</span>
            </div>
        </div>

        <div class="footer">
            Produced by Lorimak Africa - Payroll & HR System v1.0 | Generated: {{ now()->format('d M Y H:i') }}
        </div>
    </div>
</body>
</html>
