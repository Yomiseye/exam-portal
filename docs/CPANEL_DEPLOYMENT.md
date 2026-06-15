# cPanel Deployment Guide

This guide is for deploying the Exam Portal on cPanel/shared hosting.

## Before You Upload

Confirm the hosting account supports:

- PHP 8.3 or newer
- MySQL
- Composer, preferably through SSH or cPanel Terminal
- PHP extensions required by Laravel, including `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, and `zip`

The `zip` extension is important because the admin bulk question upload uses Excel `.xlsx` files.

## Recommended Folder Layout

Keep the Laravel application outside `public_html`:

```text
/home/cpanel-user/exam-portal
/home/cpanel-user/exam-portal/app
/home/cpanel-user/exam-portal/bootstrap
/home/cpanel-user/exam-portal/config
/home/cpanel-user/exam-portal/public
```

Then set the domain or subdomain document root to:

```text
/home/cpanel-user/exam-portal/public
```

This is the safest setup because only Laravel's public entry point is exposed to the web.

## If cPanel Forces `public_html`

If your host does not allow changing the document root:

1. Upload the Laravel project to `/home/cpanel-user/exam-portal`.
2. Copy the contents of `/home/cpanel-user/exam-portal/public` into `/home/cpanel-user/public_html`.
3. Edit `/home/cpanel-user/public_html/index.php`.
4. Change the Laravel paths from:

```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

to:

```php
require __DIR__.'/../exam-portal/vendor/autoload.php';
$app = require_once __DIR__.'/../exam-portal/bootstrap/app.php';
```

Do not copy the whole Laravel project into `public_html`.

## Database Setup

In cPanel:

1. Open **MySQL Databases**.
2. Create a database, for example `cpaneluser_exam_portal`.
3. Create a database user.
4. Assign the user to the database with all privileges.

Use those values in the production `.env` file.

## Production `.env`

Create `/home/cpanel-user/exam-portal/.env` from `.env.example`, then update:

```env
APP_NAME="Exam Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_exam_portal
DB_USERNAME=cpaneluser_exam_user
DB_PASSWORD=your-database-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

ADMIN_NAME="Admin"
ADMIN_EMAIL="admin@your-domain.com"
ADMIN_PASSWORD="use-a-strong-password"

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Use `DB_HOST=localhost` unless the host gives you a different MySQL hostname.

## Build Locally Before Uploading

On your computer, run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Upload the project after the build finishes. The generated frontend files in `public/build` must be included.

If your host provides SSH/Terminal, you can run these commands on the server instead.

## Server Commands

From the Laravel project directory on cPanel:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run the admin seeder only after setting `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

## File Permissions

Make sure these folders are writable by the hosting account:

```text
storage
bootstrap/cache
```

On most cPanel hosts, folder permission `755` and file permission `644` are enough. Some hosts may require `775` for writable folders.

## Cron Job

Add this cPanel cron job to run Laravel scheduled tasks:

```bash
* * * * * cd /home/cpanel-user/exam-portal && php artisan schedule:run >> /dev/null 2>&1
```

Replace `cpanel-user` with the real cPanel username.

## Final Checks

After deployment, verify:

- Admin can log in.
- Admin can create students.
- Admin can create categories and required subcategories.
- Admin can create all question types.
- Admin can upload Excel questions.
- Admin can assign exams with availability dates.
- Student can log in only after admin creation.
- Student answers autosave during an exam.
- Timeout submit records the attempt.
- Results display correctly.

## Common cPanel Issues

- **500 error:** check `.env`, `storage/logs/laravel.log`, PHP version, and file permissions.
- **Blank assets or broken CSS:** confirm `npm run build` was run and `public/build` was uploaded.
- **Database connection error:** confirm database name, username, password, host, and that the user has privileges.
- **Excel upload fails:** confirm the PHP `zip` extension is enabled.
- **Route not found after deploy:** run `php artisan route:clear`, then `php artisan route:cache`.
