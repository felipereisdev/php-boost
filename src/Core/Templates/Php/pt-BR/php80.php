<?php

return <<<'MARKDOWN'

## Boas Práticas PHP 8.0

### Sistema de Tipos
- Union types disponíveis: `string|int`, `array|null`
- Tipo mixed disponível
- Tipos nullable: `?string` ou `string|null`
- Tipo de retorno static disponível
- Tipos de retorno void e never (8.1+)

### Declaração de Propriedades
- Constructor property promotion disponível
- Propriedades com type hints obrigatórios
- Atributos (#[Attribute]) disponíveis

```php
class Exemplo
{
    public function __construct(
        private string $nome,
        private int $idade
    ) {}
}
```

### Novos Recursos do PHP 8.0
- Constructor property promotion
- Named arguments: `funcao(parametro: 'valor')`
- Match expressions
- Union types
- Mixed type
- Static return type
- Attributes
- Nullsafe operator: `$objeto?->metodo()`

### Funções de String
- Funções nativas: `str_starts_with()`, `str_ends_with()`, `str_contains()`

### Tratamento de Erros
- Use try-catch para exceptions
- Erros de tipo lançam TypeError
- Habilite strict_types com `declare(strict_types=1);`

### Match Expression
```php
$resultado = match ($valor) {
    1 => 'um',
    2 => 'dois',
    default => 'outro'
};
```

### Named Arguments
```php
funcao(
    nome: 'João',
    idade: 30,
    email: 'joao@example.com'
);
```

### Nullsafe Operator
```php
$pais = $usuario?->endereco?->pais;
```

### Boas Práticas
- Sempre use `declare(strict_types=1);` no topo dos arquivos
- Prefira early returns sobre condicionais aninhados
- Use match ao invés de switch quando apropriado
- Aproveite constructor property promotion para código mais limpo
- Use nullsafe operator para encadeamento seguro
- Use union types para maior flexibilidade de tipos
- Use attributes ao invés de docblocks para metadados

MARKDOWN;
