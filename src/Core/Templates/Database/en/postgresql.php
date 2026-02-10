<?php

return <<<'MARKDOWN'

## PostgreSQL Best Practices

### Connection
- Use UTF-8 encoding
- Set timezone in connection
- Use connection pooling (PgBouncer)

### Schema Design
- Use appropriate data types
  - `VARCHAR(n)` or `TEXT` for strings
  - `INTEGER` or `BIGINT` for integers
  - `NUMERIC` or `DECIMAL` for precise decimals
  - `TIMESTAMP WITH TIME ZONE` for timestamps
  - `JSONB` for structured data (faster than JSON)
  - `ARRAY` types for arrays
  - `UUID` for unique identifiers

### Indexes
- Add indexes to frequently queried columns
- Use B-tree indexes (default)
- Use GIN indexes for JSONB and full-text search
- Use partial indexes for filtered queries

```sql
CREATE INDEX idx_active_users ON users(email) WHERE active = true;
```

### JSONB
- Prefer `JSONB` over `JSON` for better performance
- Index JSONB columns with GIN

```sql
CREATE INDEX idx_metadata ON products USING GIN (metadata);
```

```php
$products = DB::table('products')
    ->whereJsonContains('metadata->features', 'waterproof')
    ->get();
```

### Queries
- Use prepared statements to prevent SQL injection
- Avoid `SELECT *` - specify only needed columns
- Use `EXPLAIN ANALYZE` to analyze query performance
- Use CTEs (Common Table Expressions) for complex queries

### Transactions
- Use transactions for multi-step operations
- PostgreSQL supports ACID compliance
- Use appropriate isolation levels

```php
DB::transaction(function () {
    // Multiple operations
}, 5); // 5 attempts on deadlock
```

### Full-Text Search
- PostgreSQL has powerful built-in full-text search
- Use `tsvector` and `tsquery` types

```sql
ALTER TABLE posts ADD COLUMN tsv tsvector;
CREATE INDEX idx_tsv ON posts USING GIN(tsv);
```

### Performance
- Use `EXPLAIN ANALYZE` for query optimization
- Vacuum database regularly
- Analyze tables for statistics
- Use table partitioning for large datasets
- Enable query logging for slow queries

### Advanced Features
- Use CTEs for complex queries
- Use window functions for analytics
- Use array operations
- Use UUID for distributed systems

```php
DB::table('users')->orderBy(DB::raw('created_at::date'))->get();
```

### Maintenance
- Run `VACUUM ANALYZE` regularly
- Monitor disk space
- Backup database regularly with `pg_dump`
- Keep PostgreSQL updated
- Monitor connection pool

### PostgreSQL vs MySQL
- Better for complex queries
- Superior JSON support (JSONB)
- Advanced indexing options
- ACID compliant
- Better concurrency handling

MARKDOWN;
