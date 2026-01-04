# 🚀 Carag V2 - Deployment Guide

## Server Requirements

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- Node.js 18+
- Nginx / Apache
- Composer 2+
- Git

## Initial Server Setup (Ubuntu/Debian)

```bash
# Install dependencies
sudo apt update
sudo apt install nginx php8.2-fpm php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-zip php8.2-gd mysql-server nodejs npm git supervisor -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## First-Time Deployment

```bash
# Clone repository
cd /var/www
git clone https://github.com/your-repo/carag-v2.git
cd carag-v2

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your database credentials

# Setup database
php artisan migrate --seed

# Create storage link
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data /var/www/carag-v2
sudo chmod -R 755 storage bootstrap/cache
```

## Subsequent Updates

```bash
cd /var/www/carag-v2
./deploy.sh
```

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/carag-v2/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## SSL with Certbot

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Clear All Caches
```bash
php artisan optimize:clear
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```
