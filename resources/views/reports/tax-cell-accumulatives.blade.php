<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Cell Accumulatives - {{ $cellAccumulative->tax_year }}</title>
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
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 8px; }
        .table th { background-color: #2563eb; color: white; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        .amount { text-align: right; font-family: monospace; }
        .bracket-section { margin: 20px 0; }
        .bracket-header { background-color: #dbeafe; padding: 8px; font-weight: bold; margin-top: 15px; border-left: 4px solid #2563eb; }
        .footer { position: fixed; bottom: 5px; width: 100%; text-align: center; font-size: 7px; border-top: 1px solid #ddd; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Tax Cell Accumulatives Report</div>
            <div>{{ $payroll->payroll_name }}</div>
            <div>Tax Year: {{ $cellAccumulative->tax_year }} | Currency: {{ $cellAccumulative->currency }}</div>
        </div>

        <div class="summary-box">
            <h3 style="margin-bottom: 10px;">Summary</h3>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-cell"><strong>Tax Year:</strong></div>
                    <div class="summary-cell">{{ $cellAccumulative->tax_year }}</div>
                    <div class="summary-cell"><strong>Currency:</strong></div>
                    <div class="summary-cell">{{ $cellAccumulative->currency_display }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell"><strong>Total Employees:</strong></div>
                    <div class="summary-cell">{{ $cellAccumulative->employee_count }}</div>
                    <div class="summary-cell"><strong>Generated:</strong></div>
                    <div class="summary-cell">{{ $cellAccumulative->generated_at->format('d M Y H:i') }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell"><strong>Total Income:</strong></div>
                    <div class="summary-cell">{{ number_format($cellAccumulative->total_income, 2) }}</div>
                    <div class="summary-cell"><strong>Total Tax:</strong></div>
                    <div class="summary-cell">{{ number_format($cellAccumulative->total_tax, 2) }}</div>
                </div>
            </div>
        </div>

        @php
            $groupedDetails = $details->groupBy('tax_bracket');
        @endphp

        @foreach($groupedDetails as $bracket => $bracketDetails)
        <div class="bracket-section">
            <div class="bracket-header">
                Tax Bracket: {{ $bracket }}
                ({{ $bracketDetails->first()->bracket_range }} @ {{ number_format($bracketDetails->first()->tax_rate, 2) }}%)
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">Employee Name</th>
                        <th style="width: 18%;">ID Number</th>
                        <th style="width: 22%;">Income in Bracket</th>
                        <th style="width: 15%;">Tax in Bracket</th>
                        <th style="width: 10%;">Eff. Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bracketDetails as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail->employee_name }}</td>
                        <td>{{ $detail->nat_id ?? 'N/A' }}</td>
                        <td class="amount">{{ number_format($detail->ytd_income_in_bracket, 2) }}</td>
                        <td class="amount">{{ number_format($detail->ytd_tax_in_bracket, 2) }}</td>
                        <td class="amount">{{ number_format($detail->effective_rate, 2) }}%</td>
                    </tr>
                    @endforeach
                    <tr style="font-weight: bold; background-color: #e0f2fe;">
                        <td colspan="3" style="text-align: right;">Bracket Totals:</td>
                        <td class="amount">{{ number_format($bracketDetails->sum('ytd_income_in_bracket'), 2) }}</td>
                        <td class="amount">{{ number_format($bracketDetails->sum('ytd_tax_in_bracket'), 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endforeach

        <div class="footer">
            Generated: {{ $cellAccumulative->generated_at->format('d M Y H:i') }} | {{ config('app.name') }} - Tax Cell Accumulatives
        </div>
    </div>
</body>
</html>
