<?php

return <<<'MARKDOWN'

### Laravel Scout

**Purpose:** Full-text search for Eloquent models

**Installation:**
```bash
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

**Drivers:**
- Algolia (default)
- MeiliSearch
- Database (simple)
- Typesense

**Configuration:**

Add trait to searchable models:
```php
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
```

**Usage:**

Search:
```php
Post::search('Laravel')->get();
Post::search('Laravel')->where('status', 'published')->get();
```

Import existing:
```bash
php artisan scout:import "App\Models\Post"
```

**Best Practices:**
- Index only searchable fields
- Use queues for indexing
- Configure chunk size properly
- Handle soft deletes
- Use conditional indexing
- Implement search filters
- Monitor search metrics
- Optimize index settings

MARKDOWN;
