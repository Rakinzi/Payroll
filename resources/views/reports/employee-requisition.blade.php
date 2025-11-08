<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Requisition Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #7c3aed; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; color: #7c3aed; }
        .subtitle { font-size: 12px; margin: 5px 0; }
        .info-box { margin: 15px 0; padding: 10px; background-color: #faf5ff; border: 1px solid #d8b4fe; }
        .info-row { margin: 5px 0; }
        .label { font-weight: bold; display: inline-block; width: 150px; }
        .stats-grid { display: table; width: 100%; margin: 20px 0; }
        .stats-row { display: table-row; }
        .stats-cell { display: table-cell; width: 25%; padding: 15px; text-align: center; border: 1px solid #d8b4fe; background-color: #faf5ff; }
        .stat-number { font-size: 24px; font-weight: bold; color: #7c3aed; display: block; margin-bottom: 5px; }
        .stat-label { font-size: 11px; color: #6b7280; }
        .metrics-section { margin: 20px 0; padding: 15px; background-color: #f9fafb; border: 1px solid #d1d5db; }
        .metric-row { margin: 10px 0; padding: 8px; background-color: white; border-left: 4px solid #7c3aed; }
        .metric-label { font-weight: bold; display: inline-block; width: 200px; }
        .metric-value { font-size: 14px; font-weight: bold; color: #7c3aed; }
        .health-indicator { padding: 5px 10px; border-radius: 4px; font-weight: bold; display: inline-block; }
        .health-critical { background-color: #fee2e2; color: #991b1b; }
        .health-warning { background-color: #fef3c7; color: #92400e; }
        .health-healthy { background-color: #d1fae5; color: #065f46; }
        .analysis-box { margin: 20px 0; padding: 15px; background-color: #eff6ff; border: 1px solid #bfdbfe; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Employee Requisition Report</div>
            <div class="subtitle">{{ config('app.name', 'Company Name') }}</div>
            <div>{{ $payroll->payroll_name }}</div>
            <div>Period: {{ $requisition->period_start->format('d M Y') }} - {{ $requisition->period_end->format('d M Y') }}</div>
        </div>

        <div class="info-box">
            <div class="info-row">
                <span class="label">Payroll:</span>
                <span>{{ $payroll->payroll_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Analysis Period:</span>
                <span>{{ $requisition->period_display }}</span>
            </div>
            <div class="info-row">
                <span class="label">Generated:</span>
                <span>{{ $requisition->generated_at->format('d F Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Staffing Health Status:</span>
                <span class="health-indicator health-{{ $requisition->staffing_health }}">
                    {{ strtoupper($requisition->staffing_health) }}
                </span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stats-row">
                <div class="stats-cell">
                    <span class="stat-number">{{ $requisition->total_active_employees }}</span>
                    <span class="stat-label">Active Employees</span>
                </div>
                <div class="stats-cell">
                    <span class="stat-number" style="color: #059669;">{{ $requisition->total_hired }}</span>
                    <span class="stat-label">New Hires</span>
                </div>
                <div class="stats-cell">
                    <span class="stat-number" style="color: #dc2626;">{{ $requisition->total_terminated }}</span>
                    <span class="stat-label">Terminations</span>
                </div>
                <div class="stats-cell">
                    <span class="stat-number" style="color: {{ $requisition->net_change >= 0 ? '#059669' : '#dc2626' }};">
                        {{ $requisition->net_change >= 0 ? '+' : '' }}{{ $requisition->net_change }}
                    </span>
                    <span class="stat-label">Net Change</span>
                </div>
            </div>
        </div>

        <div class="metrics-section">
            <h3 style="margin-bottom: 15px; color: #7c3aed;">Key Metrics</h3>

            <div class="metric-row">
                <span class="metric-label">Turnover Rate:</span>
                <span class="metric-value" style="color: {{ $requisition->turnover_rate > 15 ? '#dc2626' : '#059669' }};">
                    {{ number_format($requisition->turnover_rate, 2) }}%
                </span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Hiring Rate:</span>
                <span class="metric-value" style="color: #059669;">
                    {{ number_format($requisition->hiring_rate, 2) }}%
                </span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Termination Rate:</span>
                <span class="metric-value" style="color: #dc2626;">
                    {{ number_format($requisition->termination_rate, 2) }}%
                </span>
            </div>

            <div class="metric-row">
                <span class="metric-label">Net Change Percentage:</span>
                <span class="metric-value" style="color: {{ $requisition->net_change_percentage >= 0 ? '#059669' : '#dc2626' }};">
                    {{ $requisition->net_change_percentage >= 0 ? '+' : '' }}{{ number_format($requisition->net_change_percentage, 2) }}%
                </span>
            </div>
        </div>

        <div class="analysis-box">
            <h3 style="margin-bottom: 10px; color: #1e40af;">Staffing Analysis</h3>

            @if($requisition->turnover_rate > 20)
            <div style="margin: 10px 0; padding: 10px; background-color: #fee2e2; border-left: 4px solid #dc2626;">
                <strong style="color: #dc2626;">CRITICAL TURNOVER RATE</strong><br>
                The current turnover rate of {{ number_format($requisition->turnover_rate, 2) }}% is significantly above the healthy threshold of 10-15%.
                This indicates potential issues with employee retention that require immediate attention.
            </div>
            @elseif($requisition->turnover_rate > 10)
            <div style="margin: 10px 0; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b;">
                <strong style="color: #92400e;">ELEVATED TURNOVER RATE</strong><br>
                The current turnover rate of {{ number_format($requisition->turnover_rate, 2) }}% is above the normal range.
                Consider reviewing retention strategies and employee satisfaction metrics.
            </div>
            @else
            <div style="margin: 10px 0; padding: 10px; background-color: #d1fae5; border-left: 4px solid #059669;">
                <strong style="color: #065f46;">HEALTHY TURNOVER RATE</strong><br>
                The current turnover rate of {{ number_format($requisition->turnover_rate, 2) }}% is within the acceptable range (below 10%).
            </div>
            @endif

            @if($requisition->net_change < 0)
            <div style="margin: 10px 0; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b;">
                <strong style="color: #92400e;">WORKFORCE REDUCTION</strong><br>
                The workforce decreased by {{ abs($requisition->net_change) }} employee(s) ({{ number_format(abs($requisition->net_change_percentage), 2) }}%) during this period.
                Staffing levels should be reviewed against operational requirements.
            </div>
            @elseif($requisition->net_change > 0)
            <div style="margin: 10px 0; padding: 10px; background-color: #dbeafe; border-left: 4px solid #2563eb;">
                <strong style="color: #1e40af;">WORKFORCE GROWTH</strong><br>
                The workforce increased by {{ $requisition->net_change }} employee(s) ({{ number_format($requisition->net_change_percentage, 2) }}%) during this period.
            </div>
            @endif

            <div style="margin-top: 15px;">
                <strong>Recommendations:</strong>
                <ul style="margin-left: 20px; margin-top: 8px; line-height: 1.6;">
                    @if($requisition->turnover_rate > 15)
                    <li>Conduct exit interviews to identify root causes of turnover</li>
                    <li>Review compensation and benefits competitiveness</li>
                    <li>Implement retention programs for key talent</li>
                    @endif
                    @if($requisition->total_hired > $requisition->total_terminated)
                    <li>Ensure onboarding programs are scaled to handle increased hiring volume</li>
                    <li>Review training resources to support new employee integration</li>
                    @endif
                    @if($requisition->net_change < 0)
                    <li>Assess impact on remaining workforce and redistribute responsibilities if needed</li>
                    <li>Consider succession planning for critical roles</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="footer">
            Generated: {{ now()->format('d M Y H:i') }} | {{ config('app.name') }} - Employee Requisition Report
        </div>
    </div>
</body>
</html>
