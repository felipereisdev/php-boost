<?php

return <<<'MARKDOWN'

### Laravel Horizon

**Purpose:** Dashboard and configuration for Redis queues

**Installation:**
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

**Configuration:**

Publish config to `config/horizon.php`:
```bash
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
```

**Usage:**

Start Horizon:
```bash
php artisan horizon
```

Dispatch jobs:
```php
dispatch(new ProcessPodcast($podcast));
```

**Dashboard:**
Access at `/horizon` (protected by gate in `HorizonServiceProvider`)

**Best Practices:**
- Configure supervisord for production
- Set proper timeout values per queue
- Use tagging for better monitoring
- Configure memory limits
- Set up notifications for failures
- Use environments config for staging/production
- Monitor metrics and failed jobs regularly

**Deployment:**
```bash
php artisan horizon:terminate  # Graceful shutdown before deploy
php artisan horizon:purge      # Clear completed jobs
```

MARKDOWN;
