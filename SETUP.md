# Lorimak Payroll System - Complete Setup Guide

This guide provides detailed step-by-step instructions for setting up the Lorimak Payroll System from scratch.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Database Setup](#database-setup)
4. [Multi-Tenant Setup](#multi-tenant-setup)
5. [Running the Application](#running-the-application)
6. [Test Users](#test-users)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have the following installed:

### Required Software

- **PHP 8.2 or higher**
  ```bash
  php -v
  ```

- **Composer** (PHP dependency manager)
  ```bash
  composer --version
  ```

- **Node.js 18+ and NPM**
  ```bash
  node -v
  npm -v
  ```

- **MySQL 8.0+** or **MariaDB 10.3+**
  ```bash
  mysql --version
  ```

- **Git**
  ```bash
  git --version
  ```

### PHP Extensions Required

- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- MySQL (pdo_mysql)

Check installed extensions:
```bash
php -m
```

---

## Installation

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd Payroll
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

This will install all Laravel and PHP packages defined in `composer.json`.

### Step 3: Install Node.js Dependencies

```bash
npm install
```

This will install React, TypeScript, Vite, and other frontend dependencies.

### Step 4: Set Up Environment File

```bash
cp .env.example .env
```

Generate application key:
```bash
php artisan key:generate
```

---

## Database Setup

### Step 1: Start MySQL

```bash
# On Linux/Mac
sudo systemctl start mysql

# Or check if already running
sudo systemctl status mysql
```

### Step 2: Create Central Database

The central database stores tenant metadata (tenant list and domain mappings).

```bash
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS lorimak_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Alternative:** Using MySQL client:
```bash
sudo mysql -u root -p
```

Then in MySQL prompt:
```sql
CREATE DATABASE IF NOT EXISTS lorimak_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Step 3: Configure Database Connection

Your `.env` file should already be configured for MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lorimak_central
DB_USERNAME=root
DB_PASSWORD=
```

**Note:** Update `DB_USERNAME` and `DB_PASSWORD` if your MySQL setup differs.

### Step 4: Run Central Database Migrations

```bash
php artisan migrate --database=central
```

This creates the `tenants` and `domains` tables in the central database.

**Expected Output:**
```
Migrating: 2019_09_15_000010_create_tenants_table
Migrated:  2019_09_15_000010_create_tenants_table
Migrating: 2019_09_15_000020_create_domains_table
Migrated:  2019_09_15_000020_create_domains_table
```

---

## Multi-Tenant Setup

### Understanding Multi-Tenancy

This application uses **Spatie Laravel Multitenancy** with the following structure:

```
Central Database (lorimak_central)
├── tenants       # Tenant metadata
└── domains       # Domain-to-tenant mapping

Tenant Database (MySQL - per tenant)
├── users         # Tenant-specific users
├── employees     # Employee records
├── cost_centers  # Companies/Organizations
└── ... (all app tables)
```

### Step 1: Create Your First Tenant

```bash
php artisan tenant:create test localhost --migrate --name="Test Company"
```

**Parameters:**
- `test` - Tenant ID (unique identifier)
- `localhost` - Domain for accessing this tenant
- `--migrate` - Automatically run migrations for tenant database
- `--name="Test Company"` - Display name for tenant

**What this does:**
1. Creates a record in the central `tenants` table
2. Creates a MySQL database named `test` (based on tenant ID)
3. Maps domain `localhost` to tenant `test`
4. Runs all migrations on the new tenant database

**Expected Output:**
```
Creating tenant: test (Test Company)
✓ Tenant created
✓ Domain 'localhost' added
✓ Database 'test' created
Running migrations...
✓ Tenant 'test' created successfully
```

### Step 2: Seed Test Users and Companies

```bash
php artisan tenant:seed test --class=TestUserSeeder
```

**What this creates:**

#### Cost Centers (Companies):
1. **Test Company Ltd** (Code: TESTCO)
2. **Demo Corporation** (Code: DEMO)

#### Users:
1. **Super Admin**
   - Email: admin@lorimak.com
   - Password: password123
   - Access: ALL companies (center_id = null)

2. **Test Company User**
   - Email: user@testcompany.com
   - Password: password123
   - Access: Test Company Ltd only

3. **Demo Corp User**
   - Email: user@democorp.com
   - Password: password123
   - Access: Demo Corporation only

**Expected Output:**
```
Seeding tenant: test (Test Company)
Cost Center 1: Test Company Ltd (TESTCO)
Cost Center 2: Demo Corporation (DEMO)
Super Admin created: admin@lorimak.com (Access: ALL COMPANIES)
User 1 created: user@testcompany.com (Company: Test Company Ltd)
User 2 created: user@democorp.com (Company: Demo Corporation)

=== Login Credentials ===
Super Admin: admin@lorimak.com / password123 (All Companies)
Test Co User: user@testcompany.com / password123 (Test Company Ltd only)
Demo Corp User: user@democorp.com / password123 (Demo Corporation only)
✓ Tenant 'test' seeded successfully
```

---

## Running the Application

### Option 1: Run All Services with One Command (Recommended)

```bash
composer run dev
```

This starts all required services concurrently:
- **Laravel Server** (port 8000)
- **Queue Worker** (background job processing)
- **Pail** (live log viewer)
- **Vite** (frontend hot-reload dev server, port 5173)

**Expected Output:**
```
[server] Starting Laravel development server: http://127.0.0.1:8000
[queue]  Processing jobs from the queue...
[logs]   Streaming logs...
[vite]   VITE v7.1.5  ready in 1234 ms
```

### Option 2: Run Services Separately

Open 4 terminal windows:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:listen
```

**Terminal 3 - Log Viewer:**
```bash
php artisan pail
```

**Terminal 4 - Frontend Dev Server:**
```bash
npm run dev
```

### Accessing the Application

Open your browser and navigate to:
```
http://localhost:8000/login
```

---

## Test Users

### Super Admin (Access All Companies)

**Use this account to access all cost centers/companies:**

- **Email:** `admin@lorimak.com`
- **Password:** `password123`
- **Login:** Leave cost center dropdown blank OR select any company

This user has `center_id = null`, which grants access to all companies in the system.

### Company-Specific Users

#### Test Company Ltd User:
- **Email:** `user@testcompany.com`
- **Password:** `password123`
- **Login:** Select "Test Company Ltd" from dropdown

#### Demo Corporation User:
- **Email:** `user@democorp.com`
- **Password:** `password123`
- **Login:** Select "Demo Corporation" from dropdown

---

## Troubleshooting

### Issue: "No such table: tenants"

**Solution:** Run central database migrations:
```bash
php artisan migrate --database=central
```

### Issue: "Tenant not found"

**Solution:** Create a tenant:
```bash
php artisan tenant:create test localhost --migrate --name="Test Company"
```

### Issue: "The provided credentials are incorrect"

**Solution:** Re-run the seeder:
```bash
php artisan tenant:seed test --class=TestUserSeeder
```

### Issue: Need to reset database with fresh data

**Solution:** Reseed the tenant database (removes all data and reseeds):
```bash
php artisan tenant:migrate test --fresh && php artisan tenant:seed test --class=TestUserSeeder
```

**To reset everything (central + tenant):**
```bash
php artisan migrate:fresh --database=central && \
php artisan tenant:create test localhost --migrate --name="Test Company" && \
php artisan tenant:seed test --class=TestUserSeeder
```

### Issue: Cannot access http://localhost:8000

**Solution:** Ensure Laravel server is running:
```bash
php artisan serve
```

### Issue: Frontend not loading/no styles

**Solution:** Ensure Vite is running:
```bash
npm run dev
```

Or build assets for production:
```bash
npm run build
```

### Issue: MySQL connection refused

**Solution:** Start MySQL:
```bash
sudo systemctl start mysql
```

Check MySQL is listening:
```bash
sudo systemctl status mysql
```

### Issue: Port 8000 already in use

**Solution:** Use a different port:
```bash
php artisan serve --port=8001
```

Then access at `http://localhost:8001`

### Clear All Caches

If you encounter strange behavior:
```bash
php artisan optimize:clear
```

This clears:
- Configuration cache
- Route cache
- View cache
- Event cache

---

## Additional Commands

### List All Tenants

```bash
php artisan tenant:list
```

### Delete a Tenant

```bash
php artisan tenant:delete test
```

**Warning:** This will delete the tenant database and all data!

### Create Additional Tenants

```bash
php artisan tenant:create demo demo.localhost --migrate --name="Demo Company"
php artisan tenant:seed demo --class=TestUserSeeder
```

Then add to `/etc/hosts`:
```bash
echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts
```

Access at: `http://demo.localhost:8000`

---

## Next Steps

1. ✅ **Login** to the dashboard at http://localhost:8000/login
2. ✅ **Explore** the employee management features
3. ✅ **Set up** your cost centers (companies)
4. ✅ **Add** employees to the system
5. ✅ **Configure** payroll settings
6. ✅ **Process** your first payroll

For more information, see:
- [README.md](./README.md) - Main documentation
- [TENANCY_SETUP.md](./TENANCY_SETUP.md) - Multi-tenancy deep dive
- [ACCOUNTING_PERIOD_IMPLEMENTATION.md](./ACCOUNTING_PERIOD_IMPLEMENTATION.md) - Accounting periods

---

**Need Help?**
- Check `storage/logs/laravel.log` for errors
- Run `php artisan pail` for live log streaming
- Review the documentation files in this repository

**Version:** 2.0.0
**Last Updated:** 2025-11-10
