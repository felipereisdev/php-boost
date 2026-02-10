<?php

return <<<'MARKDOWN'
## SQL Server Best Practices

### Connection Configuration

```php
'sqlserver' => [
    'driver' => 'sqlsrv',
    'host' => env('SQLSERVER_HOST'),
    'port' => env('SQLSERVER_PORT', 1433),
    'database' => env('SQLSERVER_DATABASE'),
    'username' => env('SQLSERVER_USERNAME'),
    'password' => env('SQLSERVER_PASSWORD'),
    'charset' => 'utf8',
    'options' => [
        'TrustServerCertificate' => true,
    ],
],
```

### Query Optimization

- **Always use parameterized queries** to prevent SQL injection
- **Avoid SELECT *** - specify columns explicitly
- Use **TOP or OFFSET/FETCH** for pagination
- Leverage **stored procedures** for complex operations
- Use **query hints** only when necessary

### Pagination

```sql
-- SQL Server 2012+
SELECT * FROM users 
ORDER BY id
OFFSET 10 ROWS
FETCH NEXT 10 ROWS ONLY;

-- Older versions
SELECT TOP 10 * FROM (
    SELECT TOP 20 * FROM users ORDER BY id
) sub ORDER BY id DESC;
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

- Table names: `PascalCase` or `snake_case`
- Column names: `PascalCase` or `snake_case`
- Indexes: `IX_TableName_ColumnName`
- Primary keys: `PK_TableName`
- Foreign keys: `FK_TableName_ReferencedTable`

### Date Handling

```php
// Use GETDATE() for current timestamp
SELECT * FROM orders WHERE CreatedAt > '2024-01-01';

// Use DATEADD for date arithmetic
SELECT DATEADD(day, 7, GETDATE()) AS NextWeek;

// Use DATEDIFF for date differences
SELECT DATEDIFF(day, StartDate, EndDate) AS DaysDifference;
```

### Identity Columns (Auto-Increment)

```sql
CREATE TABLE Users (
    Id INT PRIMARY KEY IDENTITY(1,1),
    Name NVARCHAR(100) NOT NULL,
    Email NVARCHAR(255) NOT NULL
);

-- Get last inserted identity
SELECT SCOPE_IDENTITY();
```

### Performance Tips

- Use **execution plans** to analyze query performance
- Create **clustered indexes** on primary keys
- Create **non-clustered indexes** on frequently queried columns
- Use **table partitioning** for large tables
- Monitor with **SQL Server Profiler** or **Extended Events**
- Update statistics regularly with `UPDATE STATISTICS`

### String Operations

```sql
-- Use NVARCHAR for Unicode support
CREATE TABLE Products (
    Name NVARCHAR(100) NOT NULL
);

-- String concatenation
SELECT FirstName + ' ' + LastName AS FullName FROM Users;

-- String functions
SELECT 
    LEN(Name) AS Length,
    UPPER(Name) AS Uppercase,
    LOWER(Name) AS Lowercase,
    SUBSTRING(Name, 1, 3) AS First3Chars
FROM Products;
```

### Common Pitfalls

- Use `NVARCHAR` instead of `VARCHAR` for Unicode data
- Be aware of implicit conversions affecting performance
- `NULL` comparison requires `IS NULL`, not `= NULL`
- String concatenation with NULL returns NULL (use ISNULL or COALESCE)
- Use square brackets `[table]` for reserved keywords

### Full-Text Search

```sql
-- Create full-text index
CREATE FULLTEXT INDEX ON Products(Name, Description)
KEY INDEX PK_Products;

-- Search with CONTAINS
SELECT * FROM Products
WHERE CONTAINS(Name, 'laptop');

-- Search with FREETEXT (less precise, more flexible)
SELECT * FROM Products
WHERE FREETEXT(Description, 'gaming computer');
```

### JSON Support (SQL Server 2016+)

```sql
-- Parse JSON
SELECT * FROM OPENJSON('{"name":"John","age":30}')
WITH (name NVARCHAR(50), age INT);

-- Query JSON column
SELECT JSON_VALUE(Data, '$.email') AS Email
FROM Users
WHERE JSON_VALUE(Data, '$.status') = 'active';

-- Generate JSON
SELECT Id, Name, Email
FROM Users
FOR JSON PATH;
```

MARKDOWN;
