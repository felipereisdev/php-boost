<?php

return <<<'MARKDOWN'

### Laravel Socialite

**Purpose:** OAuth authentication with social providers

**Installation:**
```bash
composer require laravel/socialite
```

**Configuration:**

Add credentials to `config/services.php`:
```php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('GITHUB_REDIRECT_URL'),
],
```

**Usage:**

Redirect to provider:
```php
use Laravel\Socialite\Facades\Socialite;

return Socialite::driver('github')->redirect();
```

Handle callback:
```php
$user = Socialite::driver('github')->user();

User::updateOrCreate([
    'email' => $user->getEmail(),
], [
    'name' => $user->getName(),
    'github_id' => $user->getId(),
]);
```

**Providers Supported:**
- Facebook, Twitter, LinkedIn
- Google, GitHub, GitLab
- Bitbucket, Slack

**Best Practices:**
- Store provider tokens securely
- Handle failed authentications
- Verify email if not provided
- Link accounts properly
- Use stateless for API
- Implement account unlinking
- Handle provider errors gracefully

MARKDOWN;
