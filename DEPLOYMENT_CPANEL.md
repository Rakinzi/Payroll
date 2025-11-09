# cPanel Multi-Tenant Deployment Guide

This guide explains how to deploy the Lorimak Payroll System on cPanel with multiple tenants using a main domain and subdomains.

## Overview

The Lorimak Payroll System is a multi-tenant Laravel application that uses the `stancl/tenancy` package. Each tenant (client) has their own subdomain and database, while all sharing the same codebase.

**Example Setup:**
- Main Domain (Central App): `lorimakpayport.com` - Shows API information only
- Tenant 1: `nhaka.lorimakpayport.com` - Nhaka Corporation's payroll system
- Tenant 2: `clary.lorimakpayport.com` - Clary Sage Travel's payroll system
- Tenant 3: `acme.lorimakpayport.com` - ACME Company's payroll system

---

## Prerequisites

- cPanel hosting account with:
  - PHP 8.2 or higher
  - MySQL 8.0 or higher
  - Composer access (via SSH or cPanel Terminal)
  - Ability to create subdomains
  - SSH access (recommended)
- Domain name pointed to your cPanel server

---

## Step 1: Prepare Your cPanel Environment

### 1.1 Set Up Main Domain

**Option A: Use Primary Domain (Recommended)**

If your cPanel primary domain is `lorimakpayport.com`:

1. Go to **cPanel → File Manager**
2. Navigate to `public_html/`
3. Delete or backup existing files
4. The document root will be `/home/username/public_html/` (this becomes your main domain)

**Option B: Use Addon Domain**

If you want to add a new domain:

1. Go to **cPanel → Addon Domains**
2. Add domain: `lorimakpayport.com`
3. Set document root to: `/home/username/lorimakpayport.com/public`
4. Click **Add Domain**

**Note:** For this guide, we'll assume you're using the primary domain approach where the application files go in a custom directory and we symlink to public_html.

### 1.2 Enable Required PHP Extensions

In cPanel → **Select PHP Version**:
- ✅ PDO
- ✅ pdo_mysql
- ✅ mbstring
- ✅ tokenizer
- ✅ xml
- ✅ ctype
- ✅ json
- ✅ bcmath
- ✅ fileinfo
- ✅ openssl

### 1.3 Set PHP Version

Set PHP version to **8.2** or higher in **Select PHP Version**.

---

## Step 2: Upload and Install Application

### 2.1 Upload Files

**Option A: Via Git (Recommended)**

```bash
# SSH into your server
ssh username@yourdomain.com

# Navigate to home directory
cd ~

# Clone repository
git clone https://github.com/yourusername/payroll.git
```

**Option B: Via File Manager**

1. Compress project locally (exclude `vendor/` and `node_modules/`)
2. Upload ZIP via cPanel File Manager
3. Extract in `/home/username/payroll/`

### 2.2 Install Dependencies

```bash
cd ~/payroll

# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Install NPM dependencies and build assets
npm install
npm run build
```

### 2.3 Set Permissions

```bash
chmod -R 755 ~/payroll
chmod -R 775 ~/payroll/storage
chmod -R 775 ~/payroll/bootstrap/cache
```

### 2.4 Set Up Document Root

**If using primary domain** (lorimakpayport.com points to public_html):

```bash
# Remove existing public_html contents (backup first!)
cd ~
mv public_html public_html_backup

# Create symlink from public_html to Laravel's public directory
ln -s ~/payroll/public ~/public_html
```

**If using addon domain**, the document root is already set to `/home/username/lorimakpayport.com/public` during domain creation.

---

## Step 3: Database Setup

### 3.1 Create Central Database

In cPanel → **MySQL Databases**:

1. Create database: `username_payroll_central`
2. Create database user: `username_payroll`
3. Set strong password
4. Add user to database with **ALL PRIVILEGES**

### 3.2 Create Tenant Databases

For each tenant, create a separate database:

1. Database: `username_nhaka`
2. Database: `username_clary`
3. Database: `username_client3`
4. Add the same user (`username_payroll`) to all databases with ALL PRIVILEGES

---

## Step 4: Configure Environment

### 4.1 Create .env File

```bash
cd ~/payroll
cp .env.example .env
nano .env
```

### 4.2 Configure .env

```env
APP_NAME="Lorimak Payroll System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://lorimakpayport.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_payroll_central
DB_USERNAME=username_payroll
DB_PASSWORD=your_strong_password

# Central Database (stores tenant information)
CENTRAL_DB_DATABASE=username_payroll_central

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mail.lorimakpayport.com
MAIL_PORT=587
MAIL_USERNAME=noreply@lorimakpayport.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@lorimakpayport.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 4.3 Generate Application Key

```bash
php artisan key:generate
```

---

## Step 5: Run Migrations

### 5.1 Migrate Central Database

```bash
php artisan migrate --force
```

### 5.2 Seed Tenant Information

Edit `database/seeders/TenantSeeder.php` to add your tenants:

```php
$tenants = [
    [
        'id' => 'nhaka',
        'database' => 'username_nhaka',
        'domain' => 'nhaka.lorimakpayport.com',
        'system_name' => 'Nhaka Corporation',
        'logo' => null,
    ],
    [
        'id' => 'clary',
        'database' => 'username_clary',
        'domain' => 'clary.lorimakpayport.com',
        'system_name' => 'Clary Sage Travel',
        'logo' => null,
    ],
    [
        'id' => 'acme',
        'database' => 'username_acme',
        'domain' => 'acme.lorimakpayport.com',
        'system_name' => 'ACME Company',
        'logo' => null,
    ],
    // Add more tenants as needed
];
```

**Important:** Add this method to update tenant data with database connection:

```php
foreach ($tenants as $tenantData) {
    // Create tenant
    $tenant = Tenant::create([
        'id' => $tenantData['id'],
        'tenancy_db_name' => $tenantData['database'],
    ]);

    // Set system name and database connection
    $data = $tenant->data ?? [];
    $data['system_name'] = $tenantData['system_name'];
    $data['tenancy_db_connection'] = 'mysql';

    if ($tenantData['logo']) {
        $data['logo'] = $tenantData['logo'];
    }

    $tenant->data = $data;
    $tenant->save();

    // Create domain
    $tenant->domains()->create([
        'domain' => $tenantData['domain'],
    ]);

    $this->command->info("Created tenant: {$tenantData['system_name']} ({$tenantData['domain']})");
}
```

Run the seeder:

```bash
php artisan db:seed --class=TenantSeeder
```

### 5.3 Migrate Tenant Databases

```bash
php artisan tenants:migrate
```

This will run migrations on all tenant databases.

---

## Step 6: Configure Subdomains in cPanel

For each tenant, create a subdomain that points to the same application directory:

### 6.1 Create Subdomain

1. Go to **cPanel → Subdomains**
2. Subdomain: Enter `nhaka`
3. Domain: Select `lorimakpayport.com` from dropdown
4. Document Root: **Important!** Set to `/home/username/payroll/public` (same as main domain)
5. Click **Create**

### 6.2 Repeat for All Tenants

Create subdomains for each tenant, ensuring ALL point to the same document root:

| Subdomain | Full Domain | Document Root |
|-----------|-------------|---------------|
| `nhaka` | `nhaka.lorimakpayport.com` | `/home/username/payroll/public` |
| `clary` | `clary.lorimakpayport.com` | `/home/username/payroll/public` |
| `acme` | `acme.lorimakpayport.com` | `/home/username/payroll/public` |

**Critical:** All subdomains MUST point to the exact same directory (`/home/username/payroll/public`). The Laravel application will automatically detect which tenant based on the subdomain being accessed.

---

## Step 7: Configure .htaccess

The application includes a `.htaccess` file in the `public/` directory. Ensure it contains:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## Step 8: SSL Certificates

### 8.1 Install SSL for Main Domain

1. Go to **cPanel → SSL/TLS Status**
2. Select `lorimakpayport.com`
3. Click **Run AutoSSL**

### 8.2 Install SSL for Each Subdomain

Repeat for each tenant subdomain:
1. Select subdomain (e.g., `nhaka.lorimakpayport.com`)
2. Click **Run AutoSSL**

You should install SSL for:
- `lorimakpayport.com`
- `nhaka.lorimakpayport.com`
- `clary.lorimakpayport.com`
- `acme.lorimakpayport.com`
- (and any other tenant subdomains)

### 8.3 Force HTTPS

Add to `.htaccess` in `public/` directory (before existing rules):

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Step 9: Optimize for Production

### 9.1 Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9.2 Optimize Autoloader

Already done during `composer install --optimize-autoloader`

### 9.3 Set Up Cron Jobs

In cPanel → **Cron Jobs**, add:

```
* * * * * cd /home/username/payroll && php artisan schedule:run >> /dev/null 2>&1
```

This runs Laravel's task scheduler every minute.

---

## Step 10: Add New Tenants

To add a new tenant after deployment:

### 10.1 Create Tenant Database

1. Go to **cPanel → MySQL Databases**
2. Create database: `username_newclient`
3. Add `username_payroll` user to database

### 10.2 Create Subdomain

1. Go to **cPanel → Subdomains**
2. Subdomain: Enter `newclient`
3. Domain: Select `lorimakpayport.com`
4. Document root: `/home/username/payroll/public` (same as all others)
5. Click **Create**

### 10.3 Add Tenant via Tinker

```bash
cd ~/payroll
php artisan tinker
```

```php
$tenant = App\Models\Tenant::create([
    'id' => 'newclient',
    'tenancy_db_name' => 'username_newclient',
]);

$tenant->domains()->create([
    'domain' => 'newclient.lorimakpayport.com',
]);

// Set system name
$data = $tenant->data ?? [];
$data['system_name'] = 'New Client Name';
$data['tenancy_db_connection'] = 'mysql';
$tenant->data = $data;
$tenant->save();
```

### 10.4 Run Tenant Migrations

```bash
php artisan tenants:migrate --tenants=newclient
```

### 10.5 Install SSL

1. Go to **cPanel → SSL/TLS Status**
2. Run AutoSSL for `newclient.lorimakpayport.com`

---

## Troubleshooting

### Issue: "500 Internal Server Error"

**Solution:**
1. Check file permissions (755 for directories, 644 for files)
2. Ensure `storage/` and `bootstrap/cache/` are writable (775)
3. Check error logs in cPanel or `storage/logs/laravel.log`

### Issue: "Tenant could not be identified"

**Solution:**
1. Verify subdomain points to correct document root (`/home/username/payroll/public`)
2. Check tenant exists in database: `SELECT * FROM tenants;`
3. Check domain exists: `SELECT * FROM domains WHERE domain = 'subdomain.lorimakpayport.com';`
4. Verify `tenancy_db_connection` is set in tenant data: `SELECT id, data FROM tenants;`
5. Clear cache: `php artisan cache:clear`

### Issue: "Database does not exist"

**Solution:**
1. Verify database was created in cPanel
2. Check tenant data: `SELECT * FROM tenants WHERE id = 'tenant_id';`
3. Ensure `tenancy_db_name` matches actual database name
4. Ensure `tenancy_db_connection` is set to `mysql`

### Issue: Assets not loading (CSS/JS)

**Solution:**
1. Run `npm run build` again
2. Clear browser cache
3. Check `APP_URL` in `.env` matches your domain
4. Verify `.htaccess` is working

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] SSL certificates installed for all domains
- [ ] File permissions set correctly (755/644)
- [ ] `.env` file is NOT accessible via web
- [ ] Composer installed with `--no-dev` flag
- [ ] Regular backups configured
- [ ] Firewall rules configured (if applicable)

---

## Backup Strategy

### Automatic Backups (cPanel)

1. Enable **cPanel → Backup** for automatic daily backups
2. Download backups periodically to local storage

### Manual Database Backup

```bash
# Backup central database
mysqldump -u username_payroll -p username_payroll_central > central_backup.sql

# Backup tenant databases
mysqldump -u username_payroll -p username_nhaka > nhaka_backup.sql
mysqldump -u username_payroll -p username_clary > clary_backup.sql
```

### Files Backup

Backup these directories:
- `/home/username/payroll/storage/app/`
- `/home/username/payroll/.env`

---

## Updates and Maintenance

### Updating the Application

```bash
# Pull latest changes
cd ~/payroll
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Update frontend assets
npm install
npm run build

# Run migrations
php artisan migrate --force

# Run tenant migrations
php artisan tenants:migrate

# Clear and recache
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Monitoring

- Check `storage/logs/laravel.log` regularly for errors
- Monitor disk space (tenant databases can grow)
- Review cPanel error logs

---

## Support

For issues or questions:
- Check Laravel documentation: https://laravel.com/docs
- Tenancy for Laravel: https://tenancyforlaravel.com/docs
- Review error logs in `storage/logs/laravel.log`

---

## Architecture Overview

```
                    ┌──────────────────────────────────────────────┐
                    │     lorimakpayport.com (Main Domain)         │
                    │         Shows API Information Only           │
                    │                                              │
                    │   Document Root: /home/username/payroll/     │
                    │                         public/              │
                    └──────────────────────────────────────────────┘
                                        │
                                        │
                    ┌───────────────────┴─────────────────────┐
                    │      Central Database (MySQL)           │
                    │   username_payroll_central              │
                    │   ┌─────────────────────────────┐       │
                    │   │ tenants table               │       │
                    │   │ - id (nhaka, clary, acme)   │       │
                    │   │ - data (JSON)               │       │
                    │   │   - tenancy_db_name         │       │
                    │   │   - tenancy_db_connection   │       │
                    │   │   - system_name             │       │
                    │   └─────────────────────────────┘       │
                    │   ┌─────────────────────────────┐       │
                    │   │ domains table               │       │
                    │   │ - domain                    │       │
                    │   │ - tenant_id                 │       │
                    │   └─────────────────────────────┘       │
                    └─────────────────────────────────────────┘
                                        │
            ┌───────────────────────────┼──────────────────────────┐
            │                           │                          │
┌───────────────────────┐   ┌───────────────────────┐  ┌──────────────────────┐
│ nhaka.lorimakpayport  │   │ clary.lorimakpayport  │  │ acme.lorimakpayport  │
│      .com             │   │      .com             │  │      .com            │
│  (Tenant Subdomain 1) │   │  (Tenant Subdomain 2) │  │  (Tenant Subdomain 3)│
│                       │   │                       │  │                      │
│ Document Root:        │   │ Document Root:        │  │ Document Root:       │
│ /home/username/       │   │ /home/username/       │  │ /home/username/      │
│ payroll/public/       │   │ payroll/public/       │  │ payroll/public/      │
│                       │   │                       │  │                      │
│ Database:             │   │ Database:             │  │ Database:            │
│ username_nhaka        │   │ username_clary        │  │ username_acme        │
│ ├── employees         │   │ ├── employees         │  │ ├── employees        │
│ ├── payrolls          │   │ ├── payrolls          │  │ ├── payrolls         │
│ ├── transactions      │   │ ├── transactions      │  │ ├── transactions     │
│ ├── users             │   │ ├── users             │  │ ├── users            │
│ └── settings          │   │ └── settings          │  │ └── settings         │
└───────────────────────┘   └───────────────────────┘  └──────────────────────┘

KEY POINTS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. SINGLE CODEBASE: All domains/subdomains point to /home/username/payroll/public/
2. DOMAIN IDENTIFICATION: Laravel detects tenant from subdomain (nhaka, clary, acme)
3. DATABASE SWITCHING: Based on subdomain, Laravel connects to corresponding database
4. ISOLATED DATA: Each tenant has completely separate database with their own data
5. SHARED RESOURCES: All tenants share same code, updates affect all at once

EXAMPLE REQUEST FLOW:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

User visits: https://nhaka.lorimakpayport.com
    ↓
Apache serves: /home/username/payroll/public/index.php
    ↓
Laravel detects domain: "nhaka.lorimakpayport.com"
    ↓
Query central DB: SELECT * FROM domains WHERE domain = 'nhaka.lorimakpayport.com'
    ↓
Found tenant: nhaka (database: username_nhaka)
    ↓
Switch database connection to: username_nhaka
    ↓
Load application with Nhaka's data only
```

---

**Last Updated:** November 2025
**Version:** 2.0
