#!/bin/bash

# Improved Hostinger Setup Script for Laravel
# This script handles the public_html symlink Safely.

PROJECT_ROOT=$(pwd)
PROJECT_NAME=$(basename "$PROJECT_ROOT")
PARENT_DIR=$(dirname "$PROJECT_ROOT")
PUBLIC_HTML="$PARENT_DIR/public_html"

echo "------------------------------------------"
echo "🚀 Carag V2 - Hostinger Setup"
echo "------------------------------------------"
echo "Project Root: $PROJECT_ROOT"
echo "Parent Dir:   $PARENT_DIR"
echo "------------------------------------------"

# Safety Check: Are we already in public_html?
if [ "$PROJECT_NAME" == "public_html" ]; then
    echo "❌ ERROR: You have cloned the project directly INTO public_html."
    echo "This will cause circular links and security issues."
    echo ""
    echo "FIX STEPS:"
    echo "1. Run: cd .."
    echo "2. Run: mv public_html carag-v2"
    echo "3. Run: cd carag-v2"
    echo "4. Run: ./hostinger_setup.sh again"
    exit 1
fi

# 1. Handle public_html symlink
if [ -d "$PUBLIC_HTML" ] && [ ! -L "$PUBLIC_HTML" ]; then
    echo "📦 Backing up existing public_html folder..."
    mv "$PUBLIC_HTML" "${PUBLIC_HTML}_backup_$(date +%s)"
fi

if [ ! -L "$PUBLIC_HTML" ] || [ "$(readlink "$PUBLIC_HTML")" != "$PROJECT_ROOT/public" ]; then
    echo "🔗 Creating symlink: public_html -> $PROJECT_ROOT/public"
    rm -f "$PUBLIC_HTML" # Remove existing link or file if corrupted
    ln -s "$PROJECT_ROOT/public" "$PUBLIC_HTML"
else
    echo "✅ Symlink already exists and is correct."
fi

# 2. Set Permissions
echo "🔐 Setting permissions for storage and bootstrap/cache..."
chmod -R 775 "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"

# 3. Final Checks
echo "🧹 Clearing application cache..."
php artisan optimize:clear

echo ""
echo "🎉 Setup Complete!"
echo "Visit your domain to check the results."
