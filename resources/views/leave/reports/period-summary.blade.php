<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Period Summary - {{ $period }} {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
        .container { width: 100%; padding: 10px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; color: #2563eb; }
        .subtitle { font-size: 10px; margin-top: 5px; }
        .info-box { padding: 10px; background-color: #f1f5f9; border: 1px solid #cbd5e1; margin: 10px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 8px; }
        .table th { background-color: #2563eb; color: white; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        .dept-header { background-color: #dbeafe; font-weight: bold; padding: 8px; margin-top: 10px; border-left: 4px solid #2563eb; }
        .dept-totals { font-weight: bold; background-color: #e0f2fe; }
        .amount { text-align: right; font-family: monospace; }
        .grand-totals { margin-top: 20px; padding: 15px; border: 2px solid #2563eb; background-color: #eff6ff; }
        .total-row { margin: 8px 0; font-size: 11px; }
        .footer { position: fixed; bottom: 5px; width: 100%; text-align: center; font-size: 7px; border-top: 1px solid #ddd; padding-top: 3px; color: #8775bd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">LEAVE EVALUATION REPORT - DAYS ONLY</div>
            <div class="subtitle">{{ config('app.name') }}</div>
            <div class="subtitle">Period: {{ $period }} {{ $year }}</div>
        </div>

        <div class="info-box">
            <strong>Report Details</strong><br>
            Generated: {{ now()->format('d/m/Y H:i:s') }}<br>
            Payroll: {{ $payroll->payroll_name }}<br>
            Period: {{ $period }} {{ $year }}
        </div>

        @php
            $grandTotalBf = 0;
            $grandTotalCf = 0;
            $grandTotalAccrued = 0;
            $grandTotalTaken = 0;
        @endphp

        @foreach($balances as $deptName => $deptBalances)
        <div class="dept-header">
            DEPARTMENT: {{ strtoupper($deptName ?: 'UNASSIGNED') }}
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 10%;">Emp Code</th>
                    <th style="width: 25%;">Name</th>
                    <th style="width: 12%;">Bal B/F</th>
                    <th style="width: 12%;">Accrued</th>
                    <th style="width: 12%;">Taken</th>
                    <th style="width: 12%;">Bal C/F</th>
                    <th style="width: 12%;">Entitlement</th>
                    <th style="width: 5%;">%</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $deptTotalBf = 0;
                    $deptTotalCf = 0;
                    $deptTotalAccrued = 0;
                    $deptTotalTaken = 0;
                @endphp

                @foreach($deptBalances as $balance)
                @php
                    $deptTotalBf += $balance->balance_bf;
                    $deptTotalCf += $balance->balance_cf;
                    $deptTotalAccrued += $balance->days_accrued;
                    $deptTotalTaken += $balance->days_taken;
                @endphp
                <tr>
                    <td>{{ $balance->employee->emp_system_id ?? 'N/A' }}</td>
                    <td>{{ strtoupper($balance->employee->surname ?? '') }}, {{ substr($balance->employee->firstname ?? '', 0, 1) }}</td>
                    <td class="amount">{{ number_format($balance->balance_bf, 2) }}</td>
                    <td class="amount">{{ number_format($balance->days_accrued, 3) }}</td>
                    <td class="amount">{{ number_format($balance->days_taken, 2) }}</td>
                    <td class="amount">{{ number_format($balance->balance_cf, 2) }}</td>
                    <td class="amount">{{ number_format($balance->employee->leave_entitlement ?? 0, 3) }}</td>
                    <td class="amount">{{ number_format($balance->utilization_percentage, 1) }}%</td>
                </tr>
                @endforeach

                <tr class="dept-totals">
                    <td colspan="2">{{ strtoupper($deptName ?: 'UNASSIGNED') }} TOTALS ({{ count($deptBalances) }} employees)</td>
                    <td class="amount">{{ number_format($deptTotalBf, 2) }}</td>
                    <td class="amount">{{ number_format($deptTotalAccrued, 2) }}</td>
                    <td class="amount">{{ number_format($deptTotalTaken, 2) }}</td>
                    <td class="amount">{{ number_format($deptTotalCf, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        @php
            $grandTotalBf += $deptTotalBf;
            $grandTotalCf += $deptTotalCf;
            $grandTotalAccrued += $deptTotalAccrued;
            $grandTotalTaken += $deptTotalTaken;
        @endphp
        @endforeach

        <div class="grand-totals">
            <div class="total-row">
                <strong>GRAND TOTALS - ALL DEPARTMENTS</strong>
            </div>
            <div class="total-row">
                Balance B/F: <span style="float: right;">{{ number_format($grandTotalBf, 2) }} days</span>
            </div>
            <div class="total-row">
                Days Accrued: <span style="float: right;">{{ number_format($grandTotalAccrued, 2) }} days</span>
            </div>
            <div class="total-row">
                Days Taken: <span style="float: right;">{{ number_format($grandTotalTaken, 2) }} days</span>
            </div>
            <div class="total-row">
                <strong>Balance C/F: <span style="float: right; font-size: 13px; color: #2563eb;">{{ number_format($grandTotalCf, 2) }} days</span></strong>
            </div>
        </div>

        <div class="footer">
            Produced by Lorimak Africa - Payroll & HR System v1.0 | Page {PAGE_NUM}
        </div>
    </div>
</body>
</html>
