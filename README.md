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

Authenticated endpoints accept `Authorization: Bearer <token>`. Payment orders use Razorpay Test Mode credentials from `.env` and accept INR amounts from `1.00` through `500000.00`.

## Postman

Import `postman/Eruvaaka.postman_collection.json`. Run `Login - saves token` once; its test script stores the JWT in the collection automatically. The protected requests inherit that token, so no manual copying is required.

Before deployment, change the collection variable `base_url` to the deployed HTTPS API URL. On the server, set `APP_ENV=production`, `APP_DEBUG=false`, a strong unique `APP_KEY`, a strong unique `JWT_SECRET`, and the production `DB_*` and Razorpay values.
