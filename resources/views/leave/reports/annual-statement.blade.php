<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Annual Leave Statement - {{ $employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #7c3aed; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #7c3aed; }
        .subtitle { font-size: 12px; margin-top: 5px; }
        .employee-box { padding: 12px; background-color: #faf5ff; border: 2px solid #d8b4fe; margin: 15px 0; }
        .employee-row { margin: 6px 0; font-size: 10px; }
        .label { font-weight: bold; display: inline-block; width: 120px; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 8px; }
        .table th { background-color: #7c3aed; color: white; }
        .table tr:nth-child(even) { background-color: #faf5ff; }
        .amount { text-align: right; font-family: monospace; }
        .leave-accrued { background-color: #e0e7ff; font-style: italic; }
        .leave-taken { background-color: #fee2e2; }
        .totals-row { font-weight: bold; background-color: #ddd6fe; }
        .balance-row { font-weight: bold; background-color: #c7d2fe; font-size: 9px; }
        .summary-box { margin-top: 20px; padding: 15px; border: 2px solid #7c3aed; background-color: #f5f3ff; }
        .summary-row { margin: 8px 0; font-size: 10px; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; color: #8775bd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">EMPLOYEE ANNUAL LEAVE STATEMENT</div>
            <div class="subtitle">{{ config('app.name') }}</div>
            <div class="subtitle">Year: {{ $year }}</div>
        </div>

        <div class="employee-box">
            <div class="employee-row">
                <span class="label">Employee Code:</span>
                <span>{{ $employee->employee_code ?? 'N/A' }}</span>
            </div>
            <div class="employee-row">
                <span class="label">Employee Name:</span>
                <span>{{ strtoupper($employee->full_name) }}</span>
            </div>
            <div class="employee-row">
                <span class="label">Department:</span>
                <span>{{ $employee->department->dept_name ?? 'N/A' }}</span>
            </div>
            <div class="employee-row">
                <span class="label">Annual Entitlement:</span>
                <span>{{ number_format($employee->leave_entitlement ?? 0, 2) }} days</span>
            </div>
            <div class="employee-row">
                <span class="label">Monthly Accrual Rate:</span>
                <span>{{ number_format($employee->leave_accrual_rate ?? 0, 3) }} days/month</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 15%;">Transaction Type</th>
                    <th style="width: 12%;">From Date</th>
                    <th style="width: 12%;">To Date</th>
                    <th style="width: 20%;">Comments</th>
                    <th style="width: 10%;">Period</th>
                    <th style="width: 10%;">Days Taken</th>
                    <th style="width: 10%;">Days Accrued</th>
                    <th style="width: 11%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance -->
                <tr>
                    <td><strong>Balance B/D</strong></td>
                    <td>01/01/{{ $year }}</td>
                    <td>31/12/{{ $year }}</td>
                    <td>Opening Balance</td>
                    <td>{{ $year }}</td>
                    <td class="amount">-</td>
                    <td class="amount">-</td>
                    <td class="amount"><strong>{{ number_format($balances->first()->balance_bf ?? 0, 3) }}</strong></td>
                </tr>

                @php
                    $runningBalance = $balances->first()->balance_bf ?? 0;
                    $totalAccrued = 0;
                    $totalTaken = 0;
                @endphp

                <!-- Monthly Accruals and Leave Applications -->
                @foreach($balances as $balance)
                    @php
                        $runningBalance += $balance->days_accrued;
                        $totalAccrued += $balance->days_accrued;
                    @endphp
                    <tr class="leave-accrued">
                        <td>LEAVE ACCRUED</td>
                        <td>{{ \Carbon\Carbon::parse($balance->period)->startOfMonth()->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($balance->period)->endOfMonth()->format('d/m/Y') }}</td>
                        <td>Monthly Leave Accrual</td>
                        <td>{{ $balance->period }}</td>
                        <td class="amount">-</td>
                        <td class="amount">{{ number_format($balance->days_accrued, 3) }}</td>
                        <td class="amount">{{ number_format($runningBalance, 3) }}</td>
                    </tr>

                    <!-- Leave applications for this period -->
                    @foreach($applications->where('date_from', '>=', \Carbon\Carbon::parse($balance->period)->startOfMonth())->where('date_from', '<=', \Carbon\Carbon::parse($balance->period)->endOfMonth()) as $application)
                        @php
                            $runningBalance -= $application->total_days;
                            $totalTaken += $application->total_days;
                        @endphp
                        <tr class="leave-taken">
                            <td>{{ $application->leave_type }}</td>
                            <td>{{ $application->date_from->format('d/m/Y') }}</td>
                            <td>{{ $application->date_to->format('d/m/Y') }}</td>
                            <td>{{ $application->comments ? substr($application->comments, 0, 30) : 'Leave' }}</td>
                            <td>{{ $application->date_from->format('F Y') }}</td>
                            <td class="amount">{{ number_format($application->total_days, 2) }}</td>
                            <td class="amount">-</td>
                            <td class="amount">{{ number_format($runningBalance, 3) }}</td>
                        </tr>
                    @endforeach
                @endforeach

                <!-- Totals Row -->
                <tr class="totals-row">
                    <td colspan="5">TOTALS</td>
                    <td class="amount">{{ number_format($totalTaken, 2) }}</td>
                    <td class="amount">{{ number_format($totalAccrued, 3) }}</td>
                    <td class="amount">-</td>
                </tr>

                <!-- Closing Balance -->
                <tr class="balance-row">
                    <td colspan="7" style="text-align: right;">BALANCE C/D</td>
                    <td class="amount" style="font-size: 10px; color: #7c3aed;">
                        {{ number_format($balances->last()->balance_cf ?? 0, 3) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-row">
                <strong>ANNUAL SUMMARY - {{ $year }}</strong>
            </div>
            <div class="summary-row">
                Opening Balance (01/01/{{ $year }}):
                <span style="float: right;">{{ number_format($balances->first()->balance_bf ?? 0, 3) }} days</span>
            </div>
            <div class="summary-row">
                Total Leave Accrued:
                <span style="float: right;">{{ number_format($totalAccrued, 3) }} days</span>
            </div>
            <div class="summary-row">
                Total Leave Taken:
                <span style="float: right;">{{ number_format($totalTaken, 2) }} days</span>
            </div>
            <div class="summary-row">
                <strong>Closing Balance (31/12/{{ $year }}):
                <span style="float: right; font-size: 12px; color: #7c3aed;">{{ number_format($balances->last()->balance_cf ?? 0, 3) }} days</span></strong>
            </div>
            <div class="summary-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #7c3aed;">
                Utilization Rate:
                <span style="float: right;">{{ $employee->leave_entitlement > 0 ? number_format(($totalTaken / $employee->leave_entitlement) * 100, 1) : 0 }}%</span>
            </div>
            <div class="summary-row">
                Total Leave Applications:
                <span style="float: right;">{{ $applications->count() }}</span>
            </div>
        </div>

        <div class="footer">
            Produced by Lorimak Africa - Payroll & HR System v1.0 | Statement for {{ $employee->full_name }} - {{ $year }}
        </div>
    </div>
</body>
</html>
