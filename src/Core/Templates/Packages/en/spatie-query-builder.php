<?php

return <<<'MARKDOWN'

### Spatie Laravel Query Builder

**Purpose:** Build Eloquent queries from API requests

**Installation:**
```bash
composer require spatie/laravel-query-builder
```

**Usage:**

Basic filtering:
```php
use Spatie\QueryBuilder\QueryBuilder;

$users = QueryBuilder::for(User::class)
    ->allowedFilters(['name', 'email'])
    ->allowedSorts(['name', 'created_at'])
    ->allowedIncludes(['posts', 'comments'])
    ->get();
```

API request:
```
GET /users?filter[name]=john&sort=-created_at&include=posts
```

Custom filters:
```php
use Spatie\QueryBuilder\AllowedFilter;

QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('id'),
        AllowedFilter::partial('name'),
        AllowedFilter::scope('active'),
    ])
    ->get();
```

**Best Practices:**
- Always whitelist allowed filters
- Use exact/partial/scope filters appropriately
- Implement proper pagination
- Validate includes to prevent N+1
- Use custom filters for complex logic
- Document available filters in API
- Consider performance implications
- Add rate limiting for complex queries

MARKDOWN;
