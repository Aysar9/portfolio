#!/bin/bash
set -e

echo "🚀 بدء الـ deployment..."

echo "📥 سحب آخر كود من GitHub..."
git pull origin main

echo "📦 تثبيت المكتبات..."
composer install --no-dev --optimize-autoloader

echo "🎨 بناء Tailwind..."
php bin/console tailwind:build --minify

echo "🗜️  تجميع الـ assets..."
php bin/console asset-map:compile

echo "🧹 تنظيف الكاش..."
php bin/console cache:clear --env=prod

echo "🔐 ضبط الصلاحيات..."
sudo chown -R aysar:www-data /var/www/portfolio
sudo chmod -R 775 /var/www/portfolio/var

echo "✅ تم الـ deployment بنجاح!"