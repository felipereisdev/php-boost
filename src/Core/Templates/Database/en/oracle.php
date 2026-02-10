<?php

return <<<'MARKDOWN'
## Oracle Database Best Practices

### Connection Configuration

```php
'oracle' => [
    'driver' => 'oci',
    'host' => env('ORACLE_HOST'),
    'port' => env('ORACLE_PORT', 1521),
    'database' => env('ORACLE_DATABASE'), // SID or Service Name
    'username' => env('ORACLE_USERNAME'),
    'password' => env('ORACLE_PASSWORD'),
    'charset' => 'AL32UTF8',
],
```

### Query Optimization

- **Always use bind parameters** to prevent SQL injection
- **Avoid SELECT *** - specify columns explicitly
- Use **ROWNUM for pagination** instead of LIMIT
- Leverage **PL/SQL** for complex business logic
- Use **Oracle hints** sparingly and only when necessary

### Pagination

```php
SELECT * FROM (
    SELECT a.*, ROWNUM rnum FROM (
        SELECT * FROM users ORDER BY id
    ) a WHERE ROWNUM <= 20
) WHERE rnum > 10;
```

### Transactions

```php
DB::beginTransaction();
try {
    // Database operations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Naming Conventions

- Table names: `UPPER_CASE` (Oracle converts to uppercase by default)
- Column names: `UPPER_CASE` or quoted `"mixed_case"`
- Indexes: `IDX_TABLE_COLUMN`
- Sequences: `SEQ_TABLE_NAME`

### Date Handling

```php
// Use TO_DATE() for date literals
SELECT * FROM orders WHERE created_at > TO_DATE('2024-01-01', 'YYYY-MM-DD');

// Use SYSDATE for current timestamp
INSERT INTO logs (message, created_at) VALUES (?, SYSDATE);
```

### Sequences and Auto-Increment

```sql
-- Create sequence
CREATE SEQUENCE seq_users_id START WITH 1 INCREMENT BY 1;

-- Use in trigger
CREATE OR REPLACE TRIGGER trg_users_id
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    SELECT seq_users_id.NEXTVAL INTO :NEW.id FROM DUAL;
END;
```

### Performance Tips

- Use **EXPLAIN PLAN** to analyze query performance
- Create appropriate **indexes** on foreign keys and frequently queried columns
- Use **DBMS_STATS** to gather statistics regularly
- Monitor with **AWR reports** for production tuning
- Consider **partitioning** for large tables

### Common Pitfalls

- Oracle treats empty strings as NULL
- String comparison is case-sensitive by default
- VARCHAR2 max length is 4000 bytes (not characters)
- Be careful with implicit type conversions
- Use CLOB for large text fields (>4000 bytes)

MARKDOWN;
