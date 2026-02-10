<?php

return <<<'MARKDOWN'

## Boas Práticas Laravel 8

### Arquitetura
- Use padrão Service/Repository para lógica de negócio
- Mantenha Controllers finos - delegue para Services
- Use Form Requests para validação
- Organize por feature ou domínio, não por camada técnica

### Rotas
- Agrupe rotas relacionadas com `Route::group()`
- Use route model binding para controllers mais limpos
- Nomeie todas as rotas: `->name('usuarios.index')`
- Use resource controllers quando apropriado
- Separe rotas de API das rotas web

### Eloquent
- Use query scopes para queries reutilizáveis
- Carregue relacionamentos com eager loading para evitar queries N+1
- Use API Resources para formatação de respostas
- Defina fillable ou guarded nos models
- Use accessors e mutators para transformação de dados

```php
class User extends Model
{
    protected $fillable = ['name', 'email'];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

### Validação
- Sempre use Form Requests para validação complexa
- Mantenha regras de validação DRY

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];
    }
}
```

### Banco de Dados
- Use migrations para mudanças de schema
- Use seeders para dados de teste
- Use factories para criação de models em testes
- Adicione indexes em colunas consultadas frequentemente
- Use transações de banco para operações multi-etapa

### Cache
- Cache queries caras com `Cache::remember()`
- Limpe cache com `php artisan cache:clear`
- Use Redis para melhor performance

### Jobs & Filas
- Use jobs para tarefas longas
- Torne jobs idempotentes
- Trate falhas de jobs com retries e timeouts

### Segurança
- Use Laravel Sanctum para autenticação de API
- Habilite proteção CSRF em formulários
- Valide e sanitize todas as entradas de usuário
- Use `bcrypt()` ou `Hash::make()` para senhas
- Evite queries SQL raw - use Query Builder ou Eloquent

### Performance
- Use `php artisan optimize` para produção
- Habilite opcache em produção
- Use lazy collections para datasets grandes
- Coloque operações pesadas em fila
- Minimize overhead de middleware

### Testes
- Escreva testes de feature para caminhos críticos
- Use factories para criação de models
- Use trait `RefreshDatabase` em testes
- Teste regras de validação e casos extremos

### Comandos Artisan
- `php artisan make:*` - Gerar código boilerplate
- `php artisan migrate` - Executar migrations
- `php artisan db:seed` - Popular banco de dados
- `php artisan queue:work` - Processar jobs em fila
- `php artisan config:cache` - Cachear configuração

### Dependências
- Mantenha Laravel e pacotes atualizados
- Use versionamento semântico em composer.json
- Trave dependências com composer.lock

MARKDOWN;
