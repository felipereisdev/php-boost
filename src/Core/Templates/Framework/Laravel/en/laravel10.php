<?php

return <<<'MARKDOWN'

## Laravel 10 Best Practices

### New Features
- PHP 8.1+ required
- Native type declarations in skeleton code
- All-new Laravel Pennant (feature flags)
- Process interaction improvements
- Test profiling
- Predis version 2.0

### Native Types
- Use native type declarations in all code

```php
class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::all()
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());
        
        return redirect()->route('users.index');
    }
}
```

### Process Interaction

```php
use Illuminate\Support\Facades\Process;

$result = Process::run('ls -la');

$result->successful();
$result->failed();
$result->output();
```

### Feature Flags with Laravel Pennant

```php
use Laravel\Pennant\Feature;

if (Feature::active('new-dashboard')) {
    // Show new dashboard
}

Feature::for($user)->activate('beta-features');
```

### Testing
- Use test profiling to identify slow tests

```php
php artisan test --profile
```

### All Laravel 8 & 9 Best Practices Apply
- Refer to Laravel 8 and 9 guidelines for core practices

MARKDOWN;
