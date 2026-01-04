#!/bin/bash

###############################################
# Carag V2 - Deployment Script
# Usage: ./deploy.sh
###############################################

set -e  # Exit on error

echo "🚀 Starting deployment..."

cd /var/www/carag-v2

# Put application in maintenance mode
echo "⏸️  Enabling maintenance mode..."
php artisan down --retry=60

# Pull latest changes from git
echo "📥 Pulling latest changes..."
git pull origin main

# Install/update composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install/update npm dependencies and build assets
echo "🔨 Building frontend assets..."
npm ci
npm run build

# Run database migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Clear and optimize caches
echo "🧹 Clearing caches..."
php artisan optimize:clear

echo "⚡ Optimizing application..."
php artisan optimize
php artisan view:cache
php artisan event:cache

# Restart queue workers (if using)
# echo "🔄 Restarting queue workers..."
# php artisan queue:restart

# Set proper permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Bring application back online
echo "✅ Disabling maintenance mode..."
php artisan up

echo ""
echo "═══════════════════════════════════════════"
echo "  ✅ Deployment completed successfully!"
echo "═══════════════════════════════════════════"
echo ""
