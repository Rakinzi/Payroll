# 2025 Zimbabwe Tax Tables Update Guide

This guide explains how to update the payroll system with official 2025 ZIMRA tax rates.

## Current Status

The system includes a **TaxTables2025Seeder** with placeholder progressive tax rates. These need to be updated with the official 2025 ZIMRA tax tables.

## How to Update Tax Tables

### Step 1: Edit the Seeder

Open: `database/seeders/TaxTables2025Seeder.php`

### Step 2: Update Tax Brackets

Replace the placeholder arrays with official 2025 ZIMRA rates:

#### Annual USD Tax Bands
```php
$annualUsdBands = [
    ['min' => 0, 'max' => X, 'rate' => Y, 'amount' => Z],
    // Add all official brackets here
];
```

#### Annual ZWG Tax Bands
```php
$annualZwgBands = [
    ['min' => 0, 'max' => X, 'rate' => Y, 'amount' => Z],
    // Add all official brackets here
];
```

#### Monthly Bands
Monthly bands are typically annual rates divided by 12. Update accordingly.

### Step 3: Run the Seeder

```bash
php artisan db:seed --class=TaxTables2025Seeder
```

This will:
- Clear existing tax bands
- Insert new 2025 tax rates
- Apply to all tax calculations immediately

## Tax Band Structure

Each tax band has 4 components:

1. **min_salary**: Lower threshold for this bracket
2. **max_salary**: Upper threshold (null for highest bracket)
3. **tax_rate**: Rate as decimal (e.g., 0.25 for 25%)
4. **tax_amount**: Fixed deduction amount for this bracket

## Example Tax Calculation

For an income of $100,000 with brackets:
- $0 - $60,000: 0% (tax: $0)
- $60,000 - $120,000: 20% (tax: $8,000 on $40,000)

Total tax: $8,000

## Important Notes

1. **Backup First**: Always backup the database before running seeders in production
2. **Test Thoroughly**: Test calculations with sample salaries after updating
3. **Document Changes**: Keep a record of when rates were updated
4. **Verify Compliance**: Ensure rates match official ZIMRA publications

## Tax Credits

Tax credits (elderly allowance, personal allowance, etc.) are configured separately in the `tax_credits` table and are applied before calculating final PAYE.

## Questions or Issues?

If tax calculations seem incorrect after updating:
1. Verify the tax bands were inserted correctly
2. Check the TaxCalculator service is using the right table (monthly vs annual)
3. Ensure exchange rates are current
4. Review tax credits are properly configured

## Related Files

- Tax Calculator: `app/Services/TaxCalculator.php`
- Tax Band Model: `app/Models/TaxBand.php`
- Tax Credits: Database table `tax_credits`
