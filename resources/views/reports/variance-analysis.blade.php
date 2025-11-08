<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Variance Analysis Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
        .container { width: 100%; padding: 10px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; color: #2563eb; }
        .summary-box { padding: 12px; background-color: #f1f5f9; border: 1px solid #cbd5e1; margin: 15px 0; }
        .summary-grid { display: table; width: 100%; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; padding: 6px; border: 1px solid #cbd5e1; }
        .positive { color: green; font-weight: bold; }
        .negative { color: red; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 8px; }
        .table th { background-color: #2563eb; color: white; }
        .amount { text-align: right; font-family: monospace; }
        .footer { position: fixed; bottom: 5px; width: 100%; text-align: center; font-size: 7px; border-top: 1px solid #ddd; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Variance Analysis Report</div>
            <div>{{ $analysis->analysis_display }} - {{ $payroll->payroll_name }}</div>
        </div>

        <div class="summary-box">
            <h3 style="margin-bottom: 10px;">Analysis Summary</h3>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-cell"><strong>Baseline Period:</strong></div>
                    <div class="summary-cell">{{ $analysis->baseline_period->format('F Y') }}</div>
                    <div class="summary-cell"><strong>Comparison Period:</strong></div>
                    <div class="summary-cell">{{ $analysis->comparison_period->format('F Y') }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell"><strong>Variance (ZWG):</strong></div>
                    <div class="summary-cell {{ $analysis->total_variance_zwg >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($analysis->total_variance_zwg, 2) }}
                    </div>
                    <div class="summary-cell"><strong>Variance (USD):</strong></div>
                    <div class="summary-cell {{ $analysis->total_variance_usd >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($analysis->total_variance_usd, 2) }}
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell"><strong>Variance Percentage:</strong></div>
                    <div class="summary-cell {{ $analysis->variance_percentage >= 0 ? 'positive' : 'negative' }}" colspan="3">
                        {{ number_format($analysis->variance_percentage, 2) }}%
                    </div>
                </div>
            </div>
        </div>

        @if($analysis->analysis_type === 'detailed' && $details->count() > 0)
        <h3 style="margin: 15px 0 10px 0;">Detailed Breakdown</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="width: 15%;">Baseline</th>
                    <th style="width: 15%;">Comparison</th>
                    <th style="width: 15%;">Variance</th>
                    <th style="width: 10%;">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $detail)
                <tr>
                    <td>{{ $detail->item_name }}</td>
                    <td class="amount">{{ number_format($detail->baseline_amount, 2) }}</td>
                    <td class="amount">{{ number_format($detail->comparison_amount, 2) }}</td>
                    <td class="amount {{ $detail->variance_amount >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($detail->variance_amount, 2) }}
                    </td>
                    <td class="amount {{ $detail->variance_percentage >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($detail->variance_percentage, 2) }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="footer">
            Generated: {{ $analysis->generated_at->format('d M Y H:i') }} | {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
