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

# 4. Create central database
mysql -u root -p -e "CREATE DATABASE payroll CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Configure .env
# Update DB_DATABASE=payroll and other credentials

# 6. Run migrations
php artisan migrate --database=central --force

# 7. Create your first tenant
php artisan tenant:create local local.localhost --name="Lorimak Demo" --migrate --seed

# 8. Add to /etc/hosts
echo "127.0.0.1 local.localhost" | sudo tee -a /etc/hosts

# 9. Start servers
php artisan serve --host=0.0.0.0  # Terminal 1
npm run dev                        # Terminal 2

# 10. Access at http://local.localhost:8000
# Login: admin@example.com / password
```

For detailed setup instructions, see [SETUP.md](./SETUP.md) or [QUICKSTART.md](./QUICKSTART.md).

## Documentation

- **[SETUP.md](./SETUP.md)** - Complete setup guide with all configuration options
- **[QUICKSTART.md](./QUICKSTART.md)** - Get running in 5 minutes
- **[TENANCY_SETUP.md](./TENANCY_SETUP.md)** - Multi-tenancy architecture and management
- **[ACCOUNTING_PERIOD_IMPLEMENTATION.md](./ACCOUNTING_PERIOD_IMPLEMENTATION.md)** - Accounting periods system
- **[DEPLOYMENT_CPANEL.md](./DEPLOYMENT_CPANEL.md)** - cPanel deployment guide

## Multi-Tenancy Architecture

The application uses **Spatie Laravel Multitenancy** for complete tenant isolation:

```
Central Database (payroll)
├── tenants (tenant metadata)
└── domains (domain-to-tenant mapping)

Tenant Databases (one per tenant)
├── users
├── employees
├── payrolls
├── cost_centers
└── ... (all app tables)
```

Each tenant:
- Has their own isolated database
- Is identified by domain (e.g., `company1.localhost`)
- Has custom branding (system name, logo)
- Has independent users and data

## Key Commands

### Tenant Management

```bash
# Create new tenant
php artisan tenant:create <id> <domain> --name="Company Name" --migrate --seed

# List all tenants
php artisan tenant:list

# Run migrations for tenant
php artisan tenant:migrate <tenant_id>

# Seed tenant database
php artisan tenant:seed <tenant_id>

# Run command in tenant context
php artisan tenant:run <tenant_id> <command>

# Delete tenant
php artisan tenant:delete <tenant_id> --force
```

### Development

```bash
# Run all tests
php artisan test

# Clear all caches
php artisan optimize:clear

# View live logs
php artisan pail

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

# Central Database
DB_CONNECTION=central
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payroll
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
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
