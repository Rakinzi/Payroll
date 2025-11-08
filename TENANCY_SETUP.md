# Multi-Tenancy Setup Guide

This document explains the multi-tenancy implementation using stancl/tenancy package.

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
php artisan tenants:migrate
```

### 6. Seed Tenant Data

```bash
# Seed permissions in each tenant database
php artisan tenants:run db:seed --class=PermissionSeeder
```

## Adding New Tenants

To add a new tenant:

1. **Create the tenant database** (if it doesn't exist):
```bash
mysql -u root -p -e "CREATE DATABASE lorimakp_newclient"
```

2. **Create tenant record** in tinker or via seeder:
```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'id' => 'newclient',
    'tenancy_db_name' => 'lorimakp_newclient',
]);

$tenant->withSystemName('New Client Name')
    ->withLogo('path/to/logo.png')
    ->save();

$tenant->domains()->create([
    'domain' => 'newclient.lorimakpayport.com',
]);
```

3. **Run migrations** for the new tenant:
```bash
php artisan tenants:migrate --tenants=newclient
```

4. **Seed permissions**:
```bash
php artisan tenants:run db:seed --class=PermissionSeeder --tenants=newclient
```

## How It Works

### Domain Resolution
1. User accesses `nhaka.lorimakpayport.com`
2. `InitializeTenancyByDomain` middleware identifies the tenant
3. Database connection switches to `lorimakp_nhaka`
4. All queries execute against the tenant database

### Tenant Isolation
- Each request is scoped to ONE tenant database
- Users cannot access data from other tenants
- Cost centers provide sub-tenancy within each database

### Frontend Access
React components can access tenant info via:
```jsx
import { usePage } from '@inertiajs/react';

function MyComponent() {
    const { tenant } = usePage().props;

    return (
        <div>
            <h1>{tenant.name}</h1>
            {tenant.logo && <img src={tenant.logo} alt="Logo" />}
        </div>
    );
}
```

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
