# Lorimak Payroll System - Setup Guide

This guide will help you set up and run the Lorimak Payroll application with Spatie multitenancy.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and NPM
- MySQL or PostgreSQL database server
- Git

## Installation Steps

### 1. Clone and Install Dependencies

```bash
# Navigate to project directory
cd /home/user/Payroll

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` file with your database credentials:

```env
APP_NAME="Lorimak Payroll"
APP_ENV=local
APP_KEY=base64:ojXNS/GiCkChzyxnbE+pSjQLqU6HAB8Oufdg803Toqo=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Central Database (for tenants table, domains, etc.)
DB_CONNECTION=central
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lorimak_central
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### 3. Database Configuration

Update `config/database.php` to ensure you have both `central` and `tenant` connections:

**Central Connection** (already configured):
- Stores: tenants, domains, central app data
- Connection name: `central`

**Tenant Connection** (template for tenant databases):
- Each tenant gets their own database
- Connection name: `tenant`
- Created dynamically per tenant

### 4. Create Central Database

```bash
# Create the central database
mysql -u root -p -e "CREATE DATABASE lorimak_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Or for PostgreSQL:
# psql -U postgres -c "CREATE DATABASE lorimak_central WITH ENCODING 'UTF8';"
```

### 5. Run Central Migrations

```bash
# Run migrations on the central database
php artisan migrate --database=central --force
```

This creates:
- `tenants` table (with `id`, `database`, `data`, timestamps)
- `domains` table (with UUID `id`, `tenant_id`, `domain`)
- Other central app tables

**Note:** The migrations will also create all application tables in the central database. Each tenant will get a separate copy of these tables in their own database.

### 6. Create Your First Tenant

```bash
# Create a tenant with database, domain, migrations, and seeds
php artisan tenant:create local local.localhost \
    --name="Lorimak Demo" \
    --migrate \
    --seed
```

This command will:
- ✅ Create tenant record in central DB (`tenants` table)
- ✅ Create `local` MySQL database
- ✅ Add domain `local.localhost` to `domains` table
- ✅ Run all migrations on the tenant database
- ✅ Seed tenant database with:
  - Permissions and roles (via PermissionSeeder)
  - Cost centers (via CostCenterSeeder)
  - Default admin user (`admin@example.com` / `password`)

### 7. Configure Local Domain

Add to your `/etc/hosts` (or `C:\Windows\System32\drivers\etc\hosts` on Windows):

```
127.0.0.1 local.localhost
```

### 8. Build Frontend Assets

```bash
# Development (with hot reload)
npm run dev

# Or build for production
npm run build
```

### 9. Start the Application

**Option A: Using PHP built-in server**

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Option B: Using Laravel Valet/Herd (Mac)**

```bash
valet link lorimak
valet secure lorimak  # Optional: HTTPS
```

**Option C: Using Docker/Sail**

```bash
./vendor/bin/sail up
```

### 10. Access the Application

Open your browser:
- Tenant URL: `http://local.localhost:8000`
- Central URL: `http://localhost:8000` (if you have central routes)

**Default Login Credentials:**
- Email: `admin@example.com`
- Password: `password`

## Available Artisan Commands

### Tenant Management

```bash
# List all tenants
php artisan tenant:list

# Create new tenant (recommended method)
php artisan tenant:create {id} {domain} --name="Tenant Name" --migrate --seed
# Example:
php artisan tenant:create acme acme.localhost --name="Acme Corp" --migrate --seed

# Run migrations for a tenant
php artisan tenant:migrate {tenant_id}
# Example:
php artisan tenant:migrate local

# Seed tenant database
php artisan tenant:seed {tenant_id}
php artisan tenant:seed {tenant_id} --class=PermissionSeeder
# Example:
php artisan tenant:seed local

# Run artisan command in tenant context
php artisan tenant:run {tenant_id} {command}
# Examples:
php artisan tenant:run local cache:clear
php artisan tenant:run local db:seed --class=CustomSeeder

# Delete tenant (removes record, domain, and optionally database)
php artisan tenant:delete {tenant_id} --force
# Example:
php artisan tenant:delete local --force
```

### Development Commands

```bash
# Clear caches
php artisan optimize:clear

# Queue worker (if using queues)
php artisan queue:work

# View logs
php artisan pail

# Run tests
php artisan test
```

## Project Structure

```
├── app/
│   ├── Console/Commands/     # Tenant management commands
│   ├── Http/Controllers/     # Application controllers
│   ├── Models/               # Eloquent models (Tenant, Domain, User, etc.)
│   └── Services/             # Business logic (DomainTenantFinder, etc.)
├── config/
│   ├── database.php          # Database connections
│   └── multitenancy.php      # Spatie multitenancy config
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── resources/
│   ├── js/                   # React/Inertia components
│   └── css/                  # Stylesheets
└── routes/
    ├── web.php               # Central app routes
    └── tenant.php            # Tenant routes (with multitenancy middleware)
```

## Troubleshooting

### Issue: "No tenant found"
**Solution:** Make sure:
1. Tenant exists: `php artisan tenant:list`
2. Domain matches exactly in your browser
3. `/etc/hosts` is configured correctly

### Issue: "Database does not exist"
**Solution:**
```bash
# Check tenant database
php artisan tenant:list

# Recreate tenant with database
php artisan tenant:delete {tenant} --force
php artisan tenant:create {tenant} {domain} --migrate --seed
```

### Issue: Frontend not loading
**Solution:**
```bash
# Make sure Vite is running
npm run dev

# Check if routes are generated
ls -la resources/js/routes/
```

### Issue: Login not working
**Solution:**
1. Check if user exists in tenant database
2. Ensure migrations ran: `php artisan tenant:migrate {tenant}`
3. Seed database: `php artisan tenant:seed {tenant}`

## Multi-Tenancy Architecture

### Overview
The application uses **Spatie Laravel Multitenancy** for complete tenant isolation:

- **Central Database** (`lorimak_central` or your configured name):
  - Stores `tenants` table (tenant metadata)
  - Stores `domains` table (domain-to-tenant mappings)
  - Connection name: `central`

- **Tenant Databases** (one per tenant):
  - Each tenant has their own isolated MySQL database
  - Database name matches tenant ID (e.g., `local`, `acme`, `company1`)
  - Contains all application tables (users, employees, payrolls, etc.)
  - Connection name: `tenant` (dynamically switched)

- **Domain-based identification**:
  - Tenant identified by the domain in the HTTP request
  - Handled by `DomainTenantFinder` service
  - Example: `local.localhost` → tenant with ID `local`

- **Automatic database switching**:
  - Middleware detects domain and switches database connection
  - All queries automatically execute against tenant database
  - Complete data isolation between tenants

### Database Structure Example

```
Central DB (lorimak_central):
├── tenants
│   ├── id: "local"
│   ├── database: "local"
│   └── data: {"system_name": "Lorimak Demo", ...}
└── domains
    ├── id: UUID
    ├── tenant_id: "local"
    └── domain: "local.localhost"

Tenant DB (local):
├── users
├── employees
├── payrolls
├── cost_centers
└── ... (all app tables)
```

## Additional Configuration

### For Production

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Run `php artisan view:cache`
6. Use proper database credentials
7. Set up queue workers
8. Configure caching (Redis recommended)

### Email Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@lorimak.com
MAIL_FROM_NAME="Lorimak Payroll"
```

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Run `php artisan pail` for live logs
- Clear caches: `php artisan optimize:clear`

## Technology Stack

- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** React, Inertia.js, TypeScript
- **UI:** Tailwind CSS, Shadcn UI, Radix UI
- **Database:** MySQL/PostgreSQL
- **Multi-tenancy:** Spatie Laravel Multitenancy
- **Permissions:** Spatie Laravel Permission
- **Build:** Vite 5

---

## Recent Updates

### 2025-11-10: Migration to Spatie Multitenancy
- Migrated from Stancl Tenancy to Spatie Laravel Multitenancy
- Updated all tenant commands (`tenant:create`, `tenant:migrate`, `tenant:seed`)
- Fixed tenant and domain migrations (string IDs, UUID support)
- Updated DatabaseSeeder to use Spatie's `Tenant::current()` API
- Added `tenant` database connection in `config/database.php`

---

**Last Updated:** 2025-11-10
