<?php

return <<<'MARKDOWN'

## PHP 7.4 Best Practices

### Type System
- Use type hints for parameters and return types
- Nullable types: `?string`, `?int`, `?array`
- Void return type: `function doSomething(): void`
- Array and callable type hints available
- No union types (PHP 8.0+)
- No mixed type (PHP 8.0+)
- No static return type (PHP 8.0+)

### Property Declaration
- Properties must be declared separately from constructor
- No constructor property promotion (PHP 8.0+)
- Type hints required for properties

```php
class Example
{
    private string $name;
    private int $age;

    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age = $age;
    }
}
```

### Features NOT Available in PHP 7.4
- Constructor property promotion
- Named arguments
- Match expressions (use switch)
- Union types
- Mixed type
- Static return type
- Attributes (use docblocks)
- Enums (use class constants)
- Readonly properties
- Nullsafe operator (use ternary or null checks)

### String Functions
- Use polyfills for: `str_starts_with()`, `str_ends_with()`, `str_contains()`
- These functions are native in PHP 8.0+

### Error Handling
- Use try-catch for exceptions
- Type errors throw TypeError
- Enable strict_types with `declare(strict_types=1);`

### Array Functions
- Use `array_key_exists()`, `in_array()`, `array_filter()`, `array_map()`
- Array spread operator available: `[...$array1, ...$array2]`
- Arrow functions available: `fn($x) => $x * 2`

### Best Practices
- Always use `declare(strict_types=1);` at the top of files
- Prefer early returns over nested conditionals
- Use null coalescing operator: `$value = $data['key'] ?? 'default';`
- Use spaceship operator for comparisons: `$a <=> $b`
- Leverage arrow functions for simple callbacks
- Use typed properties to enforce type safety

MARKDOWN;
