# IVORRY — Installation & Setup Guide

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.3 or higher |
| MySQL | 8.0 or higher |
| Composer | 2.x |
| Node.js | 18.x or higher |
| NPM | 9.x or higher |

### Required PHP Extensions
- `pdo_mysql`
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `gd` or `imagick` (for QR code / PDF generation)
- `zip` (for Excel export)

---

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url> ivorry
cd ivorry
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure `.env`

Open `.env` and update the following:

```env
APP_NAME=IVORRY
APP_URL=http://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ivorry
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Queue (required for notifications)
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=database

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=1440

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@domain.com
MAIL_FROM_NAME="IVORRY"
```

### 5. Database Setup

```bash
# Create the database first in MySQL
mysql -u root -p -e "CREATE DATABASE ivorry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed the database
php artisan db:seed
```

### 6. Storage Setup

```bash
php artisan storage:link
```

### 7. Build Assets

```bash
# Production
npm run build

# Development
npm run dev
```

---

## Default Credentials

After seeding, the following accounts are available:

| Role | Email | Password |
|---|---|---|
| Super Admin | admin@example.com | password |
| Account Manager | account@example.com | password |
| Accreditation | accreditation@example.com | password |
| Claims Processor | claims@example.com | password |
| CSR | csr@example.com | password |
| Dentist | dentist@example.com | password |
| Member | member@example.com | password |
| Middle Management | middle@example.com | password |
| Upper Management | upper@example.com | password |

> ⚠️ **Change all passwords immediately in production.**

---

## Queue Worker Setup

The system uses database queues for notifications and background jobs.

### Development

```bash
php artisan queue:work
```

### Production (using Supervisor)

Install Supervisor:

```bash
sudo apt-get install supervisor
```

Create config file `/etc/supervisor/conf.d/ivorry-worker.conf`:

```ini
[program:ivorry-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ivorry/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ivorry/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ivorry-worker:*
```

---

## Scheduler (Cron) Setup

The scheduler handles:
- **`accounts:activate`** — Activates accounts and members when effective date is reached. Also processes approved renewals.
- **`members:deactivate`** — Deactivates members whose inactive date has passed.

Both run daily at **00:05**.

### Add Cron Entry

```bash
crontab -e
```

Add this single line:

```bash
* * * * * cd /var/www/ivorry && php artisan schedule:run >> /dev/null 2>&1
```

### Verify Scheduled Commands

```bash
php artisan schedule:list
```

Expected output:
```
0 5 * * *  php artisan members:deactivate
0 5 * * *  php artisan accounts:activate
```

### Run Manually (if needed)

```bash
php artisan accounts:activate
php artisan members:deactivate
```

---

## Web Server Configuration

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/ivorry/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/ivorry/public

    <Directory /var/www/ivorry/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## File Permissions

```bash
sudo chown -R www-data:www-data /var/www/ivorry
sudo chmod -R 755 /var/www/ivorry
sudo chmod -R 775 /var/www/ivorry/storage
sudo chmod -R 775 /var/www/ivorry/bootstrap/cache
```

---

## Post-Deployment Checklist

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components

# Clear caches if needed
php artisan optimize:clear
```

---

## Development Environment

Run all services at once:

```bash
composer dev
```

This starts:
- Laravel development server
- Queue worker
- Vite dev server
- Log viewer

---

## Roles & Permissions

| Role | Access |
|---|---|
| Super Admin | Full access |
| Upper Management | Full access (except member profile pages) |
| Middle Management | Full access (except member profile pages) |
| Account Manager | Accounts, Members, Import |
| Accreditation | Clinics, Dentists, Welcome Emails |
| Claims Processor | Claims search, Request validation |
| CSR | Member search, Add procedures |
| Dentist | Search member, My procedures |
| Member | My profile, Search clinics |

To re-seed roles and permissions:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## Scheduled Commands Reference

| Command | Schedule | Description |
|---|---|---|
| `accounts:activate` | Daily 00:05 | Activates accounts/members on effective date, processes renewals |
| `members:deactivate` | Daily 00:05 | Deactivates members whose inactive_date has passed |

---

## Troubleshooting

### Notifications not sending
```bash
# Ensure queue worker is running
php artisan queue:work

# Check failed jobs
php artisan queue:failed
php artisan queue:retry all
```

### Scheduler not running
```bash
# Test manually
php artisan schedule:run

# Check cron is set up
crontab -l
```

### Permission issues after deployment
```bash
php artisan permission:cache-reset
php artisan optimize:clear
```

### Storage files not accessible
```bash
php artisan storage:link
```
