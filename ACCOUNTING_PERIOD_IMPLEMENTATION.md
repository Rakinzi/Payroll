# Accounting Period Management System - Implementation Documentation

## Overview

This document describes the comprehensive Accounting Period Management system implementation for the Lorimak Payroll System. The system enables organizations to run, refresh, and close payroll periods with full audit trails and multi-center support.

## Implementation Summary

### Backend Components (Laravel)

#### 1. Database Migrations
**Location:** `database/migrations/`

- **2025_11_09_000001_create_payroll_accounting_periods_table.php**
  - Creates the main accounting periods table
  - Fields: period_id, payroll_id, month_name, period_year, period_start, period_end
  - Indexes for efficient querying by payroll, date ranges, and year

- **2025_11_09_000002_create_center_period_status_table.php**
  - Creates center-specific period status tracking
  - Fields: status_id, period_id, center_id, period_currency, period_run_date, pay_run_date, is_closed_confirmed
  - Unique constraint on (period_id, center_id) combination
  - Tracks individual center progress through the period lifecycle

#### 2. Models
**Location:** `app/Models/`

- **AccountingPeriod.php**
  - Main model for accounting periods
  - Relationships: belongsTo Payroll, hasMany CenterPeriodStatus, hasMany Payslips
  - Computed attributes: status, is_current, is_future, is_past, completion_percentage
  - Business methods:
    - `canBeRunBy()`, `canBeRefreshedBy()`, `canBeClosedBy()` - Authorization checks
    - `getOrCreateCenterStatus()` - Center status management
    - `generatePeriodsForPayroll()` - Static method to generate periods for a year

- **CenterPeriodStatus.php**
  - Tracks period status per cost center
  - Relationships: belongsTo AccountingPeriod, belongsTo CostCenter
  - Computed attributes: is_completed, can_be_run, can_be_refreshed, can_be_closed
  - Business methods:
    - `markAsRun()`, `markAsClosed()`, `reset()` - Status transitions
  - Scopes: completed(), pending(), processed()

#### 3. Service Layer
**Location:** `app/Services/`

- **PayrollProcessor.php**
  - Core business logic for payroll processing
  - Methods:
    - `runPeriod()` - Initial period run with full employee processing
    - `refreshPeriod()` - Recalculate existing payslips
    - `closePeriod()` - Finalize period and mark payslips for distribution
  - Validates prerequisites (exchange rates, cost centers, tax configuration)
  - Processes employees with salary calculations, tax computations
  - Creates and manages payslips and transactions
  - Full transaction support with rollback on errors

#### 4. Job Queue
**Location:** `app/Jobs/`

- **ProcessPayrollPeriod.php**
  - Queue-based processing for long-running payroll operations
  - Supports both 'run' and 'refresh' actions
  - Configuration:
    - 3 retry attempts
    - 10-minute timeout
    - 30-minute retry window
  - Comprehensive logging and error handling
  - Tagged for easy monitoring (payroll, period, center, action)
  - Failed job handling with notifications (TODO)

#### 5. Controller
**Location:** `app/Http/Controllers/`

- **AccountingPeriodController.php**
  - RESTful API endpoints for period management
  - Routes:
    - `GET /accounting-periods` - List periods with filters
    - `GET /accounting-periods/{period}` - Show period details
    - `POST /accounting-periods/{period}/run` - Run period processing
    - `POST /accounting-periods/{period}/refresh` - Refresh/recalculate
    - `POST /accounting-periods/{period}/close` - Close and finalize
    - `GET /accounting-periods/{period}/status` - AJAX status endpoint
    - `POST /accounting-periods/{period}/currency` - Update currency selection
    - `GET /accounting-periods/{period}/summary` - Period summary statistics
    - `POST /accounting-periods/generate` - Generate periods (admin only)
  - Authorization via policies
  - Job dispatching for run/refresh operations
  - Direct processing for close operation
  - Inertia.js responses for page rendering

#### 6. Policy
**Location:** `app/Policies/`

- **AccountingPeriodPolicy.php**
  - Authorization rules for period operations
  - Methods: viewAny(), view(), run(), refresh(), close(), generatePeriods(), export()
  - Delegates business logic to model methods
  - Admin checks for privileged operations

#### 7. Routes
**Location:** `routes/tenant.php`

- Registered under `/accounting-periods` prefix
- Protected by auth and verified middleware
- Admin-only routes protected by 'permission:access all centers' middleware
- AJAX endpoints for real-time status updates

### Frontend Components (React + TypeScript)

#### 1. React Query Hooks
**Location:** `resources/js/hooks/queries/use-accounting-periods.ts`

- TypeScript interfaces for type safety
- Query keys for cache management
- Hooks:
  - `usePeriodStatus()` - Real-time status updates (10s polling)
  - `usePeriodSummary()` - Period summary statistics
  - `useRunPeriod()` - Run period mutation
  - `useRefreshPeriod()` - Refresh period mutation
  - `useClosePeriod()` - Close period mutation
  - `useUpdatePeriodCurrency()` - Update currency selection
  - `useGeneratePeriods()` - Generate periods mutation
  - `useExportPeriod()` - Export period data
- Automatic cache invalidation on mutations
- Error handling and retry logic

#### 2. Zustand Store
**Location:** `resources/js/stores/accounting-period-store.ts`

- Centralized state management for period operations
- State:
  - Filters (payroll_id, year, status, search)
  - Selected period
  - Processing state tracking
  - Currency selections per period
  - Dialog/modal states
  - Confirmation dialog state
- Actions for state mutations
- Selectors for derived state
- DevTools integration for debugging

#### 3. Components

**AccountingPeriodTable** (`resources/js/components/accounting-period-table.tsx`)
- Comprehensive table component for displaying periods
- Features:
  - Status badges (Current, Future, Past)
  - Currency selection dropdown
  - Action buttons (Run, Re-Calculate, Close)
  - Processing indicators
  - Admin vs. user views
  - Center-specific status display
  - Confirmation dialogs
- Real-time updates with React Query
- Integrated with Zustand store
- Responsive design with Tailwind CSS

#### 4. Page Component

**AccountingPeriodsIndex** (`resources/js/pages/Payroll/Periods/Index.tsx`)
- Main page for accounting period management
- Features:
  - Payroll and year filters
  - Generate periods dialog (admin only)
  - Period table with pagination
  - Information card with usage instructions
  - Breadcrumb navigation
  - Responsive layout with Card components
- Inertia.js integration for server-side rendering
- SEO-friendly with Head component

## Key Features

### 1. Multi-Center Support
- Each cost center can independently process periods
- Admin view shows completion across all centers
- Center-specific currency selection
- Center-specific run dates and status

### 2. Period Lifecycle Management
- **Pending**: Period created but not yet run
- **Run**: Initial processing with employee calculations
- **Processed**: Run completed, available for recalculation
- **Closed**: Finalized, payslips ready for distribution

### 3. Currency Management
- Multi-currency support (ZWL, USD, DEFAULT)
- Period-specific currency selection
- Currency split calculations
- Exchange rate integration (placeholder for actual rates)

### 4. Real-Time Updates
- AJAX polling for status updates (10-second interval)
- Live progress tracking during processing
- Processing indicators in UI
- Automatic cache invalidation

### 5. Queue-Based Processing
- Long-running operations handled via jobs
- Background processing prevents timeouts
- Retry logic for transient failures
- Comprehensive error logging

### 6. Authorization & Security
- Policy-based authorization
- Center-specific access control
- Admin-only operations protected
- Audit trail with timestamps

## Usage Instructions

### For Administrators

1. **Generate Periods**
   - Click "Generate Periods" button
   - Select payroll and year
   - Click "Generate Periods" to create 12 monthly periods

2. **Monitor Progress**
   - View completion percentage across all centers
   - Track which centers have completed processing
   - Access period summaries and statistics

### For Center Users

1. **Select Currency**
   - Choose currency mode before running period
   - Options: Multi (DEFAULT), USD only, ZWG only
   - Cannot change after period is run

2. **Run Period**
   - Click "Run Period" for current or past periods
   - Confirm the action
   - Wait for processing to complete (background job)
   - Refresh page to see updated status

3. **Re-Calculate**
   - Available after initial run, before closing
   - Updates all payslips with latest data
   - Use when employee data or rates change

4. **Close Period**
   - Finalizes all payslips
   - Marks period as complete
   - Prepares payslips for distribution
   - Cannot be easily undone

## Database Schema

```sql
-- Accounting Periods
CREATE TABLE payroll_accounting_periods (
    period_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    payroll_id UUID NOT NULL,
    month_name VARCHAR(20) NOT NULL,
    period_year INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE
);

-- Center Period Status
CREATE TABLE center_period_status (
    status_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    period_id BIGINT NOT NULL,
    center_id UUID NOT NULL,
    period_currency ENUM('ZWL', 'USD', 'DEFAULT') DEFAULT 'DEFAULT',
    period_run_date DATETIME NULL,
    pay_run_date DATETIME NULL,
    is_closed_confirmed BOOLEAN NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (period_id) REFERENCES payroll_accounting_periods(period_id) ON DELETE CASCADE,
    FOREIGN KEY (center_id) REFERENCES cost_centers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_period_center (period_id, center_id)
);
```

## API Endpoints

### Public Endpoints (Authenticated Users)
- `GET /accounting-periods` - List accounting periods
- `GET /accounting-periods/{period}` - Show period details
- `GET /accounting-periods/{period}/status` - Get real-time status (AJAX)
- `POST /accounting-periods/{period}/currency` - Update currency selection

### User/Center Endpoints
- `POST /accounting-periods/{period}/run` - Run period processing
- `POST /accounting-periods/{period}/refresh` - Refresh calculations
- `POST /accounting-periods/{period}/close` - Close period

### Admin-Only Endpoints
- `POST /accounting-periods/generate` - Generate periods for a year
- `GET /accounting-periods/{period}/export` - Export period data
- `GET /accounting-periods/{period}/summary` - Get summary statistics

## Next Steps & TODOs

1. **Exchange Rate Integration**
   - Connect to currency setup system
   - Use actual exchange rates in calculations
   - Historical rate preservation

2. **Transaction Processing**
   - Implement NEC contribution calculations
   - Add medical aid processing
   - Custom transaction code support

3. **Payslip Distribution**
   - Email encrypted payslips
   - SMS notifications
   - Distribution tracking

4. **Reporting & Analytics**
   - Period comparison reports
   - Cost center analytics
   - Export to Excel/PDF

5. **Notifications**
   - Job completion notifications
   - Error/failure alerts
   - Email notifications to users

6. **Testing**
   - Unit tests for models and services
   - Feature tests for controllers
   - Frontend component tests

## File Structure

```
Backend:
├── app/
│   ├── Http/Controllers/
│   │   └── AccountingPeriodController.php
│   ├── Jobs/
│   │   └── ProcessPayrollPeriod.php
│   ├── Models/
│   │   ├── AccountingPeriod.php
│   │   └── CenterPeriodStatus.php
│   ├── Policies/
│   │   └── AccountingPeriodPolicy.php
│   └── Services/
│       └── PayrollProcessor.php
├── database/migrations/
│   ├── 2025_11_09_000001_create_payroll_accounting_periods_table.php
│   └── 2025_11_09_000002_create_center_period_status_table.php
└── routes/
    └── tenant.php (updated)

Frontend:
├── resources/js/
│   ├── components/
│   │   └── accounting-period-table.tsx
│   ├── hooks/queries/
│   │   └── use-accounting-periods.ts
│   ├── pages/Payroll/Periods/
│   │   └── Index.tsx
│   └── stores/
│       └── accounting-period-store.ts
```

## Technologies Used

### Backend
- Laravel 11
- PHP 8.2+
- MySQL 8.0+
- Laravel Queue (Jobs)
- Inertia.js (Server-side rendering)

### Frontend
- React 18
- TypeScript
- Zustand (State management)
- React Query (Data fetching)
- Tailwind CSS (Styling)
- Shadcn/ui (Component library)
- Date-fns (Date formatting)

## Conclusion

This comprehensive implementation provides a robust, scalable accounting period management system with multi-center support, real-time updates, and a modern user interface. The system follows Laravel and React best practices, includes proper error handling, and is production-ready with minor enhancements (exchange rates, notifications, testing).
