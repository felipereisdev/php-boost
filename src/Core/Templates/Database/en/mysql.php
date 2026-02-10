<?php

return <<<'MARKDOWN'

## MySQL Best Practices

### Connection
- Use UTF-8 encoding: `utf8mb4` and `utf8mb4_unicode_ci`
- Set timezone in connection
- Use persistent connections for performance

### Schema Design
- Use appropriate data types
  - `VARCHAR` for variable length strings (max 255 for indexed fields)
  - `TEXT` for long content
  - `INT UNSIGNED` for positive integers
  - `DECIMAL` for monetary values
  - `TIMESTAMP` for timestamps
  - `JSON` for structured data (MySQL 5.7+)

### Indexes
- Add indexes to frequently queried columns
- Use composite indexes for multi-column queries
- Avoid over-indexing (impacts INSERT/UPDATE performance)
- Use `EXPLAIN` to analyze query performance

```sql
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

### Queries
- Use prepared statements to prevent SQL injection
- Avoid `SELECT *` - specify only needed columns
- Use `LIMIT` for large result sets
- Use `JOIN` appropriately (INNER vs LEFT vs RIGHT)
- Avoid N+1 queries with eager loading

### Transactions
- Use transactions for multi-step operations
- Keep transactions short
- Handle deadlocks gracefully

```php
DB::transaction(function () {
    // Multiple operations
});
```

### Performance
- Use query caching when appropriate
- Optimize slow queries with indexes
- Use `EXPLAIN` to understand query execution
- Monitor slow query log
- Use connection pooling

### JSON Columns (MySQL 5.7+)
- Store structured data as JSON
- Query JSON data efficiently

```php
$users = DB::table('users')
    ->whereJsonContains('options->languages', 'en')
    ->get();
```

### Full-Text Search
- Use full-text indexes for search functionality
- Better than `LIKE '%term%'` for large datasets

```sql
ALTER TABLE posts ADD FULLTEXT(title, body);
SELECT * FROM posts WHERE MATCH(title, body) AGAINST('keyword');
```

### Charset & Collation
- Use `utf8mb4` for full Unicode support (including emojis)
- Use `utf8mb4_unicode_ci` for case-insensitive comparisons

### Maintenance
- Regularly optimize tables
- Monitor disk space
- Backup database regularly
- Keep MySQL updated

MARKDOWN;
