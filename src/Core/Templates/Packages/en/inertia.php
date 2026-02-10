<?php

return <<<'MARKDOWN'

### Inertia.js

**Purpose:** Build modern SPAs with server-side routing

**Installation:**
```bash
composer require inertiajs/inertia-laravel
npm install @inertiajs/vue3
```

**Setup Middleware:**
```bash
php artisan inertia:middleware
```

Add to `app/Http/Kernel.php`:
```php
'web' => [
    \App\Http\Middleware\HandleInertiaRequests::class,
],
```

**Controller:**
```php
use Inertia\Inertia;

public function index()
{
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
}
```

**Vue Component (resources/js/Pages/Users/Index.vue):**
```vue
<template>
  <div>
    <h1>Users</h1>
    <ul>
      <li v-for="user in users" :key="user.id">
        {{ user.name }}
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  users: Array
})
</script>
```

**Best Practices:**
- Share global data via HandleInertiaRequests
- Use Inertia::share for flash messages
- Leverage page caching for performance
- Use lazy props for heavy data
- Validate on server side, not just client
- Use form helpers for easier form handling

MARKDOWN;
