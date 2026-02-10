<?php

return <<<'MARKDOWN'

## PHP 8.0+ Best Practices

### Type System
- Use union types: `string|int`, `int|float|null`
- Mixed type: `mixed` for any type
- Static return type: `static` for fluent interfaces
- Nullsafe operator: `$obj?->method()`

### Constructor Property Promotion

```php
class Example
{
    public function __construct(
        private string $name,
        private int $age,
        private ?string $email = null
    ) {}
}
```

### Named Arguments

```php
function createUser(string $name, string $email, bool $active = true) {}

createUser(
    name: 'John',
    email: 'john@example.com',
    active: false
);
```

### Match Expressions

```php
$result = match ($status) {
    'active' => 'User is active',
    'inactive' => 'User is inactive',
    'banned' => 'User is banned',
    default => 'Unknown status'
};
```

### String Functions
- Native: `str_starts_with()`, `str_ends_with()`, `str_contains()`
- No need for polyfills

### Attributes

```php
#[Route('/api/users', methods: ['GET'])]
class UserController
{
    #[Authenticated]
    public function index(): array
    {
        return [];
    }
}
```

### Nullsafe Operator

```php
$country = $user?->getAddress()?->getCountry();
```

### Features Available in PHP 8.0+
- Constructor property promotion
- Named arguments
- Match expressions
- Union types
- Mixed type
- Static return type
- Attributes
- Nullsafe operator
- Throw expressions
- WeakMap

### PHP 8.1+ Features
- Enums
- Readonly properties
- First-class callable syntax: `$fn = strlen(...)`
- New in initializers
- Pure intersection types

### PHP 8.2+ Features
- Readonly classes
- Disjunctive Normal Form (DNF) types
- True type
- Constants in traits

### Best Practices
- Use constructor property promotion to reduce boilerplate
- Leverage match expressions over switch when appropriate
- Use nullsafe operator to avoid verbose null checks
- Prefer union types over docblock annotations
- Use attributes for metadata instead of docblocks
- Use named arguments for better readability in complex calls
- Leverage readonly properties for immutability

MARKDOWN;
