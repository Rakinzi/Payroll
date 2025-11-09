# Default and Custom Transactions - Implementation Status

## Completed Components

### ✅ Database Layer (100%)
Created 4 migration files:
1. **2025_11_09_100001_create_default_transactions_table.php**
   - Main table for default transactions
   - Unique constraint on code_id + period_id + center_id + currency
   - Foreign keys to transaction_codes, periods, and cost_centers

2. **2025_11_09_100002_create_custom_transactions_tbl_table.php**
   - Main table for custom transactions
   - Supports both basic salary and custom amount calculations
   - Stores worked_hours and base_hours for prorated calculations

3. **2025_11_09_100003_create_custom_transactions_employees_tbl_table.php**
   - Junction table for employee assignments
   - Many-to-many relationship between custom transactions and employees
   - Unique constraint prevents duplicate assignments

4. **2025_11_09_100004_create_custom_transactions_tag_tbl_table.php**
   - Junction table for transaction code assignments
   - Many-to-many relationship between custom transactions and codes
   - Unique constraint prevents duplicate code assignments

### ✅ Models (100%)
Created 2 comprehensive models with business logic:

1. **DefaultTransaction Model** (`app/Models/DefaultTransaction.php`)
   - Relationships: transactionCode, period, center
   - Computed attributes: total_amount, effect_display, currency_symbol
   - Scopes: forPeriod, forCenter, currentPeriod
   - Business methods:
     - `canBeModifiedBy()` - Authorization check
     - `validateUniqueness()` - Prevents duplicate transactions
   - Validation rules included

2. **CustomTransaction Model** (`app/Models/CustomTransaction.php`)
   - Relationships: center, period, employees (many-to-many), transactionCodes (many-to-many)
   - Computed attributes: employee_count, transaction_count, amount_type, work_ratio
   - Complex business methods:
     - `calculateAmountForEmployee()` - Prorated calculations with currency support
     - `assignToEmployees()` - Handles 'all' employees or specific assignments
     - `assignTransactionCodes()` - Manages transaction code assignments
     - `canBeModifiedBy()` - Authorization check
   - Validation rules included

## Next Steps Required

### Backend Components (Remaining)

#### 1. Controllers (Priority: HIGH)
- [ ] **DefaultTransactionController**
  - index() - List default transactions for current period
  - store() - Bulk save default transactions
  - destroy() - Delete individual transaction
  - Additional endpoints for AJAX operations

- [ ] **CustomTransactionController**
  - index() - List custom transactions with pagination
  - store() - Create new custom transaction
  - show() - View transaction details
  - update() - Modify existing transaction
  - destroy() - Delete transaction
  - getEmployees() - Get assigned employees (AJAX)
  - getCodes() - Get assigned codes (AJAX)

#### 2. Routes (Priority: HIGH)
Need to add to `/routes/tenant.php`:

```php
// Default Transactions Routes
Route::prefix('default-transactions')->name('default-transactions.')->group(function () {
    Route::get('/', [DefaultTransactionController::class, 'index'])->name('index');
    Route::post('/', [DefaultTransactionController::class, 'store'])->name('store');
    Route::delete('/{transaction}', [DefaultTransactionController::class, 'destroy'])->name('destroy');
});

// Custom Transactions Routes
Route::prefix('custom-transactions')->name('custom-transactions.')->group(function () {
    Route::get('/', [CustomTransactionController::class, 'index'])->name('index');
    Route::post('/', [CustomTransactionController::class, 'store'])->name('store');
    Route::get('/{transaction}', [CustomTransactionController::class, 'show'])->name('show');
    Route::put('/{transaction}', [CustomTransactionController::class, 'update'])->name('update');
    Route::delete('/{transaction}', [CustomTransactionController::class, 'destroy'])->name('destroy');

    // AJAX endpoints
    Route::get('/{transaction}/employees', [CustomTransactionController::class, 'getEmployees'])->name('employees');
    Route::get('/{transaction}/codes', [CustomTransactionController::class, 'getCodes'])->name('codes');
});
```

#### 3. Policies (Priority: MEDIUM)
- [ ] **DefaultTransactionPolicy**
  - viewAny, view, create, update, delete methods

- [ ] **CustomTransactionPolicy**
  - viewAny, view, create, update, delete methods

### Frontend Components (Remaining)

#### 1. React Query Hooks (Priority: HIGH)
- [ ] **use-default-transactions.ts**
  - Queries: useDefaultTransactions, useDefaultTransactionsByPeriod
  - Mutations: useCreateDefaultTransactions, useDeleteDefaultTransaction

- [ ] **use-custom-transactions.ts**
  - Queries: useCustomTransactions, useCustomTransactionDetails
  - Mutations: useCreateCustomTransaction, useUpdateCustomTransaction, useDeleteCustomTransaction

#### 2. Zustand Stores (Priority: MEDIUM)
- [ ] **default-transaction-store.ts**
  - State: current period, transactions array, form state
  - Actions: addRow, removeRow, updateRow, reset

- [ ] **custom-transaction-store.ts**
  - State: filters, selected transaction, form state
  - Actions: setFilters, openForm, closeForm, reset

#### 3. Components (Priority: HIGH)
- [ ] **DefaultTransactionForm.tsx**
  - Multi-row form for adding transactions
  - Dynamic row addition/removal
  - Validation and submission

- [ ] **DefaultTransactionTable.tsx**
  - Display existing default transactions
  - Delete functionality
  - Currency and amount display

- [ ] **CustomTransactionForm.tsx**
  - Complex form with employee/code multi-select
  - Amount type toggle (basic salary vs custom amount)
  - Hours worked calculation display

- [ ] **CustomTransactionTable.tsx**
  - Paginated list of custom transactions
  - Edit/delete actions
  - Employee and code count badges

#### 4. Pages (Priority: HIGH)
- [ ] **DefaultTransactions/Index.tsx**
  - Main page for default transactions management
  - Only accessible for current period
  - Integrates form and table components

- [ ] **CustomTransactions/Index.tsx**
  - Main page for custom transactions management
  - Payroll and period filters
  - Integrates form and table components

## Integration Points

### Payroll Processing Integration
The transaction models need to be integrated into the PayrollProcessor service:

1. **During Period Run** (`PayrollProcessor::processEmployee`)
   - Fetch default transactions for period/center
   - Apply to all employees
   - Fetch custom transactions for specific employee
   - Calculate prorated amounts
   - Save to payslip transactions

2. **During Period Refresh** (`PayrollProcessor::refreshPeriod`)
   - Re-fetch and recalculate all transactions
   - Update existing payslip transactions

### Dependencies
- TransactionCode model (exists)
- AccountingPeriod model (exists)
- CostCenter model (exists)
- Employee model (exists)
- PayrollProcessor service (needs integration)

## Testing Requirements

### Backend Tests
- [ ] Unit tests for models
  - DefaultTransaction calculations
  - CustomTransaction prorated calculations
  - Employee assignment logic

- [ ] Feature tests for controllers
  - CRUD operations
  - Authorization checks
  - Validation rules

### Frontend Tests
- [ ] Component tests
  - Form validation
  - Row addition/removal
  - Multi-select functionality

- [ ] Integration tests
  - Full workflow testing
  - API integration

## Database Schema Summary

```
default_transactions
├── default_id (PK)
├── code_id (FK → transaction_codes)
├── period_id (FK → payroll_accounting_periods)
├── center_id (FK → cost_centers)
├── transaction_effect ('+' or '-')
├── employee_amount (decimal)
├── employer_amount (decimal)
├── hours_worked (decimal)
├── transaction_currency ('ZWL' or 'USD')
└── timestamps

custom_transactions_tbl
├── custom_id (PK)
├── center_id (FK → cost_centers)
├── period_id (FK → payroll_accounting_periods)
├── worked_hours (decimal)
├── base_hours (decimal, default 176)
├── base_amount (decimal, nullable)
├── use_basic (boolean)
└── timestamps

custom_transactions_employees_tbl
├── assignment_id (PK)
├── custom_id (FK → custom_transactions_tbl)
├── employee_id (FK → employees)
└── timestamps

custom_transactions_tag_tbl
├── tag_id (PK)
├── custom_id (FK → custom_transactions_tbl)
├── code_id (FK → transaction_codes)
└── timestamps
```

## Estimated Completion Time

Based on remaining work:
- Backend (Controllers + Routes + Policies): 3-4 hours
- Frontend (Hooks + Stores + Components + Pages): 6-8 hours
- Integration with PayrollProcessor: 2-3 hours
- Testing: 4-5 hours

**Total Estimated Time: 15-20 hours**

## Notes for Implementation

1. **Currency Handling**: All calculations should support both ZWL and USD with proper exchange rate integration
2. **Prorated Calculations**: Custom transactions use complex prorated logic based on hours worked vs base hours
3. **Shift Allowance Exception**: Hours worked can exceed base hours for shift allowance transaction codes
4. **Authorization**: Center-based isolation is critical - users should only see/modify their center's transactions
5. **Period Restrictions**: Default transactions are only available for the current active period
6. **Bulk Operations**: Default transactions support bulk save/delete for efficiency
7. **Multi-Select**: Custom transactions support both individual and "all employees" assignment

## Current Status

**Overall Completion: 30%**

✅ Database migrations complete
✅ Models complete with full business logic
⏳ Controllers pending
⏳ Routes pending
⏳ Policies pending
⏳ Frontend completely pending

The foundation is solid with well-structured database schema and comprehensive models. The remaining work is primarily CRUD operations, UI components, and integration with the existing payroll processing system.
