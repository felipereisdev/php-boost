<?php

return <<<'MARKDOWN'

## Laravel 9 Best Practices

### New Features
- Anonymous migration classes
- Improved route:list command
- Controller route groups
- Enum casting for Eloquent
- Forced scoped bindings
- Full-text indexes

### Architecture
- Use Service/Repository pattern for business logic
- Keep Controllers thin - delegate to Services
- Use Form Requests for validation
- Organize by feature or domain

### Routing
- Use controller route groups for cleaner definitions

```php
Route::controller(OrderController::class)->group(function () {
    Route::get('/orders/{id}', 'show');
    Route::post('/orders', 'store');
});
```

### Eloquent
- Use enum casting for database columns

```php
use App\Enums\ServerStatus;

protected $casts = [
    'status' => ServerStatus::class,
];
```

### Database
- Anonymous migration classes (no timestamp prefixes needed)

```php
return new class extends Migration
{
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
```

- Full-text indexes for better search

```php
$table->text('bio')->fullText();
```

### String Helpers
- Use `str()` helper for fluent string manipulation

```php
str('hello world')->title()->toString();
```

### Testing
- Use Pest for more expressive tests
- Leverage parallel testing for faster execution

### All Laravel 8 Best Practices Apply
- Refer to Laravel 8 guidelines for core practices

MARKDOWN;
