<?php

return <<<'MARKDOWN'

### Filament PHP

**Purpose:** Modern admin panel for Laravel

**Installation:**
```bash
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-user
```

**Configuration:**

Create resources:
```bash
php artisan make:filament-resource Post
```

**Resource Structure:**
```php
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required(),
            Textarea::make('content'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
            TextColumn::make('created_at')->dateTime(),
        ]);
    }
}
```

**Best Practices:**
- Use Form Builder for consistent UIs
- Implement authorization policies
- Create custom pages when needed
- Use relationship managers for related data
- Configure navigation properly
- Implement global search
- Use notifications for user feedback
- Customize themes when appropriate

MARKDOWN;
