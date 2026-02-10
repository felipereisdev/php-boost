<?php

return <<<'MARKDOWN'

## Boas Práticas MySQL

### Conexão
- Use codificação UTF-8: `utf8mb4` e `utf8mb4_unicode_ci`
- Configure timezone na conexão
- Use conexões persistentes para performance

### Design de Schema
- Use tipos de dados apropriados
  - `VARCHAR` para strings de tamanho variável (máx 255 para campos indexados)
  - `TEXT` para conteúdo longo
  - `INT UNSIGNED` para inteiros positivos
  - `DECIMAL` para valores monetários
  - `TIMESTAMP` para timestamps
  - `JSON` para dados estruturados (MySQL 5.7+)

### Índices
- Adicione índices em colunas consultadas frequentemente
- Use índices compostos para queries multi-coluna
- Evite over-indexing (impacta performance de INSERT/UPDATE)
- Use `EXPLAIN` para analisar performance de queries

```sql
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

### Queries
- Use prepared statements para prevenir SQL injection
- Evite `SELECT *` - especifique apenas colunas necessárias
- Use `LIMIT` para result sets grandes
- Use `JOIN` apropriadamente (INNER vs LEFT vs RIGHT)
- Evite queries N+1 com eager loading

### Transações
- Use transações para operações multi-etapa
- Mantenha transações curtas
- Trate deadlocks graciosamente

```php
DB::transaction(function () {
    // Múltiplas operações
});
```

### Performance
- Use query caching quando apropriado
- Otimize queries lentas com índices
- Use `EXPLAIN` para entender execução de queries
- Monitore slow query log
- Use connection pooling

### Colunas JSON (MySQL 5.7+)
- Armazene dados estruturados como JSON
- Consulte dados JSON eficientemente

```php
$users = DB::table('users')
    ->whereJsonContains('options->languages', 'en')
    ->get();
```

### Full-Text Search
- Use índices full-text para funcionalidade de busca
- Melhor que `LIKE '%termo%'` para datasets grandes

```sql
ALTER TABLE posts ADD FULLTEXT(title, body);
SELECT * FROM posts WHERE MATCH(title, body) AGAINST('palavra-chave');
```

### Charset & Collation
- Use `utf8mb4` para suporte completo a Unicode (incluindo emojis)
- Use `utf8mb4_unicode_ci` para comparações case-insensitive

### Manutenção
- Otimize tabelas regularmente
- Monitore espaço em disco
- Faça backup de banco de dados regularmente
- Mantenha MySQL atualizado

MARKDOWN;
