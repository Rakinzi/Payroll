<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Taxable Accumulatives - {{ $accumulative->tax_year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 12px; margin: 5px 0; }
        .info-box { margin: 15px 0; padding: 10px; background-color: #f8fafc; border: 1px solid #ddd; }
        .info-row { margin: 5px 0; }
        .label { font-weight: bold; display: inline-block; width: 120px; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 9px; }
        .table th { background-color: #e2e8f0; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        .amount { text-align: right; font-family: monospace; }
        .totals { margin-top: 20px; padding: 15px; border: 2px solid #000; }
        .total-row { margin: 8px 0; font-size: 12px; }
        .status-badge { padding: 3px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .status-compliant { background-color: #d1fae5; color: #065f46; }
        .status-outstanding { background-color: #fee2e2; color: #991b1b; }
        .status-under { background-color: #fef3c7; color: #92400e; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Taxable Accumulatives Report</div>
            <div class="subtitle">{{ config('app.name', 'Company Name') }}</div>
            <div>Tax Year: {{ $accumulative->tax_year }} | Currency: {{ $accumulative->currency }}</div>
        </div>

        <div class="info-box">
            <div class="info-row">
                <span class="label">Payroll:</span>
                <span>{{ $payroll->payroll_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Currency:</span>
                <span>{{ $accumulative->currency_display }}</span>
            </div>
            <div class="info-row">
                <span class="label">Tax Year:</span>
                <span>{{ $accumulative->tax_year }}</span>
            </div>
            <div class="info-row">
                <span class="label">Generated:</span>
                <span>{{ $accumulative->generated_at->format('d F Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Employee Count:</span>
                <span>{{ $accumulative->employee_count }}</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 22%;">Employee Name</th>
                    <th style="width: 14%;">ID Number</th>
                    <th style="width: 15%;">YTD Taxable Income</th>
                    <th style="width: 13%;">YTD Tax Paid</th>
                    <th style="width: 12%;">Outstanding</th>
                    <th style="width: 10%;">Eff. Rate</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->employee_name }}</td>
                    <td>{{ $detail->nat_id ?? 'N/A' }}</td>
                    <td class="amount">{{ number_format($detail->ytd_taxable_income, 2) }}</td>
                    <td class="amount">{{ number_format($detail->ytd_tax_paid, 2) }}</td>
                    <td class="amount">{{ number_format($detail->outstanding_tax, 2) }}</td>
                    <td class="amount">{{ number_format($detail->effective_tax_rate, 2) }}%</td>
                    <td style="text-align: center;">
                        <span class="status-badge status-{{ $detail->compliance_status }}">
                            {{ ucfirst(str_replace('_', ' ', $detail->compliance_status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">No employee records available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <strong>Total Taxable Income ({{ $accumulative->currency }}):</strong>
                <span style="float: right; font-size: 14px;">{{ number_format($accumulative->total_taxable_income, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Total Tax Paid ({{ $accumulative->currency }}):</strong>
                <span style="float: right; font-size: 14px;">{{ number_format($accumulative->total_tax_paid, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Total Outstanding Tax ({{ $accumulative->currency }}):</strong>
                <span style="float: right; font-size: 14px; color: {{ $accumulative->total_outstanding_tax > 0 ? '#dc2626' : '#059669' }};">
                    {{ number_format($accumulative->total_outstanding_tax, 2) }}
                </span>
            </div>
        </div>

        <div class="footer">
            Generated: {{ now()->format('d M Y H:i') }} | {{ config('app.name') }} - Tax Year {{ $accumulative->tax_year }}
        </div>
    </div>
</body>
</html>
