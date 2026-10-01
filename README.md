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

## Postman

Import `postman/Eruvaaka.postman_collection.json`. Run `Login - Request OTP`, then `Verify OTP - saves token`; the verification script saves the JWT in the collection. The protected comment and download requests inherit that token automatically.

Before deployment, change the collection variable `base_url` to the deployed HTTPS API URL. On the server, set `APP_ENV=production`, `APP_DEBUG=false`, a strong unique `APP_KEY`, a strong unique `JWT_SECRET`, and the production `DB_*` and Razorpay values.
