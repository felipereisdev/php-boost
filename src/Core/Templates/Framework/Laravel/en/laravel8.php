<?php

return <<<'MARKDOWN'

## Laravel 8 Best Practices

### Architecture
- Use Service/Repository pattern for business logic
- Keep Controllers thin - delegate to Services
- Use Form Requests for validation
- Organize by feature or domain, not technical layer

### Routing
- Group related routes with `Route::group()`
- Use route model binding for cleaner controllers
- Name all routes: `->name('users.index')`
- Use resource controllers when appropriate
- Separate API routes from web routes

### Eloquent
- Use query scopes for reusable queries
- Eager load relationships to avoid N+1 queries
- Use API Resources for response formatting
- Define fillable or guarded on models
- Use accessors and mutators for data transformation

```php
class User extends Model
{
    protected $fillable = ['name', 'email'];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

### Validation
- Always use Form Requests for complex validation
- Keep validation rules DRY

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];
    }
}
```

### Database
- Use migrations for schema changes
- Use seeders for test data
- Use factories for model creation in tests
- Add indexes to frequently queried columns
- Use database transactions for multi-step operations

### Caching
- Cache expensive queries with `Cache::remember()`
- Clear cache with `php artisan cache:clear`
- Use Redis for better performance

### Jobs & Queues
- Use jobs for long-running tasks
- Make jobs idempotent
- Handle job failures with retries and timeouts

### Security
- Use Laravel Sanctum for API authentication
- Enable CSRF protection on forms
- Validate and sanitize all user input
- Use `bcrypt()` or `Hash::make()` for passwords
- Avoid raw SQL queries - use Query Builder or Eloquent

### Performance
- Use `php artisan optimize` for production
- Enable opcache in production
- Use lazy collections for large datasets
- Queue heavy operations
- Minimize middleware overhead

### Testing
- Write feature tests for critical paths
- Use factories for model creation
- Use `RefreshDatabase` trait in tests
- Test validation rules and edge cases

### Artisan Commands
- `php artisan make:*` - Generate boilerplate code
- `php artisan migrate` - Run migrations
- `php artisan db:seed` - Seed database
- `php artisan queue:work` - Process queued jobs
- `php artisan config:cache` - Cache configuration

### Dependencies
- Keep Laravel and packages updated
- Use semantic versioning in composer.json
- Lock dependencies with composer.lock

MARKDOWN;
