<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report->report_type_display }}</title>
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
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .table th { background-color: #e2e8f0; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        .amount { text-align: right; font-family: monospace; }
        .totals { margin-top: 20px; padding: 15px; border: 2px solid #000; }
        .total-row { margin: 8px 0; font-size: 12px; }
        .submission-box { margin-top: 20px; padding: 10px; background-color: #fef3c7; border: 1px solid #f59e0b; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">{{ $report->report_type_display }}</div>
            <div class="subtitle">{{ config('app.name', 'Company Name') }}</div>
            <div>Period: {{ $report->period_start->format('d M Y') }} - {{ $report->period_end->format('d M Y') }}</div>
        </div>

        <div class="info-box">
            <div class="info-row">
                <span class="label">Payroll:</span>
                <span>{{ $payroll->payroll_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Currency:</span>
                <span>{{ $report->currency }}</span>
            </div>
            <div class="info-row">
                <span class="label">Report Type:</span>
                <span>{{ $report->report_type_display }}</span>
            </div>
            <div class="info-row">
                <span class="label">Status:</span>
                <span>{{ $report->status_display }}</span>
            </div>
            <div class="info-row">
                <span class="label">Generated:</span>
                <span>{{ $report->generated_at->format('d F Y H:i') }}</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Employee Name</th>
                    <th style="width: 20%;">ID Number</th>
                    <th style="width: 25%;">Contribution Amount</th>
                    <th style="width: 20%;">Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->employee_name }}</td>
                    <td>{{ $detail->nat_id ?? 'N/A' }}</td>
                    <td class="amount">{{ number_format($detail->contribution_amount, 2) }}</td>
                    <td>{{ $detail->reference_number ?? 'Pending' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">No employee records available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <strong>Total Contributions ({{ $report->currency }}):</strong>
                <span style="float: right; font-size: 16px;">{{ number_format($report->total_amount, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Number of Employees:</strong>
                <span style="float: right;">{{ $details->count() }}</span>
            </div>
        </div>

        @if($report->submission_status !== 'draft')
        <div class="submission-box">
            <h4 style="margin-bottom: 8px;">Submission Information</h4>
            <div><strong>Status:</strong> {{ $report->status_display }}</div>
            @if($report->submitted_at)
            <div><strong>Submitted:</strong> {{ $report->submitted_at->format('d M Y H:i') }}</div>
            @endif
            @if($report->submission_reference)
            <div><strong>Reference:</strong> {{ $report->submission_reference }}</div>
            @endif
        </div>
        @endif

        <div class="footer">
            Generated: {{ now()->format('d M Y H:i') }} | {{ config('app.name') }} - Payroll Management System
        </div>
    </div>
</body>
</html>
