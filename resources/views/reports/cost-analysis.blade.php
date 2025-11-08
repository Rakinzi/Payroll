<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cost Analysis Report - {{ $report->report_type_display }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #2563eb; padding-bottom: 15px; }
        .company-name { font-size: 20px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .report-title { font-size: 16px; font-weight: bold; margin: 5px 0; }
        .info-grid { margin: 15px 0; }
        .info-row { display: table; width: 100%; margin: 5px 0; }
        .info-cell { display: table-cell; padding: 5px; border: 1px solid #ddd; background-color: #f8fafc; }
        .label { font-weight: bold; color: #64748b; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #2563eb; color: white; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        .amount { text-align: right; font-family: monospace; }
        .totals { margin-top: 20px; padding: 15px; background-color: #1e40af; color: white; border-radius: 5px; }
        .totals .total-row { margin: 10px 0; font-size: 14px; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-name">{{ config('app.name', 'Payroll System') }}</div>
            <div class="report-title">Cost Analysis Report</div>
            <div>{{ $report->report_type_display }}</div>
        </div>

        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width: 25%;"><span class="label">Payroll:</span> {{ $payroll->payroll_name }}</div>
                <div class="info-cell" style="width: 25%;"><span class="label">Currency:</span> {{ $report->currency }}</div>
                <div class="info-cell" style="width: 25%;"><span class="label">Period:</span> {{ $report->period_display }}</div>
                <div class="info-cell" style="width: 25%;"><span class="label">Generated:</span> {{ $report->generated_at->format('d M Y H:i') }}</div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="width: 20%;">Amount ({{ $report->currency }})</th>
                    <th style="width: 15%;">Employee Count</th>
                    <th style="width: 15%;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdownDetails as $detail)
                <tr>
                    <td>{{ $detail->category_name }}</td>
                    <td class="amount">
                        {{ number_format($report->currency === 'ZWG' ? $detail->zwg_amount : $detail->usd_amount, 2) }}
                    </td>
                    <td class="amount">{{ $detail->employee_count }}</td>
                    <td class="amount">{{ number_format($detail->percentage_of_total, 2) }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8;">No breakdown data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <strong>Total Costs ({{ $report->currency }}):</strong>
                <span style="float: right; font-size: 18px;">{{ number_format($report->total_costs, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Total Categories:</strong>
                <span style="float: right;">{{ $breakdownDetails->count() }}</span>
            </div>
        </div>

        <div class="footer">
            Generated on {{ now()->format('d M Y H:i:s') }} | {{ config('app.name') }} - Payroll Management System
        </div>
    </div>
</body>
</html>
