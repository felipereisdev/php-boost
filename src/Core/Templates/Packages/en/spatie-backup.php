<?php

return <<<'MARKDOWN'

### Spatie Laravel Backup

**Purpose:** Backup your Laravel app

**Installation:**
```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

**Configuration:**

Configure in `config/backup.php`:
```php
'backup' => [
    'name' => env('APP_NAME', 'laravel-backup'),
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
            ],
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'disks' => ['s3'],
    ],
],
```

**Usage:**

Run backup:
```bash
php artisan backup:run
php artisan backup:run --only-db
php artisan backup:run --only-files
```

List backups:
```bash
php artisan backup:list
```

Clean old backups:
```bash
php artisan backup:clean
```

**Best Practices:**
- Schedule backups in cron
- Store backups offsite (S3, etc)
- Configure retention strategies
- Set up notifications for failures
- Exclude unnecessary files
- Test restore process regularly
- Monitor backup sizes
- Encrypt sensitive backups

**Scheduling:**
```php
$schedule->command('backup:clean')->daily()->at('01:00');
$schedule->command('backup:run')->daily()->at('02:00');
```

MARKDOWN;
