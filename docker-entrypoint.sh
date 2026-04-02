#!/bin/bash
set -e

echo "=== RV_CRM Container Starting ==="

# Create required storage directories (volume-mounted, persists across deploys)
mkdir -p /app/storage/framework/{cache,sessions,views}
mkdir -p /app/storage/logs
mkdir -p /app/bootstrap/cache

# Create media upload directories (CRITICAL: these MUST exist for file uploads to work)
mkdir -p /app/storage/app/public/categories
mkdir -p /app/storage/app/public/products/cover
mkdir -p /app/storage/app/public/chatflow-media
mkdir -p /app/storage/app/public/company_logos
mkdir -p /app/storage/app/public/whatsapp_media

# Create storage symlink (public/storage → storage/app/public)
php artisan storage:link --force 2>/dev/null || true

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "WARNING: Migration failed, continuing anyway..."

# Run users seeder automatically
echo "Seeding users table..."
php artisan db:seed --class=UsersTableSeeder --force 2>&1 || echo "WARNING: Seeding failed, continuing anyway..."


# Cache config, routes, and views for performance
echo "Caching configuration..."
php artisan config:cache 2>&1 || true
php artisan route:cache 2>&1 || true
php artisan view:cache 2>&1 || true

echo "=== RV_CRM Ready — Starting Apache ==="

# Fix permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Start Apache (exec replaces shell so Apache is PID 1)
exec apache2-foreground
