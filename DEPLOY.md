# Unwinded CMS — Deployment Guide

## Server requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.2+ |
| PHP extensions | `pdo_mysql`, `gd`, `mbstring`, `fileinfo`, `openssl`, `intl` |
| MySQL / MariaDB | 8.0+ / 10.6+ |
| Web server | Apache 2.4+ with `mod_rewrite`, `mod_headers`, `mod_expires` |
| Disk space | 2 GB recommended (media storage grows with uploads) |

---

## 1. Clone the repository

```bash
cd /var/www
git clone https://github.com/nyashameja/unwinded.git
cd unwinded
```

The document root must point to `public/`. **Never** expose the project root.

---

## 2. Configure the environment

```bash
cp .env.example .env
chmod 600 .env
```

Open `.env` and fill in every value. Key variables:

| Variable | Notes |
|---|---|
| `APP_KEY` | Leave blank — generated in step 4 |
| `APP_URL` | Full URL with no trailing slash, e.g. `https://unwinded.co.za` |
| `APP_DEBUG` | Must be `false` in production |
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | MySQL credentials |
| `MAIL_PASSWORD` | Google Workspace App Password (not your account password) |
| `PAYFAST_SANDBOX` | Set to `false` for live payments |
| `TICKET_HMAC_KEY` | Generated in step 4 |
| `CRON_SECRET` | Any long random string |

> **Important:** The `.env` file must never be committed to version control. Restrict its permissions to the web-server user only.

---

## 3. Create the MySQL database

```sql
CREATE DATABASE unwinded_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'unwinded_user'@'localhost' IDENTIFIED BY 'strong-random-password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER
    ON unwinded_db.* TO 'unwinded_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 4. Generate application keys

```bash
php bin/console key:generate
# Copy the output and set APP_KEY= in .env

php bin/console key:generate
# Copy again and set TICKET_HMAC_KEY= in .env
```

---

## 5. Run migrations and seeders

```bash
php bin/console migrate
php bin/console db:seed
```

The seeders create:
- Default roles and permissions
- A placeholder admin user (change the password immediately — see step 7)
- Default settings, homepage sections, and email templates

---

## 6. Create the private storage directory

Media originals are stored outside the web root for security.

```bash
mkdir -p storage/private/media
chmod -R 755 storage
chown -R www-data:www-data storage
```

If you want the private directory elsewhere (e.g. `/srv/unwinded-storage`), set `STORAGE_PRIVATE_PATH` in `.env` to the absolute path.

---

## 7. Set file permissions

```bash
# Web server owns writable directories
chown -R www-data:www-data storage/ public/media/
chmod -R 755 storage/ public/media/

# Application files: readable by web server, writable only by deploy user
chown -R deploy:www-data .
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Protect .env
chown deploy:www-data .env
chmod 640 .env
```

---

## 8. Configure Apache

Create `/etc/apache2/sites-available/unwinded.conf`:

```apache
<VirtualHost *:80>
    ServerName unwinded.co.za
    ServerAlias www.unwinded.co.za
    Redirect permanent / https://unwinded.co.za/
</VirtualHost>

<VirtualHost *:443>
    ServerName unwinded.co.za
    ServerAlias www.unwinded.co.za

    DocumentRoot /var/www/unwinded/public

    <Directory /var/www/unwinded/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Block access to everything above public/
    <Directory /var/www/unwinded>
        Require all denied
    </Directory>
    <Directory /var/www/unwinded/public>
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/unwinded.co.za/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/unwinded.co.za/privkey.pem

    ErrorLog  /var/log/apache2/unwinded-error.log
    CustomLog /var/log/apache2/unwinded-access.log combined
</VirtualHost>
```

```bash
a2ensite unwinded
a2enmod rewrite headers expires ssl
systemctl reload apache2
```

---

## 9. Set up the cron job

The scheduler handles email queues, reminders, and cleanup tasks.

```bash
crontab -e -u www-data
```

Add:

```cron
* * * * * /usr/bin/php /var/www/unwinded/bin/console schedule:run >> /var/www/unwinded/storage/logs/cron.log 2>&1
```

---

## 10. Create the first admin user

```bash
php bin/console user:create
```

Follow the prompts. Choose **yes** for super admin on the first account.

---

## 11. Final checks

- [ ] Visit `https://unwinded.co.za/admin` — login works
- [ ] Visit `https://unwinded.co.za` — public site loads
- [ ] `APP_DEBUG=false` in `.env`
- [ ] `PAYFAST_SANDBOX=false` in `.env` (when going live with real payments)
- [ ] SSL certificate valid and auto-renewing (`certbot renew --dry-run`)
- [ ] `storage/private/` is not web-accessible
- [ ] Log directory is writable: `storage/logs/`
- [ ] Email test: Admin → Email Templates → any template → "Send test"

---

## Updating

```bash
git pull origin main
php bin/console migrate        # run any new migrations
systemctl reload apache2       # clear opcode cache if using opcache
```

For zero-downtime deploys, put the site into maintenance mode or use a staging slot before running migrations.

---

## Directory structure reference

```
unwinded/
├── app/                  # Controllers, Services, Models, Views
├── bin/console           # CLI tool
├── bootstrap/            # Container and path setup
├── database/
│   ├── migrations/       # Schema migrations (run in order)
│   └── seeds/            # Reference data seeders
├── public/               # ← Document root
│   ├── index.php         # Front controller
│   ├── .htaccess         # Apache rewrite + security headers
│   └── media/            # Public resized image variants (auto-created)
├── routes/               # web.php, admin.php, api.php, webhooks.php
├── storage/
│   ├── logs/             # Application logs
│   └── private/media/    # Original uploads (outside web root)
├── vendor/               # Composer dependencies (committed)
├── .env.example          # Template — copy to .env, never commit .env
└── DEPLOY.md             # This file
```
