<?php

return <<<'MARKDOWN'

### Spatie Laravel Activitylog

**Purpose:** Log activity in your Laravel app

**Installation:**
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate
```

**Configuration:**

Enable logging on models:
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Post extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'content'])
            ->logOnlyDirty();
    }
}
```

**Usage:**

Manual logging:
```php
activity()
    ->performedOn($post)
    ->causedBy($user)
    ->log('Post published');
```

Retrieve logs:
```php
$activities = Activity::forSubject($post)->get();
$lastActivity = $post->activities->last();
```

**Best Practices:**
- Log only necessary attributes
- Use logOnlyDirty to log changes only
- Set proper retention policies
- Index activity logs for performance
- Clean old logs periodically
- Use descriptive log names
- Associate activities with users

MARKDOWN;
