# 2025 Zimbabwe Tax Tables Guide

This guide documents the official 2025 ZIMRA tax rates implemented in the payroll system.

## Current Status

The system includes a **TaxTables2025Seeder** with **official ZIMRA tax rates for 2025**. The tax tables are ready to be seeded into the database.

## Official 2025 ZIMRA Tax Rates

The seeder contains the following official tax brackets:

### USD Tax Bands

**Monthly (Primary payroll calculation):**
- $0 - $100: 0% (deduct $0)
- $100.01 - $300: 20% (deduct $20)
- $300.01 - $1,000: 25% (deduct $35)
- $1,000.01 - $2,000: 30% (deduct $85)
- $2,000.01 - $3,000: 35% (deduct $185)
- $3,000.01+: 40% (deduct $335)

**Annual:**
- $0 - $1,200: 0% (deduct $0)
- $1,200.01 - $3,600: 20% (deduct $240)
- $3,600.01 - $12,000: 25% (deduct $420)
- $12,000.01 - $24,000: 30% (deduct $1,020)
- $24,000.01 - $36,000: 35% (deduct $2,220)
- $36,000.01+: 40% (deduct $4,020)

### ZWG Tax Bands

**Monthly (Primary payroll calculation):**
- ZWG 0 - 2,800: 0% (deduct ZWG 0)
- ZWG 2,800.01 - 8,400: 20% (deduct ZWG 560)
- ZWG 8,400.01 - 28,000: 25% (deduct ZWG 980)
- ZWG 28,000.01 - 56,000: 30% (deduct ZWG 2,380)
- ZWG 56,000.01 - 84,000: 35% (deduct ZWG 5,180)
- ZWG 84,000.01+: 40% (deduct ZWG 9,380)

**Annual:**
- ZWG 0 - 33,600: 0% (deduct ZWG 0)
- ZWG 33,600.01 - 100,800: 20% (deduct ZWG 6,720)
- ZWG 100,800.01 - 336,000: 25% (deduct ZWG 11,760)
- ZWG 336,000.01 - 672,000: 30% (deduct ZWG 28,560)
- ZWG 672,000.01 - 1,008,000: 35% (deduct ZWG 62,160)
- ZWG 1,008,000.01+: 40% (deduct ZWG 112,560)

## How to Apply Tax Tables to Database

### Run the Seeder

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

## Example Tax Calculations

### Example 1: Monthly USD Salary of $1,800
Using the monthly USD tax bands:
- Bracket: $1,000.01 - $2,000 → 30% rate, deduct $85
- Tax = ($1,800 × 30%) - $85
- Tax = $540 - $85
- **Tax = $455.00**

### Example 2: Monthly ZWG Salary of ZWG 18,000
Using the monthly ZWG tax bands:
- Bracket: ZWG 8,400.01 - 28,000 → 25% rate, deduct ZWG 980
- Tax = (ZWG 18,000 × 25%) - ZWG 980
- Tax = ZWG 4,500 - ZWG 980
- **Tax = ZWG 3,520.00**

### Example 3: Annual USD Salary of $32,000
Using the annual USD tax bands:
- Bracket: $24,000.01 - $36,000 → 35% rate, deduct $2,220
- Tax = ($32,000 × 35%) - $2,220
- Tax = $11,200 - $2,220
- **Tax = $8,980.00**

**Note:** AIDS Levy (3% of calculated tax) is applied separately.

## Important Notes

1. **Backup First**: Always backup the database before running seeders in production
2. **Test Thoroughly**: Test calculations with sample salaries after seeding
3. **AIDS Levy**: Remember that a 3% AIDS Levy is applied on top of the calculated PAYE (handled separately in the tax calculator)
4. **Official Rates**: These rates are from the official ZIMRA PAYE Tax Tables for 1 January to 31 December 2025
5. **Progressive Taxation**: The deduction amounts ensure proper progressive tax calculation across brackets

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
