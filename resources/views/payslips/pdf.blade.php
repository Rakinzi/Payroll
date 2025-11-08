<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payslip->payslip_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .container {
            width: 100%;
            padding: 10px;
        }

        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 9px;
            color: #666;
            line-height: 1.3;
        }

        .payslip-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }

        .payslip-number {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }

        .employee-section {
            background-color: #f8fafc;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .employee-grid {
            display: table;
            width: 100%;
        }

        .employee-row {
            display: table-row;
        }

        .employee-cell {
            display: table-cell;
            width: 25%;
            padding: 4px 8px;
        }

        .label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .value {
            font-size: 10px;
            color: #1e293b;
            font-weight: 600;
        }

        .transactions-section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #e2e8f0;
        }

        .transactions-grid {
            display: table;
            width: 100%;
        }

        .trans-column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 2%;
        }

        .trans-column:last-child {
            padding-right: 0;
            padding-left: 2%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th {
            background-color: #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .totals-section {
            background-color: #1e40af;
            color: white;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .totals-grid {
            display: table;
            width: 100%;
        }

        .total-row {
            display: table-row;
        }

        .total-cell {
            display: table-cell;
            width: 25%;
            padding: 6px 10px;
        }

        .total-label {
            font-size: 9px;
            opacity: 0.9;
            margin-bottom: 3px;
        }

        .total-value {
            font-size: 14px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .ytd-section {
            background-color: #f1f5f9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .ytd-title {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 8px;
        }

        .ytd-grid {
            display: table;
            width: 100%;
        }

        .ytd-item {
            display: table-cell;
            width: 25%;
            padding: 4px 8px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }

        .notes {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 9px;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 4px;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    <div class="company-name">{{ config('app.name', 'Payroll System') }}</div>
                    <div class="company-details">
                        Comprehensive Payroll Management Solution<br>
                        Period: {{ $payslip->period_display }}<br>
                        Payment Date: {{ $payslip->payment_date->format('d F Y') }}
                    </div>
                </div>
                <div class="header-right">
                    <div class="payslip-title">PAYSLIP</div>
                    <div class="payslip-number">{{ $payslip->payslip_number }}</div>
                </div>
            </div>
        </div>

        <!-- Employee Details -->
        <div class="employee-section">
            <div class="employee-grid">
                <div class="employee-row">
                    <div class="employee-cell">
                        <div class="label">Employee ID</div>
                        <div class="value">{{ $employee->emp_system_id }}</div>
                    </div>
                    <div class="employee-cell">
                        <div class="label">Employee Name</div>
                        <div class="value">{{ $employee->full_name }}</div>
                    </div>
                    <div class="employee-cell">
                        <div class="label">Position</div>
                        <div class="value">{{ $employee->position->position_name ?? 'N/A' }}</div>
                    </div>
                    <div class="employee-cell">
                        <div class="label">Department</div>
                        <div class="value">{{ $employee->department->department_name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($payslip->notes)
        <!-- Notes -->
        <div class="notes">
            <div class="notes-title">Notes:</div>
            {{ $payslip->notes }}
        </div>
        @endif

        <!-- Transactions -->
        <div class="transactions-section">
            <div class="transactions-grid">
                <!-- Earnings Column -->
                <div class="trans-column">
                    <div class="section-title">EARNINGS</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50%">Description</th>
                                <th style="width: 25%">ZWG</th>
                                <th style="width: 25%">USD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($earnings as $earning)
                            <tr>
                                <td>{{ $earning->description }}</td>
                                <td class="amount">{{ number_format($earning->amount_zwg, 2) }}</td>
                                <td class="amount">{{ number_format($earning->amount_usd, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8;">No earnings</td>
                            </tr>
                            @endforelse
                            <tr style="font-weight: bold; background-color: #e2e8f0;">
                                <td>TOTAL EARNINGS</td>
                                <td class="amount">{{ number_format($payslip->gross_salary_zwg, 2) }}</td>
                                <td class="amount">{{ number_format($payslip->gross_salary_usd, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Deductions Column -->
                <div class="trans-column">
                    <div class="section-title">DEDUCTIONS</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50%">Description</th>
                                <th style="width: 25%">ZWG</th>
                                <th style="width: 25%">USD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deductions as $deduction)
                            <tr>
                                <td>{{ $deduction->description }}</td>
                                <td class="amount">{{ number_format($deduction->amount_zwg, 2) }}</td>
                                <td class="amount">{{ number_format($deduction->amount_usd, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8;">No deductions</td>
                            </tr>
                            @endforelse
                            <tr style="font-weight: bold; background-color: #e2e8f0;">
                                <td>TOTAL DEDUCTIONS</td>
                                <td class="amount">{{ number_format($payslip->total_deductions_zwg, 2) }}</td>
                                <td class="amount">{{ number_format($payslip->total_deductions_usd, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Net Pay Totals -->
        <div class="totals-section">
            <div class="totals-grid">
                <div class="total-row">
                    <div class="total-cell">
                        <div class="total-label">Gross Salary (ZWG)</div>
                        <div class="total-value">{{ number_format($payslip->gross_salary_zwg, 2) }}</div>
                    </div>
                    <div class="total-cell">
                        <div class="total-label">Gross Salary (USD)</div>
                        <div class="total-value">{{ number_format($payslip->gross_salary_usd, 2) }}</div>
                    </div>
                    <div class="total-cell">
                        <div class="total-label">NET PAY (ZWG)</div>
                        <div class="total-value">{{ number_format($payslip->net_salary_zwg, 2) }}</div>
                    </div>
                    <div class="total-cell">
                        <div class="total-label">NET PAY (USD)</div>
                        <div class="total-value">{{ number_format($payslip->net_salary_usd, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Year-to-Date Section -->
        <div class="ytd-section">
            <div class="ytd-title">Year-to-Date Accumulatives ({{ $payslip->period_year }})</div>
            <div class="ytd-grid">
                <div class="ytd-item">
                    <div class="label">YTD Gross (ZWG)</div>
                    <div class="value">{{ number_format($payslip->ytd_gross_zwg, 2) }}</div>
                </div>
                <div class="ytd-item">
                    <div class="label">YTD Gross (USD)</div>
                    <div class="value">{{ number_format($payslip->ytd_gross_usd, 2) }}</div>
                </div>
                <div class="ytd-item">
                    <div class="label">YTD PAYE (ZWG)</div>
                    <div class="value">{{ number_format($payslip->ytd_paye_zwg, 2) }}</div>
                </div>
                <div class="ytd-item">
                    <div class="label">YTD PAYE (USD)</div>
                    <div class="value">{{ number_format($payslip->ytd_paye_usd, 2) }}</div>
                </div>
            </div>
        </div>

        @if($payslip->exchange_rate)
        <div style="font-size: 9px; color: #64748b; margin-bottom: 10px;">
            Exchange Rate: 1 USD = {{ number_format($payslip->exchange_rate, 4) }} ZWG
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div style="margin-bottom: 5px;">
                This is a system-generated payslip. No signature is required.
            </div>
            <div>
                Generated on {{ now()->format('d F Y H:i:s') }} | {{ config('app.name') }}
            </div>
            <div style="margin-top: 5px; font-style: italic;">
                For any queries regarding this payslip, please contact your HR department.
            </div>
        </div>
    </div>
</body>
</html>
