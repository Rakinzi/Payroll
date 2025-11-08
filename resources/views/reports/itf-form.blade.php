<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ITF {{ $form->form_type }} - {{ $form->tax_year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #000; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; margin: 5px 0; }
        .form-info { margin: 15px 0; padding: 10px; background-color: #f8fafc; border: 1px solid #ddd; }
        .info-row { margin: 5px 0; }
        .label { font-weight: bold; display: inline-block; width: 150px; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 9px; }
        .table th { background-color: #e2e8f0; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f8fafc; }
        .amount { text-align: right; font-family: monospace; }
        .totals { margin-top: 20px; padding: 12px; border: 2px solid #000; }
        .total-row { margin: 8px 0; font-size: 11px; }
        .signature { margin-top: 40px; }
        .signature-line { margin: 30px 0; }
        .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 8px; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">INLAND TAXES FORM {{ $form->form_type }}</div>
            <div>{{ $form->form_type === 'ITF16' ? 'Tax Certificate' : 'Annual Return of Emoluments' }}</div>
            <div>Tax Year: {{ $form->tax_year }}</div>
        </div>

        <div class="form-info">
            <div class="info-row">
                <span class="label">Employer:</span>
                <span>{{ config('app.name', 'Company Name') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Payroll:</span>
                <span>{{ $payroll->payroll_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Currency:</span>
                <span>{{ $form->currency }}</span>
            </div>
            <div class="info-row">
                <span class="label">Generated:</span>
                <span>{{ $form->generated_at->format('d F Y H:i') }}</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Employee Name</th>
                    <th style="width: 15%;">ID Number</th>
                    <th style="width: 18%;">Gross Income</th>
                    <th style="width: 18%;">Taxable Income</th>
                    <th style="width: 19%;">Tax Deducted</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->employee_name }}</td>
                    <td>{{ $detail->nat_id ?? 'N/A' }}</td>
                    <td class="amount">{{ number_format($detail->gross_income, 2) }}</td>
                    <td class="amount">{{ number_format($detail->taxable_income, 2) }}</td>
                    <td class="amount">{{ number_format($detail->tax_deducted, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No employee records available</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <strong>Total Gross Income ({{ $form->currency }}):</strong>
                <span style="float: right;">{{ number_format($form->total_gross_income, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Total Taxable Income ({{ $form->currency }}):</strong>
                <span style="float: right;">{{ number_format($form->total_taxable_income, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Total Tax Deducted ({{ $form->currency }}):</strong>
                <span style="float: right;">{{ number_format($form->total_tax_deducted, 2) }}</span>
            </div>
            <div class="total-row">
                <strong>Total Employees:</strong>
                <span style="float: right;">{{ $form->employee_count }}</span>
            </div>
        </div>

        <div class="signature">
            <div class="signature-line">
                <p>Prepared by: _________________________________________</p>
            </div>
            <div class="signature-line">
                <p>Date: _______________________________________________</p>
            </div>
            <div class="signature-line">
                <p>Authorized Signature: ________________________________</p>
            </div>
        </div>

        <div class="footer">
            This is a computer-generated document | Generated: {{ now()->format('d M Y H:i') }} | {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
