# Deployment Guide

## Requirements

- cPanel shared hosting with PHP 8.2+
- MySQL 8.0+
- Apache with mod_rewrite
- PHP extensions: pdo, pdo_mysql, mbstring, gd, exif, fileinfo, openssl, json

## Directory Layout on cPanel

```
~/                          (home directory)
├── unwinded_app/           ← application root (OUTSIDE public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/            ← writable; set 750
│   ├── vendor/
│   ├── .env                ← secrets; never in public_html
│   └── composer.json
└── public_html/            ← or a subdomain's document root
    ├── index.php           ← front controller
    ├── .htaccess
    ├── assets/
    └── uploads/
```

> **Note:** The `public/` directory in this repository maps to `public_html/` on the server.
> Upload the contents of `public/` into `public_html/`, and everything else into `unwinded_app/`.

## Step-by-Step Deployment

### 1. Upload files

Upload via cPanel File Manager or FTP:

- Upload `public/*` → `public_html/`
- Upload everything else → `unwinded_app/`

`vendor/` is committed to the repository — no Composer needed on the server.

### 2. Set directory permissions

```
chmod 750 ~/unwinded_app
chmod 750 ~/unwinded_app/storage
chmod 750 ~/unwinded_app/storage/logs
chmod 750 ~/unwinded_app/storage/sessions
chmod 750 ~/unwinded_app/storage/cache
chmod 750 ~/unwinded_app/storage/temp
chmod 750 ~/unwinded_app/storage/private
chmod 755 ~/public_html
chmod 755 ~/public_html/uploads
```

### 3. Create the database

In cPanel → MySQL Databases:
1. Create a database
2. Create a user with a strong password
3. Grant ALL PRIVILEGES on the database to that user

### 4. Configure .env

Copy `.env.example` to `~/unwinded_app/.env` and fill in every value.

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.co.za
APP_KEY=base64:...   # generate with: php bin/console key:generate

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=yourdb
DB_USER=youruser
DB_PASS=yourpassword

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.co.za
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=hello@unwinded.co.za
MAIL_FROM_NAME=Unwinded

PAYFAST_MERCHANT_ID=...
PAYFAST_MERCHANT_KEY=...
PAYFAST_PASSPHRASE=...
PAYFAST_SANDBOX=false

STORAGE_PRIVATE_PATH=/home/yourusername/unwinded_app/storage/private
```

### 5. Update public/index.php APP_ROOT

Verify the path in `public_html/index.php`:

```php
define('APP_ROOT', '/home/yourusername/unwinded_app');
```

### 6. Run migrations and seeds

If you have SSH access:

```bash
cd ~/unwinded_app
php bin/console migrate
php bin/console db:seed
```

Without SSH, use cPanel's Terminal or PHP Script Runner (via a temporary protected endpoint — remove after use).

### 7. Set up cron job

In cPanel → Cron Jobs, add:

```
* * * * * /usr/bin/php /home/yourusername/unwinded_app/bin/console schedule:run >> /home/yourusername/unwinded_app/storage/logs/cron.log 2>&1
```

This runs every minute; the scheduler gates tasks to their configured intervals internally.

### 8. Configure PHP (via .htaccess or php.ini)

In `public_html/.htaccess` or a `php.ini` in `public_html/`:

```
php_flag display_errors Off
php_value error_reporting 0
php_value upload_max_filesize 20M
php_value post_max_size 25M
php_value max_execution_time 60
php_value memory_limit 256M
php_value session.save_path /home/yourusername/unwinded_app/storage/sessions
```

### 9. First login

Navigate to `https://yourdomain.co.za/admin` and log in with the credentials set in `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` (or the defaults from `AdminUserSeeder`).

**Change your password immediately.**

## Updating (Zero-Downtime)

1. Upload new files over FTP
2. If migrations exist: run `php bin/console migrate`
3. Clear any opcode cache (via cPanel or `opcache_reset()` in a temporary script)

## Security Checklist

- [ ] `.env` is outside `public_html/`
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is set and unique
- [ ] `PAYFAST_SANDBOX=false` in production
- [ ] `storage/` is not web-accessible
- [ ] `uploads/.htaccess` is in place (disables PHP execution)
- [ ] HTTPS is enforced (cPanel SSL or Cloudflare)
- [ ] Admin password changed after first login
- [ ] Cron job is running
