<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retirement Warning Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; }
        .container { width: 100%; padding: 10px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; color: #dc2626; }
        .summary-box { padding: 12px; background-color: #fef2f2; border: 1px solid #fecaca; margin: 15px 0; }
        .summary-grid { display: table; width: 100%; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; padding: 6px; border: 1px solid #fecaca; }
        .stat-highlight { font-size: 18px; font-weight: bold; color: #dc2626; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 8px; }
        .table th { background-color: #dc2626; color: white; }
        .table tr:nth-child(even) { background-color: #fef2f2; }
        .amount { text-align: right; font-family: monospace; }
        .status-overdue { color: #dc2626; font-weight: bold; }
        .status-imminent { color: #ea580c; font-weight: bold; }
        .status-approaching { color: #ca8a04; font-weight: bold; }
        .urgency-badge { padding: 3px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; text-align: center; }
        .urgency-3 { background-color: #fee2e2; color: #991b1b; }
        .urgency-2 { background-color: #fed7aa; color: #9a3412; }
        .urgency-1 { background-color: #fef3c7; color: #92400e; }
        .footer { position: fixed; bottom: 5px; width: 100%; text-align: center; font-size: 7px; border-top: 1px solid #ddd; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Retirement Warning Report</div>
            <div>{{ $payroll->payroll_name }}</div>
            <div>Threshold: {{ $warning->warning_threshold_months }} months</div>
        </div>

        <div class="summary-box">
            <h3 style="margin-bottom: 10px;">Summary</h3>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-cell"><strong>Payroll:</strong></div>
                    <div class="summary-cell" colspan="3">{{ $payroll->payroll_name }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell"><strong>Warning Threshold:</strong></div>
                    <div class="summary-cell">{{ $warning->warning_threshold_months }} months</div>
                    <div class="summary-cell"><strong>Generated:</strong></div>
                    <div class="summary-cell">{{ $warning->generated_at->format('d M Y H:i') }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell"><strong>Total Warnings:</strong></div>
                    <div class="summary-cell"><span class="stat-highlight">{{ $warning->total_warnings }}</span></div>
                    <div class="summary-cell"><strong>Breakdown:</strong></div>
                    <div class="summary-cell">
                        <span class="status-overdue">{{ $warning->overdue_count }} Overdue</span>,
                        <span class="status-imminent">{{ $warning->imminent_count }} Imminent</span>,
                        <span class="status-approaching">{{ $warning->approaching_count }} Approaching</span>
                    </div>
                </div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 24%;">Employee Name</th>
                    <th style="width: 12%;">ID Number</th>
                    <th style="width: 10%;">Current Age</th>
                    <th style="width: 10%;">Service Years</th>
                    <th style="width: 12%;">Retirement Date</th>
                    <th style="width: 10%;">Months Left</th>
                    <th style="width: 10%;">Years Left</th>
                    <th style="width: 8%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->employee_name }}</td>
                    <td>{{ $detail->nat_id ?? 'N/A' }}</td>
                    <td class="amount">{{ $detail->current_age }}</td>
                    <td class="amount">{{ $detail->years_of_service }}</td>
                    <td>{{ $detail->projected_retirement_date->format('d M Y') }}</td>
                    <td class="amount">{{ $detail->months_to_retirement }}</td>
                    <td class="amount">{{ $detail->years_to_retirement }}</td>
                    <td style="text-align: center;">
                        <span class="urgency-badge urgency-{{ $detail->urgency_level }}">
                            {{ ucfirst($detail->warning_status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">No employees approaching retirement</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($details->count() > 0)
        <div style="margin-top: 20px; padding: 10px; background-color: #fffbeb; border: 1px solid #fcd34d;">
            <h4 style="margin-bottom: 8px; color: #92400e;">Action Items</h4>
            <ul style="margin-left: 20px; line-height: 1.6;">
                @if($warning->overdue_count > 0)
                <li style="color: #dc2626;"><strong>URGENT:</strong> {{ $warning->overdue_count }} employee(s) have passed retirement date - immediate action required</li>
                @endif
                @if($warning->imminent_count > 0)
                <li style="color: #ea580c;"><strong>HIGH PRIORITY:</strong> {{ $warning->imminent_count }} employee(s) retiring within 6 months - begin succession planning</li>
                @endif
                @if($warning->approaching_count > 0)
                <li style="color: #ca8a04;">{{ $warning->approaching_count }} employee(s) approaching retirement - start knowledge transfer planning</li>
                @endif
            </ul>
        </div>
        @endif

        <div class="footer">
            Generated: {{ $warning->generated_at->format('d M Y H:i') }} | {{ config('app.name') }} - Retirement Warning Report
        </div>
    </div>
</body>
</html>
