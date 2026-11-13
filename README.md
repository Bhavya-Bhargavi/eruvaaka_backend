# Eruvaaka Backend

This API runs on Laravel 13 with Eloquent, Laravel routing, request validation, the HTTP client, and compatibility middleware for the existing HS256 JWT tokens.

## Setup

1. Copy `.env.example` to `.env` and set the MySQL and Razorpay values.
2. Import `database.sql` into the database configured by `DB_DATABASE`.
3. Generate the application key:

```text
php artisan key:generate
```

Run locally with:

```text
php artisan serve
```

For Apache, set the document root to the `public` directory.

## API

The existing paths and JSON contracts are preserved:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/logout`
- `GET /api/users/profile`
- `GET|POST /api/users/bookmarks`
- `DELETE /api/users/bookmarks/{article_slug}`
- `POST /api/payments/order`
- `POST /api/payments/verify`
- `GET /api/offers/active` (returns currently active popup offers, including coupon code and eligible plans)
- `POST /api/coupon/validate` (authenticated coupon quote; coupon usage is enforced per user)
- `GET /api/books`, `GET /api/books/{bookId}` (read only; no book download route)
- `GET /api/forums`, `GET /api/forums/{forumId}`, `GET /api/forums/{forumId}/comments`
- `POST /api/forums/{forumId}/comments` (authenticated; 10 posts per minute)
- `GET /api/epapers`, `GET /api/emagazines`
- `GET /api/epapers/{publicationId}/download`, `GET /api/emagazines/{publicationId}/download` (authenticated backend file downloads)

Authenticated endpoints accept `Authorization: Bearer <token>`. Payment orders use the Razorpay credentials configured in `.env` and accept INR amounts from `1.00` through `500000.00`.

## Library, Forums, and Publications

Apply the new content migration with `php artisan migrate`. Temporary demo records and private PDF publication files can be loaded with:

```text
php artisan db:seed --class="Database\Seeders\ContentDemoSeeder"
```

The demo seeder copies the uploaded PDFs from `app/images/` into `storage/app/private/images`. Replace the demo book/discussion content and files with approved client content before production. The API never exposes a public storage URL. The UI should call a download endpoint with its bearer token, read the response as a blob, and display or save that blob as needed. Book responses return readable text content, with no download endpoint.

## Admin Panel

The Blade-based admin panel is available at `/admin/login` when the domain's document root and rewrite rules route requests to this Laravel application's `public` directory. The checked-in `.htaccess` rules forward requests through `public`; the live cPanel document root must still be confirmed before treating `https://www.eruvaaka.com/admin/login` as verified.

Apply the additive admin/content and admin email-verification migrations during a planned deployment:

```text
php artisan migrate --force
php artisan admin:create-super-admin "Admin Name" admin@example.com --generate-password
php artisan storage:link
```

If cPanel has no terminal access, back up the production database, select the correct application database in phpMyAdmin, then import [`admin_panel_migration.sql`](./admin_panel_migration.sql). It creates the Admin Panel schema and Laravel database-session table, and records the migrations so Laravel will not attempt to apply them again. It does not create an administrator account. Do not import `database_export.sql` for this change; that file contains unrelated API/payment changes and seed updates.

The Super Admin command can prompt for a password (minimum 12 characters) or generate and display a one-time password with `--generate-password`; save the generated value securely because it cannot be retrieved later. No default account or password is created. Admin accounts are stored separately from Android customers and use the `admin` session guard. Content Manager permissions are enforced by server middleware.

Admins can update their own password from **Change password** in the sidebar. The form requires the current password, confirmation of the new password, and a minimum length of 12 characters.

Admin account changes and book, magazine, and article changes send email alerts to the affected admin, all active Super Admins, and `ADMIN_NOTIFICATION_EMAIL` (default `admin@eruvaaka.com`). Password alerts never include the password. Admin email changes remain pending until the new address follows a signed verification link; the old address receives an alert, and the link expires after 24 hours.

Configure outbound email in the server `.env` before relying on alerts. For example, use the mail host and credentials supplied by cPanel or your mail provider:

```text
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_SCHEME=tls
MAIL_USERNAME=admin@example.com
MAIL_PASSWORD=provider-supplied-secret
MAIL_FROM_ADDRESS=admin@example.com
MAIL_FROM_NAME="Eruvaaka Admin"
ADMIN_NOTIFICATION_EMAIL=admin@eruvaaka.com
```

Use the provider's actual SMTP hostname, port, and encryption settings; do not commit `.env` or share mail credentials. With `MAIL_MAILER=log`, messages are written to the application log and are not delivered.
Set `APP_URL` to the verified deployed HTTPS URL before email changes are requested so signed verification links use the correct host.

Books and magazine PDFs are stored on Laravel's private `local` disk and served only by published-content download endpoints. Cover images use the `public` disk and require the storage link. Configure `ADMIN_PDF_MAX_KB` and `ADMIN_IMAGE_MAX_KB` to match hosting limits, and make sure cPanel's `upload_max_filesize` and `post_max_size` are at least as large as those configured limits.

Paginated, published-only endpoints are available under `/api/v2/`:

- `GET /api/v2/books`, `/api/v2/books/{book}`, `/api/v2/books/{book}/download`
- `GET /api/v2/magazines`, `/api/v2/magazines/{magazine}`, `/api/v2/magazines/{magazine}/download`
- `GET /api/v2/articles`, `/api/v2/articles/{article}`

The original Android API endpoints and response contracts remain in place. Magazine issues with the same month and year are allowed.

## Postman

Import `postman/Eruvaaka.postman_collection.json`. Run `Login - Request OTP`, then `Verify OTP - saves token`; the verification script saves the JWT in the collection. The protected comment and download requests inherit that token automatically.

Before deployment, change the collection variable `base_url` to the deployed HTTPS API URL. On the server, set `APP_ENV=production`, `APP_DEBUG=false`, a strong unique `APP_KEY`, a strong unique `JWT_SECRET`, and the production `DB_*` and Razorpay values.
