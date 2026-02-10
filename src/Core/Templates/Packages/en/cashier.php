<?php

return <<<'MARKDOWN'

### Laravel Cashier

**Purpose:** Subscription billing with Stripe/Paddle

**Installation (Stripe):**
```bash
composer require laravel/cashier
php artisan migrate
```

**Configuration:**

Add trait to User model:
```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

Set env variables:
```env
STRIPE_KEY=your-stripe-key
STRIPE_SECRET=your-stripe-secret
```

**Usage:**

Create subscription:
```php
$user->newSubscription('default', 'price_monthly')->create($paymentMethod);
```

Check subscription:
```php
if ($user->subscribed('default')) {
    // User has active subscription
}
```

Cancel subscription:
```php
$user->subscription('default')->cancel();
```

**Webhooks:**
```bash
php artisan cashier:webhook
```

**Best Practices:**
- Handle webhooks properly
- Test with Stripe test mode
- Implement grace periods
- Handle failed payments
- Offer trials when appropriate
- Use metered billing for usage-based
- Monitor subscription metrics
- Handle customer portal integration

MARKDOWN;
