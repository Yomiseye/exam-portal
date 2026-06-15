# Exam Portal

Laravel-based online exam portal for admin-managed students, question banks, timed attempts, autosaved answers, retake permissions, Excel question import, and category/subcategory question organization.

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- MySQL or another Laravel-supported database
- PHP extensions commonly required by Laravel, plus `zip` for Excel imports

## Local Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
```

Set database credentials in `.env` before running migrations.

## First Admin User

The admin seeder no longer creates a default password. Set these values in `.env` before seeding:

```env
ADMIN_NAME="Admin"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="use-a-strong-password"
```

Then run:

```bash
php artisan db:seed --class=AdminUserSeeder
```

If `ADMIN_EMAIL` or `ADMIN_PASSWORD` is missing, the seeder skips admin creation.

## Production Environment

Use production-safe values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="no-reply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Configure real database and mail credentials on the host. Public student registration is disabled; students are created by admins only.

## Deployment Commands

Run these on the server after pulling the latest code:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

For cPanel/shared hosting, use the dedicated checklist in [docs/CPANEL_DEPLOYMENT.md](docs/CPANEL_DEPLOYMENT.md).

If this is the first deployment, seed the first admin after setting `ADMIN_*` values:

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

## Key Production Notes

- Email verification is disabled for now because students are admin-created.
- Student self-registration is disabled.
- Account self-delete is disabled to preserve exam and attempt history.
- Excel imports require `.xlsx` files and use the first worksheet.
- Excel import columns include `category`, `subcategory`, `question_type`, `question`, `difficulty`, `option_1`, `option_2`, `correct_answers`, `match_1`, `match_2`, `explanation`, and `is_active`.

## Verification

Before deploying, run:

```bash
php artisan test
npm run build
```
