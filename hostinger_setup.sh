#!/bin/bash

# Hostinger Setup Script for Laravel
# This script should be run from the ROOT of your project directory (e.g. ~/carag-v2)

PROJECT_ROOT=$(pwd)
PARENT_DIR=$(dirname "$PROJECT_ROOT")
PUBLIC_HTML="$PARENT_DIR/public_html"

echo "Current Project Root: $PROJECT_ROOT"
echo "Target public_html: $PUBLIC_HTML"

# 1. Handle public_html symlink
if [ -d "$PUBLIC_HTML" ] && [ ! -L "$PUBLIC_HTML" ]; then
    echo "Found existing public_html directory. Backing it up to public_html_backup..."
    mv "$PUBLIC_HTML" "${PUBLIC_HTML}_backup"
fi

if [ ! -L "$PUBLIC_HTML" ]; then
    echo "Creating symlink for public_html -> $PROJECT_ROOT/public"
    ln -s "$PROJECT_ROOT/public" "$PUBLIC_HTML"
else
    echo "Symlink already exists at $PUBLIC_HTML"
fi

# 2. Set Permissions
echo "Setting permissions for storage and bootstrap/cache..."
chmod -R 775 "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"

# 3. Optimizations
echo "Cleaning up caches..."
php artisan optimize:clear

echo ""
echo "✅ Hostinger Setup Complete!"
echo "Your domain should now point to: $PROJECT_ROOT/public"
