<?php

return <<<'MARKDOWN'

## Boas Práticas PHP 7.4

### Sistema de Tipos
- Use type hints para parâmetros e tipos de retorno
- Tipos nullable: `?string`, `?int`, `?array`
- Tipo de retorno void: `function doSomething(): void`
- Type hints array e callable disponíveis
- Sem union types (PHP 8.0+)
- Sem tipo mixed (PHP 8.0+)
- Sem tipo de retorno static (PHP 8.0+)

### Declaração de Propriedades
- Propriedades devem ser declaradas separadamente do construtor
- Sem constructor property promotion (PHP 8.0+)
- Type hints obrigatórios para propriedades

```php
class Exemplo
{
    private string $nome;
    private int $idade;

    public function __construct(string $nome, int $idade)
    {
        $this->nome = $nome;
        $this->idade = $idade;
    }
}
```

### Recursos NÃO Disponíveis no PHP 7.4
- Constructor property promotion
- Named arguments
- Match expressions (use switch)
- Union types
- Mixed type
- Static return type
- Attributes (use docblocks)
- Enums (use constantes de classe)
- Readonly properties
- Nullsafe operator (use ternário ou verificações de null)

### Funções de String
- Use polyfills para: `str_starts_with()`, `str_ends_with()`, `str_contains()`
- Essas funções são nativas no PHP 8.0+

### Tratamento de Erros
- Use try-catch para exceptions
- Erros de tipo lançam TypeError
- Habilite strict_types com `declare(strict_types=1);`

### Funções de Array
- Use `array_key_exists()`, `in_array()`, `array_filter()`, `array_map()`
- Operador de spread de array disponível: `[...$array1, ...$array2]`
- Arrow functions disponíveis: `fn($x) => $x * 2`

### Boas Práticas
- Sempre use `declare(strict_types=1);` no topo dos arquivos
- Prefira early returns sobre condicionais aninhados
- Use null coalescing operator: `$valor = $dados['chave'] ?? 'padrao';`
- Use spaceship operator para comparações: `$a <=> $b`
- Aproveite arrow functions para callbacks simples
- Use propriedades tipadas para garantir type safety

MARKDOWN;
