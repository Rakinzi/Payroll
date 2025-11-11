# Lorimak Payroll System

A comprehensive, multi-tenant payroll management system built with Laravel 11 and React, designed for managing employee payroll, leave, and HR operations across multiple organizations.

## Features

- **Multi-Tenancy**: Complete tenant isolation with domain-based routing
- **Payroll Management**: Comprehensive payroll processing with tax calculations
- **Employee Management**: Full employee lifecycle management
- **Leave Management**: Leave applications, approvals, and balance tracking
- **Cost Centers**: Department/branch-level access control
- **Permissions & Roles**: Fine-grained permission system using Spatie Laravel Permission
- **Accounting Periods**: Period-based payroll processing and locking
- **Tax Calculations**: Jamaica tax compliance (PAYE, NIS, NHT, Education Tax)
- **Reports**: Payslips, ITF forms, variance analysis, and more
- **Modern UI**: Beautiful, responsive interface built with React and Tailwind CSS

## Technology Stack

### Backend
- **Laravel 11** - PHP framework
- **PHP 8.2+** - Server-side language
- **MySQL/MariaDB** - Database
- **Spatie Laravel Multitenancy** - Multi-tenant architecture
- **Spatie Laravel Permission** - Role-based access control
- **Laravel Fortify** - Authentication backend

### Frontend
- **React 18** - UI library
- **TypeScript** - Type-safe JavaScript
- **Inertia.js** - Server-side routing with SPA experience
- **Tailwind CSS** - Utility-first CSS framework
- **Shadcn UI** - Component library
- **Radix UI** - Accessible component primitives
- **Vite 5** - Fast build tool

## Quick Start

Get up and running in 5 minutes:

```bash
# 1. Clone the repository
git clone <repository-url>
cd Payroll

# 2. Install dependencies
composer install
npm install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Ensure MySQL is running
sudo systemctl start mysql

# 5. Create central database
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS lorimak_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Configure database in .env (already set for MySQL)
# DB_CONNECTION=mysql
# DB_DATABASE=lorimak_central
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Run central database migrations and seed tenants
php artisan migrate:fresh --database=central
php artisan db:seed --database=central --class=TenantSeeder

# 8. Migrate and seed a tenant (e.g., 'local', 'test', 'nhaka', or 'clary')
php artisan tenant:migrate local --fresh
php artisan tenant:seed local

# 9. Start the development environment (runs Laravel server, queue, logs, and Vite)
composer run dev

# 10. Access at http://localhost:8000/login
```

**Login Credentials:**
- **Super Admin:** admin@lorimak.com / password123 (Access to all companies)
- **Test Co User:** user@testcompany.com / password123 (Test Company Ltd only)
- **Demo Corp User:** user@democorp.com / password123 (Demo Corporation only)

For detailed setup instructions, see the **Setup Guide** section below.

## Documentation

- **[SETUP.md](./SETUP.md)** - Complete setup guide with all configuration options
- **[QUICKSTART.md](./QUICKSTART.md)** - Get running in 5 minutes
- **[TENANCY_SETUP.md](./TENANCY_SETUP.md)** - Multi-tenancy architecture and management
- **[ACCOUNTING_PERIOD_IMPLEMENTATION.md](./ACCOUNTING_PERIOD_IMPLEMENTATION.md)** - Accounting periods system
- **[DEPLOYMENT_CPANEL.md](./DEPLOYMENT_CPANEL.md)** - cPanel deployment guide

## Multi-Tenancy Architecture

The application uses **Spatie Laravel Multitenancy** for complete tenant isolation:

```
Central Database (lorimak_central)
├── tenants (tenant metadata)
└── domains (domain-to-tenant mapping)

Tenant Databases (MySQL - one per tenant)
├── users
├── employees
├── payrolls
├── cost_centers (represents companies/organizations)
└── ... (all app tables)
```

Each tenant:
- Has their own isolated MySQL database
- Is identified by domain (e.g., `localhost`, `company.example.com`)
- Has custom branding (system name, logo)
- Has independent users and data

**Important:** Cost centers represent **companies/organizations**, not departments. Each cost center is a separate company entity within a tenant's database.

### User Access Levels:
- **Super Admin** (`center_id = null`): Can access all cost centers/companies
- **Company User** (`center_id = <uuid>`): Restricted to specific cost center/company

## Roles & Permissions

The system uses Spatie Laravel Permission for role-based access control. Roles and permissions are **tenant-specific** and must be seeded correctly:

### Available Roles:
1. **super-admin** - Full access to all features and all cost centers (28 permissions)
2. **admin** - Access to most features within their cost center (20 permissions)
3. **payroll-manager** - Payroll processing focused (8 permissions)
4. **hr-manager** - HR and leave management focused (9 permissions)
5. **employee** - Limited self-service access (3 permissions)

### Key Permissions:
- `access all centers` - Required to access all cost centers (super-admin only)
- `view employees`, `create employees`, `edit employees`, `delete employees`
- `view payroll`, `create payroll`, `process payroll`, `approve payroll`
- `view leaves`, `create leaves`, `approve leaves`
- `view reports`, `export reports`

### Seeding Order:
**IMPORTANT:** The seeding order matters for multi-tenancy:

1. **Central Database**: `TenantSeeder` creates tenant records
2. **Tenant Database**:
   - `PermissionSeeder` creates roles and permissions (must run first)
   - `CostCenterSeeder` creates cost centers/companies
   - `TestUserSeeder` creates users and assigns roles (must run after PermissionSeeder)

The `DatabaseSeeder` handles this order automatically when you run `php artisan tenant:seed <tenant_id>`.

## Key Commands

### Tenant Management

**Available Tenants** (defined in TenantSeeder):
- `test` - Database: `lorimakp_test` (Test Tenant)
- `local` - Database: `lorimakp_lorimak_v1` (Lorimak)
- `nhaka` - Database: `lorimakp_nhaka` (Lorimak)
- `clary` - Database: `lorimakp_clary` (Clary Sage Travel)

```bash
# List all tenants
php artisan tenant:list

# Run migrations for tenant
php artisan tenant:migrate <tenant_id>

# Seed tenant database (runs PermissionSeeder, CostCenterSeeder, TestUserSeeder)
php artisan tenant:seed <tenant_id>

# Seed with specific seeder
php artisan tenant:seed <tenant_id> --class=PermissionSeeder

# Run command in tenant context
php artisan tenant:run <tenant_id> <command> --option=key=value

# Fresh migrate tenant (drop all tables and re-migrate)
php artisan tenant:migrate <tenant_id> --fresh

# Reseed tenant (fresh migrate + seed)
php artisan tenant:migrate <tenant_id> --fresh && php artisan tenant:seed <tenant_id>
```

**Important:** When seeding, roles and permissions must be created first (PermissionSeeder) before creating users (TestUserSeeder). The DatabaseSeeder runs them in the correct order automatically.

### Database Reset/Reseed

```bash
# Reseed tenant only (keeps central database)
php artisan tenant:migrate local --fresh && php artisan tenant:seed local

# Reseed everything (central + all tenants)
php artisan migrate:fresh --database=central && \
php artisan db:seed --database=central --class=TenantSeeder && \
php artisan tenant:migrate local --fresh && \
php artisan tenant:seed local

# Complete fresh setup from MySQL (nuclear option)
sudo mysql -u root -e "DROP DATABASE IF EXISTS lorimak_central;" && \
sudo mysql -u root -e "CREATE DATABASE lorimak_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" && \
php artisan migrate:fresh --database=central && \
php artisan db:seed --database=central --class=TenantSeeder && \
php artisan tenant:migrate local --fresh && \
php artisan tenant:seed local
```

### Development

```bash
# Start development environment (recommended)
# Runs: Laravel server, queue worker, log viewer (pail), and Vite
composer run dev

# Or run components separately:
php artisan serve              # Laravel server
php artisan queue:listen       # Queue worker
php artisan pail              # Live log viewer
npm run dev                   # Vite dev server (hot reload)

# Run all tests
php artisan test

# Clear all caches
php artisan optimize:clear

# Code analysis
./vendor/bin/phpstan analyse

# Code formatting
./vendor/bin/pint
```

## Project Structure

```
├── app/
│   ├── Console/Commands/       # Artisan commands (tenant management)
│   ├── Http/
│   │   ├── Controllers/        # Application controllers
│   │   └── Middleware/         # Custom middleware
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Business logic
│   └── Providers/              # Service providers
├── config/
│   ├── database.php            # Database connections (central + tenant)
│   └── multitenancy.php        # Spatie multitenancy config
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── resources/
│   ├── js/
│   │   ├── Components/         # React components
│   │   ├── Pages/              # Inertia pages
│   │   └── types/              # TypeScript types
│   └── css/                    # Stylesheets
├── routes/
│   ├── web.php                 # Central routes
│   └── tenant.php              # Tenant routes (not implemented yet)
└── tests/                      # PHPUnit tests
```

## Key Features Detail

### Payroll Processing
- Automatic tax calculations (PAYE, NIS, NHT, Education Tax)
- Custom transaction codes (earnings and deductions)
- Payslip generation and distribution
- Period-based processing with locking

### Employee Management
- Comprehensive employee records
- Bank details and payment methods
- Employment history tracking
- Cost center assignments

### Leave Management
- Leave type configuration
- Application and approval workflow
- Balance tracking and accrual
- Leave year management

### Cost Centers
- Multi-level organizational structure
- Department/branch isolation
- Per-center access control
- Cost center-based reporting

### Accounting Periods
- Monthly period management
- Period locking/unlocking
- Period status tracking per cost center
- Historical data preservation

### Reports
- Payslips (PDF)
- ITF forms (Jamaica tax reporting)
- Variance analysis
- Third-party payments
- Custom report scheduling

## Environment Configuration

Key environment variables:

```env
# Application
APP_NAME="Lorimak Payroll"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lorimak_central
DB_USERNAME=root
DB_PASSWORD=

# Central database name
CENTRAL_DB_DATABASE=lorimak_central

# Session & Cache
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

## Deployment

For production deployment:

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Run optimization commands:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```
3. Set up proper database credentials
4. Configure queue workers for background jobs
5. Set up Redis for caching (recommended)
6. Configure proper web server (Nginx/Apache)

See [DEPLOYMENT_CPANEL.md](./DEPLOYMENT_CPANEL.md) for cPanel-specific instructions.

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and NPM
- MySQL 8.0+ or MariaDB 10.3+
- Git

## Development Setup

```bash
# Install dependencies
composer install
npm install

# Set up pre-commit hooks (optional)
# ...add git hooks setup if needed

# Run tests
php artisan test

# Run linter
./vendor/bin/pint --test

# Run static analysis
./vendor/bin/phpstan analyse
```

## License

Proprietary - All rights reserved.

## Support

For issues or questions:
- Check the documentation in the `docs/` directory
- Review Laravel logs: `storage/logs/laravel.log`
- Run `php artisan pail` for live log streaming

---

**Version:** 1.0.0
**Last Updated:** 2025-11-10

Built with ❤️ by Lorimak Software Solutions
