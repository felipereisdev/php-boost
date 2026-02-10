<?php

return <<<'MARKDOWN'

### Laravel Telescope

**Purpose:** Debug assistant for development

**Installation:**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Configuration:**

Enable only in specific environments in `TelescopeServiceProvider`:
```php
public function register()
{
    if ($this->app->environment('local')) {
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
    }
}
```

**Usage:**

Access dashboard at `/telescope`

**Watchers Available:**
- Requests, Commands, Schedule
- Jobs, Queues, Batches
- Database queries, Models
- Exceptions, Logs
- Cache, Redis
- Mail, Notifications
- Events, Gates

**Best Practices:**
- NEVER enable in production
- Use only in local/staging
- Configure watchers in `config/telescope.php`
- Set recording limits to avoid DB bloat
- Prune records regularly
- Exclude sensitive paths from recording
- Use specific watchers, disable unused ones

**Pruning:**
```bash
php artisan telescope:prune --hours=48
```

MARKDOWN;
