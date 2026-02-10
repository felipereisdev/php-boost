<?php

return <<<'MARKDOWN'

## SQLite Best Practices

### About SQLite
- File-based database (no server required)
- Ideal for development, testing, and small applications
- ACID compliant
- Not recommended for high-concurrency production workloads

### Configuration
- Enable foreign keys (disabled by default)

```php
DB::statement('PRAGMA foreign_keys = ON;');
```

### Schema Design
- Use appropriate data types
  - `INTEGER` for integers
  - `TEXT` for strings
  - `REAL` for decimals
  - `BLOB` for binary data
- SQLite is dynamically typed (flexible but less strict)

### Indexes
- Add indexes to frequently queried columns
- Use `EXPLAIN QUERY PLAN` to analyze queries

```sql
EXPLAIN QUERY PLAN SELECT * FROM users WHERE email = 'test@example.com';
```

### Queries
- Use prepared statements to prevent SQL injection
- Avoid `SELECT *` - specify only needed columns
- Use `LIMIT` for large result sets

### Transactions
- SQLite uses file locking for concurrency
- Keep transactions short to avoid lock contention
- Handle SQLITE_BUSY errors with retries

```php
DB::transaction(function () {
    // Multiple operations
});
```

### Performance
- SQLite is fast for reads, slower for concurrent writes
- Use WAL mode for better concurrency

```php
DB::statement('PRAGMA journal_mode = WAL;');
```

- Use in-memory database for tests

```php
'database' => ':memory:',
```

### Limitations
- No stored procedures
- Limited ALTER TABLE support
- No RIGHT JOIN or FULL OUTER JOIN
- Single writer at a time (file locking)
- Not suitable for high-traffic production apps

### When to Use SQLite
- Development environment
- Testing (fast, in-memory)
- Small applications
- Desktop applications
- Embedded systems
- Prototyping

### When NOT to Use SQLite
- High-concurrency production apps
- Multiple writers simultaneously
- Large datasets requiring complex queries
- Applications requiring advanced features

### Testing
- Use in-memory SQLite for faster tests

```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Migration from SQLite to MySQL/PostgreSQL
- Test migrations thoroughly
- Watch for SQLite-specific syntax
- Re-test constraints and indexes
- Check date/time handling differences

MARKDOWN;
