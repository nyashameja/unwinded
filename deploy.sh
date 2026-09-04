#!/usr/bin/env bash
# =============================================================
# Unwinded CMS — Initial server deployment script
#
# Usage (fresh server):
#   chmod +x deploy.sh
#   sudo ./deploy.sh
#
# This script installs system dependencies, sets up the app,
# runs migrations and seeders, and configures Apache.
# Review each section before running in production.
# =============================================================

set -euo pipefail

# ── Configuration — edit before running ───────────────────────
APP_DIR="/var/www/unwinded"
WEB_USER="www-data"
DOMAIN="unwinded.co.za"
PHP_VERSION="8.2"
# ──────────────────────────────────────────────────────────────

GREEN="\033[32m"
YELLOW="\033[33m"
RED="\033[31m"
RESET="\033[0m"

info()  { echo -e "${GREEN}▶ $*${RESET}"; }
warn()  { echo -e "${YELLOW}⚠ $*${RESET}"; }
abort() { echo -e "${RED}✖ $*${RESET}" >&2; exit 1; }

[[ $EUID -eq 0 ]] || abort "Run this script as root (sudo ./deploy.sh)"
[[ -f "$APP_DIR/.env" ]] || abort ".env not found at $APP_DIR/.env — copy .env.example and fill it in first."

cd "$APP_DIR"

# ── 1. Install PHP and extensions ─────────────────────────────
info "Installing PHP $PHP_VERSION and required extensions…"
apt-get update -qq
apt-get install -y -qq \
    "php$PHP_VERSION" \
    "php$PHP_VERSION-cli" \
    "php$PHP_VERSION-fpm" \
    "php$PHP_VERSION-mysql" \
    "php$PHP_VERSION-gd" \
    "php$PHP_VERSION-mbstring" \
    "php$PHP_VERSION-fileinfo" \
    "php$PHP_VERSION-openssl" \
    "php$PHP_VERSION-intl" \
    "php$PHP_VERSION-xml" \
    apache2 \
    libapache2-mod-php"$PHP_VERSION" \
    certbot python3-certbot-apache

# ── 2. Generate APP_KEY if not set ────────────────────────────
info "Checking APP_KEY…"
if grep -qE '^APP_KEY=$' "$APP_DIR/.env"; then
    warn "APP_KEY is blank — generating one now."
    KEY=$(php bin/console key:generate 2>&1 | grep -oP 'base64:\S+')
    sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" "$APP_DIR/.env"
    info "APP_KEY set."
fi

# ── 3. Create storage directories ─────────────────────────────
info "Creating storage directories…"
mkdir -p storage/private/media storage/logs public/media
chown -R "$WEB_USER:$WEB_USER" storage/ public/media/
chmod -R 755 storage/ public/media/

# ── 4. Run migrations ─────────────────────────────────────────
info "Running database migrations…"
php bin/console migrate

# ── 5. Run seeders ────────────────────────────────────────────
info "Seeding reference data…"
php bin/console db:seed

# ── 6. File permissions ───────────────────────────────────────
info "Setting file permissions…"
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chown -R "$WEB_USER:$WEB_USER" storage/ public/media/
chmod 640 .env

# ── 7. Apache VirtualHost ─────────────────────────────────────
info "Writing Apache VirtualHost…"
VHOST="/etc/apache2/sites-available/${DOMAIN}.conf"
cat > "$VHOST" <<APACHECONF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    Redirect permanent / https://${DOMAIN}/
</VirtualHost>

<VirtualHost *:443>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}

    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}>
        Require all denied
    </Directory>

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/${DOMAIN}/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/${DOMAIN}/privkey.pem

    ErrorLog  /var/log/apache2/${DOMAIN}-error.log
    CustomLog /var/log/apache2/${DOMAIN}-access.log combined
</VirtualHost>
APACHECONF

a2ensite "${DOMAIN}"
a2enmod rewrite headers expires ssl
systemctl reload apache2

# ── 8. SSL certificate ────────────────────────────────────────
info "Obtaining SSL certificate (Let's Encrypt)…"
certbot --apache -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos \
    --email "hello@${DOMAIN}" || warn "certbot failed — configure SSL manually."

# ── 9. Cron job ───────────────────────────────────────────────
info "Installing cron job for scheduled tasks…"
CRON_CMD="* * * * * /usr/bin/php ${APP_DIR}/bin/console schedule:run >> ${APP_DIR}/storage/logs/cron.log 2>&1"
(crontab -u "$WEB_USER" -l 2>/dev/null | grep -qF "schedule:run") \
    || (crontab -u "$WEB_USER" -l 2>/dev/null; echo "$CRON_CMD") | crontab -u "$WEB_USER" -

# ── 10. Done ──────────────────────────────────────────────────
echo ""
info "Deployment complete."
echo ""
echo "  Next steps:"
echo "  1. Run:  php bin/console user:create"
echo "     to create your first admin user."
echo ""
echo "  2. Visit: https://${DOMAIN}/admin"
echo ""
echo "  3. In .env, verify:"
echo "     APP_DEBUG=false"
echo "     PAYFAST_SANDBOX=false  (when ready for live payments)"
echo ""
echo "  See DEPLOY.md for full documentation."
echo ""
