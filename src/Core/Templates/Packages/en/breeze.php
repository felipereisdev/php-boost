<?php

return <<<'MARKDOWN'

### Laravel Breeze

**Purpose:** Minimal authentication scaffolding

**Installation:**
```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run dev
php artisan migrate
```

**Stacks Available:**
- Blade (default)
- React
- Vue
- API only

**Features:**
- Login, Registration
- Password Reset
- Email Verification
- Profile Management

**Structure:**
```
routes/auth.php          # Auth routes
app/Http/Controllers/Auth/  # Auth controllers
resources/views/auth/    # Auth views
```

**Best Practices:**
- Customize after installation
- Keep auth logic simple
- Use middleware for route protection
- Implement rate limiting
- Enable email verification in production
- Customize email templates
- Add 2FA if needed

**API Usage:**
```bash
php artisan breeze:install api
```

MARKDOWN;
