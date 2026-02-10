<?php

return <<<'MARKDOWN'

### Spatie Laravel Permission

**Purpose:** Role and permission management

**Installation:**
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

**Configuration:**

Add trait to User model:
```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

**Usage:**

Create roles and permissions:
```php
$role = Role::create(['name' => 'admin']);
$permission = Permission::create(['name' => 'edit posts']);

$role->givePermissionTo($permission);
$user->assignRole('admin');
```

Check permissions:
```php
$user->hasPermissionTo('edit posts');
$user->hasRole('admin');
$user->can('edit posts');
```

Middleware:
```php
Route::middleware(['role:admin'])->group(function () {
    // Admin routes
});
```

Blade directives:
```blade
@role('admin')
    Admin content
@endrole

@can('edit posts')
    Edit button
@endcan
```

**Best Practices:**
- Use gates for complex authorization logic
- Cache permissions properly
- Assign permissions to roles, not users directly
- Use middleware for route protection
- Clear permission cache after changes
- Create seeder for initial roles/permissions

MARKDOWN;
