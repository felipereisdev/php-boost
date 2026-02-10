<?php

return <<<'MARKDOWN'

### Spatie Laravel Medialibrary

**Purpose:** Associate files with Eloquent models

**Installation:**
```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"
php artisan migrate
```

**Configuration:**

Add trait to models:
```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232);
    }
}
```

**Usage:**

Add media:
```php
$post->addMedia($request->file('image'))->toMediaCollection('images');
```

Retrieve media:
```php
$post->getFirstMediaUrl('images');
$post->getMedia('images');
```

**Best Practices:**
- Define media collections clearly
- Use conversions for image variants
- Set up proper disk configuration
- Implement responsive images
- Clean up media on model deletion
- Use queues for large conversions
- Configure optimizers properly

MARKDOWN;
