<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Summary - {{ $period }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            color: #333;
        }
        .container {
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 14px;
            color: #64748b;
            font-weight: normal;
        }
        .summary-section {
            background-color: #f8fafc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            width: 25%;
            padding: 8px;
            vertical-align: top;
        }
        .summary-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .summary-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin: 15px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background-color: #e2e8f0;
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        table td {
            padding: 6px;
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
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }
        .earning {
            color: #059669;
        }
        .deduction {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>PAYROLL SUMMARY REPORT</h1>
            <h2>{{ $payroll->payroll_name }} - {{ $period }}</h2>
        </div>

        <!-- Summary Totals -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-cell">
                        <div class="summary-label">Total Employees</div>
                        <div class="summary-value">{{ number_format($summary['total_employees']) }}</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-label">Gross Salary (ZWG)</div>
                        <div class="summary-value">{{ number_format($summary['gross_salary_zwg'], 2) }}</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-label">Gross Salary (USD)</div>
                        <div class="summary-value">{{ number_format($summary['gross_salary_usd'], 2) }}</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-label">Net Pay (ZWG)</div>
                        <div class="summary-value">{{ number_format($summary['net_salary_zwg'], 2) }}</div>
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell">
                        <div class="summary-label">Net Pay (USD)</div>
                        <div class="summary-value">{{ number_format($summary['net_salary_usd'], 2) }}</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-label">Total Deductions (ZWG)</div>
                        <div class="summary-value">{{ number_format($summary['total_deductions_zwg'], 2) }}</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-label">Total Deductions (USD)</div>
                        <div class="summary-value">{{ number_format($summary['total_deductions_usd'], 2) }}</div>
                    </div>
                    <div class="summary-cell"></div>
                </div>
            </div>
        </div>

        <!-- Department Breakdown -->
        <div class="section-title">Breakdown by Department</div>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-right">Employees</th>
                    <th class="text-right">Gross (ZWG)</th>
                    <th class="text-right">Gross (USD)</th>
                    <th class="text-right">Net (ZWG)</th>
                    <th class="text-right">Net (USD)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departmentBreakdown as $department => $data)
                <tr>
                    <td>{{ $department }}</td>
                    <td class="amount">{{ number_format($data['count']) }}</td>
                    <td class="amount">{{ number_format($data['gross_zwg'], 2) }}</td>
                    <td class="amount">{{ number_format($data['gross_usd'], 2) }}</td>
                    <td class="amount">{{ number_format($data['net_zwg'], 2) }}</td>
                    <td class="amount">{{ number_format($data['net_usd'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8;">No department data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Transaction Code Breakdown -->
        <div class="section-title">Breakdown by Transaction Code</div>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Type</th>
                    <th class="text-right">Amount (ZWG)</th>
                    <th class="text-right">Amount (USD)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactionBreakdown as $description => $data)
                <tr>
                    <td>{{ $description }}</td>
                    <td>
                        <span class="{{ $data['type'] }}">
                            {{ ucfirst($data['type']) }}
                        </span>
                    </td>
                    <td class="amount">{{ number_format($data['amount_zwg'], 2) }}</td>
                    <td class="amount">{{ number_format($data['amount_usd'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8;">No transaction data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <div style="margin-bottom: 5px;">
                Generated on {{ now()->format('d F Y H:i:s') }}
            </div>
            <div>
                This is a system-generated report | {{ config('app.name') }}
            </div>
        </div>
    </div>
</body>
</html>
