<?php

return <<<'MARKDOWN'

## Lumen 8 Best Practices

### About Lumen
- Lumen is a micro-framework by Laravel for building fast microservices and APIs
- Optimized for speed - ~2x faster than Laravel
- Stateless by default (no sessions, no views)

### Architecture
- Keep Controllers thin
- Use Services for business logic
- Use Repository pattern for data access
- Organize by feature or domain

### Routing
- All routes defined in `routes/web.php`
- No route caching available
- Use route model binding

```php
$router->get('/users/{id}', 'UserController@show');
$router->post('/users', 'UserController@store');
```

### Database & Eloquent
- Enable Eloquent in `bootstrap/app.php`

```php
$app->withEloquent();
```

- Use query scopes for reusable queries
- Eager load relationships to avoid N+1 queries
- Define fillable or guarded on models

### Validation
- Manual validation in controllers

```php
$this->validate($request, [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);
```

### Configuration
- Enable facades: `$app->withFacades();`
- Enable Eloquent: `$app->withEloquent();`
- Register service providers manually

```php
$app->register(App\Providers\AppServiceProvider::class);
```

### Caching
- Redis recommended for better performance
- Manual cache operations with `Cache` facade

### Security
- Use Laravel Sanctum for API authentication
- Validate and sanitize all user input
- Use `bcrypt()` or `Hash::make()` for passwords

### Performance
- Lumen is already optimized for speed
- Use Redis for caching and sessions
- Minimize middleware overhead
- Use queue for heavy operations

### Testing
- Write feature tests for critical paths
- Use database transactions in tests

### Artisan Commands
- Limited artisan commands available
- `php artisan route:list` - List all routes
- `php artisan make:migration` - Create migration

### Key Differences from Laravel
- No views (API only)
- No sessions by default
- No route caching
- No configuration caching
- Facades disabled by default
- Eloquent disabled by default
- Limited artisan commands
- Faster bootstrap time

### When to Use Lumen
- Building microservices
- Building APIs
- Need maximum performance
- Don't need full Laravel features

### When NOT to Use Lumen
- Building full web applications
- Need views and sessions
- Need extensive artisan commands
- Better to use Laravel with API resources

MARKDOWN;
