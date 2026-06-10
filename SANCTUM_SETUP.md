# Sanctum Setup Guide

This document outlines the steps required to properly set up Laravel Sanctum for API token authentication in this project.

## Prerequisites

- Laravel 12+ with Sanctum ^4.3
- SQLite or another supported database

## Setup Steps

### 1. Publish Sanctum Configuration

If not already done, publish the Sanctum service provider to generate the configuration file:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

This creates `config/sanctum.php` with default configurations.

### 2. Run Database Migrations

Ensure the `personal_access_tokens` migration has been run to create the required table:

```bash
php artisan migrate
```

The migration file `database/migrations/2026_05_13_124548_create_personal_access_tokens_table.php` must be executed before using Sanctum.

### 3. Configure Environment Variables

Add the following to your `.env` file (see `.env.example`):

```env
# Sanctum Configuration
SANCTUM_TOKEN_NAME=auth_token
SANCTUM_TOKEN_EXPIRATION=null
SANCTUM_TOKEN_ROTATION=null
```

Configuration options:
- `SANCTUM_TOKEN_NAME`: Name of generated API tokens (default: `auth_token`)
- `SANCTUM_TOKEN_EXPIRATION`: Minutes until token expires (default: `null` = no expiration)
- `SANCTUM_TOKEN_ROTATION`: Request count before token rotation (default: `null` = disabled)

### 4. Verify Configuration

Check that `config/sanctum.php` contains:

```php
'token_name' => env('SANCTUM_TOKEN_NAME', 'auth_token'),
```

### 5. API Endpoints Available

Once set up, the following authenticated endpoints are available:

- **POST** `/api/v1/register` - Register a new user (rate limited: 5 requests/minute)
- **POST** `/api/v1/login` - Login and receive an API token (rate limited: 5 requests/minute)
- **POST** `/api/v1/logout` - Logout and revoke the current token (requires auth)
- **GET** `/api/v1/profile` - Retrieve authenticated user profile (requires auth)

### 6. Testing the Authentication Flow

#### Register
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "Password@123",
    "password_confirmation": "Password@123"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "Password@123"
  }'
```

#### Use Token for Protected Routes
```bash
curl -X GET http://localhost:8000/api/v1/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Logout
```bash
curl -X POST http://localhost:8000/api/v1/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Troubleshooting

### "Required @OA\Info() not found"
This error occurs if the OpenAPI documentation is not properly configured. Ensure `app/Http/Controllers/ApiDocController.php` or another controller contains the `@OA\Info()` annotation.

### "personal_access_tokens table not found"
Run `php artisan migrate` to create the required table.

### Token Not Working
- Verify the `auth:sanctum` middleware is applied to protected routes
- Check that the token is included in the `Authorization` header as `Bearer <token>`
- Ensure the token has not expired (default: no expiration unless configured)
## Token Lifecycle & Expiration Strategy

### Default Behavior (Long-Lived Tokens)

By default, Sanctum issues **indefinite tokens** with no expiration. These tokens remain valid until:

1. **Explicitly revoked** via logout endpoint
2. **Manually deleted** from the `personal_access_tokens` table
3. **Token rotation** (if configured)

### Configuring Token Expiration

To implement token expiration, set `SANCTUM_TOKEN_EXPIRATION` in `.env`:

```env
# Expire tokens after 480 minutes (8 hours)
SANCTUM_TOKEN_EXPIRATION=480
```

Once configured, tokens will be considered expired and rejected after the specified duration.

### Token Rotation Strategy

To automatically rotate tokens after a set number of requests:

```env
# Rotate token every 50 requests
SANCTUM_TOKEN_ROTATION=50
```

This invalidates and issues a new token periodically, improving security.

### Production Recommendations

For production environments:

- **Short-lived tokens**: Set `SANCTUM_TOKEN_EXPIRATION=60` (1 hour)
- **Token rotation**: Enable `SANCTUM_TOKEN_ROTATION=25` for mobile/web clients
- **HTTPS only**: All API requests must use HTTPS
- **Token refresh**: Implement a refresh token endpoint for long-running clients

## Middleware & Authentication Behavior

### Protected Routes

Routes protected with `middleware('auth:sanctum')` require a valid bearer token:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});
```

**Middleware Behavior:**
- If no token is provided: Returns **401 Unauthorized** before reaching the controller
- If token is invalid or expired: Returns **401 Unauthorized** before reaching the controller
- If token is valid: `$request->user()` is always populated with the authenticated user

### Edge Cases

#### Profile Endpoint (`GET /api/v1/profile`)
The `auth:sanctum` middleware ensures `$request->user()` is always available. No additional null-checks are needed inside the controller.

#### Logout Endpoint (`POST /api/v1/logout`)
The null-coalescing operator `?->currentAccessToken()` safely handles edge cases:
- If middleware fails (user is null), returns 401
- If user has no active token, returns 401
- Otherwise, revokes the token and returns 200

#### Register Endpoint (`POST /api/v1/register`)
Validates email uniqueness via the database constraint `unique:users,email`. If registration fails, a user-friendly error message is returned.
## Rate Limiting

Auth endpoints are protected with rate limiting to prevent brute-force attacks:

- **Register & Login**: 5 requests per minute per IP

To customize, modify `routes/api/v1.php`:

```php
Route::post('/login', [...])
    ->middleware('throttle:5,1'); // 5 attempts per 1 minute
```

## Security Notes

- Tokens are stored in the `personal_access_tokens` table with hashed values
- Never commit `.env` files with real credentials to version control
- Use HTTPS in production for all API requests
- Consider implementing token expiration in `config/sanctum.php` for enhanced security

## Additional Resources

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [OpenAPI/Swagger Specification](https://swagger.io/specification/)
