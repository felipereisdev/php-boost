<?php

return <<<'MARKDOWN'

### Laravel Pennant

**Purpose:** Feature flags management

**Installation:**
```bash
composer require laravel/pennant
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
php artisan migrate
```

**Configuration:**

Define features in `AppServiceProvider`:
```php
use Laravel\Pennant\Feature;

public function boot()
{
    Feature::define('new-dashboard', function ($user) {
        return $user->isAdmin();
    });
}
```

**Usage:**

Check feature:
```php
if (Feature::active('new-dashboard')) {
    // Show new dashboard
}
```

In Blade:
```blade
@feature('new-dashboard')
    <div>New Dashboard</div>
@endfeature
```

Class-based features:
```php
class NewDashboard
{
    public function resolve(User $user)
    {
        return $user->isEarlyAdopter();
    }
}
```

**Best Practices:**
- Use descriptive feature names
- Implement gradual rollouts
- Clean up old features
- Log feature usage
- Use percentage rollouts
- Test both states
- Document feature flags
- Remove flags after full rollout

MARKDOWN;
