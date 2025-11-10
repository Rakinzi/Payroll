# Quick Start Guide - Run in 5 Minutes

Follow these steps to get the Lorimak Payroll system running quickly.

## 🚀 Quick Setup (Current Environment)

Since your `.env` is already configured with SQLite, here's the fastest way to run:

### 1. Install Dependencies (if not done)

```bash
composer install
npm install
```

### 2. Create Central Database Tables

```bash
# Run migrations on central database
php artisan migrate --database=central --force
```

### 3. Create a Test Tenant

```bash
# Create tenant with ID "local" and domain "local.localhost"
php artisan tenant:create local local.localhost \
    --name="Lorimak Demo" \
    --migrate \
    --seed
```

### 4. Configure Your Hosts File

Add this line to `/etc/hosts` (Linux/Mac) or `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 local.localhost
```

### 5. Start Development Servers

**Terminal 1 - Laravel:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 - Vite (Frontend):**
```bash
npm run dev
```

### 6. Access the Application

Open your browser:
```
http://local.localhost:8000
```

**Login Credentials:**
- Email: `admin@example.com`
- Password: `password`

---

## 🐳 Alternative: Using Docker

If you have Docker installed:

```bash
# Start services
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate --database=central
./vendor/bin/sail artisan tenant:create local local.localhost --migrate --seed

# Access at: http://local.localhost
```

---

## ⚡ One-Command Setup

Run everything at once:

```bash
composer install && \
npm install && \
php artisan migrate --database=central --force && \
php artisan tenant:create local local.localhost --name="Demo" --migrate --seed && \
echo "✅ Setup complete! Add '127.0.0.1 local.localhost' to /etc/hosts"
```

Then just start the servers:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

---

## 🔧 Troubleshooting

### "SQLSTATE[HY000]: General error: 1 no such table"
```bash
# Run migrations again
php artisan migrate --database=central --force
```

### "No tenant found"
```bash
# Check tenants exist
php artisan tenant:list

# Recreate if needed
php artisan tenant:create local local.localhost --migrate --seed
```

### "Vite manifest not found"
```bash
# Make sure Vite is running
npm run dev
```

### "Connection refused"
```bash
# Check database is running (for MySQL/PostgreSQL)
# Or use SQLite (already configured in your .env)
```

---

## 📱 Multiple Tenants

Create additional tenants:

```bash
php artisan tenant:create company1 company1.localhost --name="Company 1" --migrate
php artisan tenant:create company2 company2.localhost --name="Company 2" --migrate

# Add to /etc/hosts:
# 127.0.0.1 company1.localhost
# 127.0.0.1 company2.localhost
```

Access at:
- `http://company1.localhost:8000`
- `http://company2.localhost:8000`

---

**For full setup details, see [SETUP.md](./SETUP.md)**
