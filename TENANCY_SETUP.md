# Multi-Tenancy Setup Guide

This document explains the multi-tenancy implementation using **Spatie Laravel Multitenancy** package.

## Architecture

The application uses **domain-based multi-tenancy** where each client has:
- Their own subdomain (e.g., `nhaka.lorimakpayport.com`, `clary.lorimakpayport.com`)
- Their own dedicated MySQL database
- Custom branding (logo, system name)

## Database Structure

### Central Database (`lorimak_central`)
Stores tenant configuration:
- `tenants` table - Tenant records with custom database names
- `domains` table - Domain-to-tenant mappings

### Tenant Databases
Each tenant has their own database:
- `lorimakp_nhaka` - Nhaka tenant database
- `lorimakp_clary` - Clary Sage Travel database
- etc.

## Setup Instructions

### 1. Create Central Database

```bash
# Create the central database
mysql -u root -p -e "CREATE DATABASE lorimak_central"
```

### 2. Update Environment Variables

Add to your `.env` file:

```env
# Central database configuration
DB_CONNECTION=central
CENTRAL_DB_DATABASE=lorimak_central
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Run Central Migrations

```bash
# Migrate the central database (creates tenants and domains tables)
php artisan migrate --database=central
```

### 4. Seed Tenant Records

```bash
# Populate the central database with existing tenants
php artisan db:seed --class=TenantSeeder
```

### 5. Run Tenant Migrations

```bash
# Migrate all tenant databases
php artisan tenant:migrate
# Or for specific tenant
php artisan tenant:migrate local
```

### 6. Seed Tenant Data

```bash
# Seed specific tenant database
php artisan tenant:seed local

# Or seed with specific seeder
php artisan tenant:seed local --class=PermissionSeeder
```

## Adding New Tenants

To add a new tenant, use the convenient Artisan command:

### Quick Method (Recommended)

```bash
# Create tenant with database, domain, migrations, and seeds in one command
php artisan tenant:create newclient newclient.lorimakpayport.com \
    --name="New Client Name" \
    --migrate \
    --seed
```

This command automatically:
- ✅ Creates the tenant record in central database
- ✅ Creates the MySQL database (`newclient`)
- ✅ Registers the domain (`newclient.lorimakpayport.com`)
- ✅ Runs all tenant migrations
- ✅ Seeds the tenant database with permissions and default data

### Manual Method

If you need more control:

1. **Create tenant record**:
```php
use App\Models\Tenant;
use App\Models\Domain;

$tenant = Tenant::create([
    'id' => 'newclient',
    'database' => 'newclient',
    'data' => [
        'system_name' => 'New Client Name',
        'logo' => 'path/to/logo.png',
    ],
]);

Domain::create([
    'tenant_id' => $tenant->id,
    'domain' => 'newclient.lorimakpayport.com',
]);
```

2. **Create the database**:
```bash
mysql -u root -p -e "CREATE DATABASE newclient CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

3. **Run migrations**:
```bash
php artisan tenant:migrate newclient
```

4. **Seed database**:
```bash
php artisan tenant:seed newclient
```

## How It Works

### Domain Resolution
1. User accesses `nhaka.lorimakpayport.com`
2. `DomainTenantFinder` service identifies the tenant from the domain
3. Spatie multitenancy automatically switches database connection to the tenant's database
4. All queries execute against the tenant database (`nhaka`)

### Tenant Isolation
- Each request is scoped to ONE tenant database
- Users cannot access data from other tenants
- Cost centers provide sub-tenancy within each database

### Frontend Access
React components can access tenant info via Inertia shared props:
```jsx
import { usePage } from '@inertiajs/react';

function MyComponent() {
    const { tenant } = usePage().props;

    return (
        <div>
            <h1>{tenant.system_name}</h1>
            {tenant.logo && <img src={tenant.logo} alt="Logo" />}
        </div>
    );
}
```

The tenant data is automatically shared via `HandleInertiaRequests` middleware.

## Routes

### Central Routes (`routes/web.php`)
- Only accessible from central domains (localhost, 127.0.0.1)
- Used for tenant management, billing, etc.

### Tenant Routes (`routes/tenant.php`)
- All application routes (login, dashboard, employees, etc.)
- Automatically tenant-scoped via middleware
- Each tenant has independent authentication

## Deployment

### Production Checklist
- [ ] Create central database on production server
- [ ] Update production `.env` with correct credentials
- [ ] Run central migrations
- [ ] Seed tenant records
- [ ] Verify all tenant databases exist
- [ ] Run tenant migrations
- [ ] Configure web server to route subdomains correctly

### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name *.lorimakpayport.com lorimakpayport.com;
    root /var/www/payroll/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Troubleshooting

### Issue: "Tenant not found"
- Verify domain exists in `domains` table
- Check tenant record exists in `tenants` table
- Ensure domain matches exactly (no www prefix unless configured)

### Issue: Database connection error
- Verify tenant database exists
- Check `tenancy_db_name` field in tenant record
- Confirm database credentials in `.env`

### Issue: Routes not working
- Ensure you're accessing via tenant domain, not central domain
- Check that route is in `routes/tenant.php`
- Verify middleware is applied correctly

## Benefits

✅ **Complete Data Isolation** - Each tenant's data in separate database
✅ **Custom Branding** - Per-tenant logos and system names
✅ **Scalability** - Easy to move tenants to different servers
✅ **Security** - No risk of cross-tenant data leakage
✅ **Backup & Restore** - Simple per-tenant database backups

## Cost Center vs Tenant

- **Tenant** = A client company with their own domain and database
- **Cost Center** = Sub-divisions within a tenant (e.g., departments, branches)
- Users with `center_id = NULL` are super admins within their tenant
- Regular users are restricted to their cost center
