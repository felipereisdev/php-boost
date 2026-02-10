# 📖 Guia Completo de Features - PHP Boost

> Documentação detalhada de todas as features implementadas, com exemplos práticos de uso e configuração.

---

## 📑 Índice

1. [Instalação e Configuração Inicial](#1-instalação-e-configuração-inicial)
2. [Geração de Guidelines para IA](#2-geração-de-guidelines-para-ia)
3. [Validação de Código](#3-validação-de-código)
4. [Project Health Score](#4-project-health-score)
5. [Análise com Inteligência Artificial](#5-análise-com-inteligência-artificial)
6. [Guias de Migração](#6-guias-de-migração)
7. [Geração de Snippets](#7-geração-de-snippets)
8. [Performance Profiling](#8-performance-profiling)
9. [Geração de Documentação](#9-geração-de-documentação)
10. [MCP Tools](#10-mcp-tools)
11. [Integração com Git](#11-integração-com-git)
12. [Team Sync](#12-team-sync)
13. [Templates Customizados](#13-templates-customizados)

---

## 1. Instalação e Configuração Inicial

### Instalação via Composer

```bash
composer require felipereisdev/php-boost
```

### Comando de Instalação

```bash
php artisan boost:install
```

#### Opções Disponíveis

| Opção | Descrição |
|-------|-----------|
| `--force` | Sobrescreve arquivos existentes sem fazer backup |
| `--claude-only` | Gera apenas o arquivo `CLAUDE.md` |
| `--agents-only` | Gera apenas o arquivo `AGENTS.md` |

#### O que o comando faz

1. Detecta informações do projeto (framework, PHP version, packages)
2. Gera `CLAUDE.md` com guidelines para Claude AI
3. Gera `AGENTS.md` com guidelines para desenvolvedores
4. Cria backup de arquivos existentes (`.backup-TIMESTAMP`)

#### Exemplos

```bash
# Instalação completa
php artisan boost:install

# Forçar regeneração sem backup
php artisan boost:install --force

# Gerar apenas CLAUDE.md
php artisan boost:install --claude-only

# Gerar apenas AGENTS.md
php artisan boost:install --agents-only
```

#### Arquivos Gerados

```
project-root/
├── CLAUDE.md          # Guidelines para Claude AI
├── AGENTS.md          # Guidelines para desenvolvedores
└── .php-boost/        # Diretório de configuração
    ├── templates/     # Templates customizados
    └── config.json    # Configurações do projeto
```

---

## 2. Geração de Guidelines para IA

O PHP Boost detecta automaticamente as características do seu projeto e gera guidelines personalizadas para agentes de IA.

### CLAUDE.md - Guidelines para Claude AI

Arquivo otimizado para uso com Claude (Anthropic), incluindo:

- Informações do projeto (nome, framework, versão PHP)
- Packages instalados (Laravel, Spatie, Filament, etc.)
- Estrutura de diretórios
- Guidelines de testing
- Convenções de código
- Padrões de arquitetura

### AGENTS.md - Guidelines para Desenvolvedores

Documento técnico com:

- Comandos de build/lint/test
- Compatibilidade com PHP 7.4+
- Code style e naming conventions
- Estrutura de diretórios
- Padrões de criação de ferramentas
- Convenções de testes

### Personalização

Você pode adicionar seções customizadas que serão preservadas durante atualizações:

```markdown
<!-- END AUTO-GENERATED -->
<!-- Custom sections below are preserved during updates -->

## Seção Customizada

Conteúdo personalizado que não será sobrescrito.
```

### Regeneração Automática

As guidelines são regeneradas automaticamente após:

- `composer install`
- `composer update`

Para desabilitar, adicione ao `composer.json`:

```json
{
    "extra": {
        "php-boost": {
            "auto-update": false
        }
    }
}
```

---

## 3. Validação de Código

### Comando: `boost:validate`

Valida o código do projeto contra as guidelines do PHP Boost.

```bash
php artisan boost:validate
```

#### Opções

| Opção | Padrão | Descrição |
|-------|--------|-----------|
| `--format` | `text` | Formato de saída (`text`, `json`) |
| `--ci` | `false` | Modo CI - retorna erro se score < threshold |
| `--threshold` | `70` | Score mínimo para passar no CI |

#### O que é validado

##### Strict Types
Verifica uso de `declare(strict_types=1)` nos arquivos PHP.

##### PSR Compliance
- PSR-1: Basic Coding Standard
- PSR-12: Extended Coding Standard

##### Type Safety
- Type hints em parâmetros
- Return types declarados
- Uso de nullable types

##### Security
- Uso inseguro de `eval()`
- `$_GET/$_POST` sem sanitização
- Senhas hardcoded

##### Database
- `SELECT *` em queries
- Raw SQL ao invés de ORM

#### Exemplo de Output

```
PHP Boost - Guideline Validation
=================================

Analyzing project...

Running validators...

✓ Strict Types: 85/100
  - Files analyzed: 127
  - Files with strict_types: 108
  - Files missing: 19

✗ PSR Compliance: 65/100
  - PSR-1 violations: 12
  - PSR-12 violations: 8

✓ Type Safety: 78/100
  - Functions with type hints: 156/200 (78%)

✗ Security: 55/100
  - Critical issues: 2
  - High issues: 5
  - Medium issues: 8

✓ Database: 92/100
  - SELECT * queries: 3
  - Raw SQL queries: 5

Overall Score: 75/100 🟡

Recommendations:
  1. Add declare(strict_types=1) to 19 files
  2. Fix PSR-12 violations in Controllers
  3. Add type hints to 44 functions
  4. Review 2 critical security issues
```

#### Uso no CI/CD

```yaml
# .github/workflows/quality.yml
name: Code Quality

on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Validate code
        run: php artisan boost:validate --ci --threshold=75
```

#### Output JSON

```bash
php artisan boost:validate --format=json > validation.json
```

```json
{
  "score": 75,
  "validations": {
    "strict_types": {
      "score": 85,
      "files_analyzed": 127,
      "files_with_strict_types": 108
    },
    "security": {
      "score": 55,
      "critical": 2,
      "high": 5,
      "medium": 8
    }
  },
  "recommendations": [
    "Add declare(strict_types=1) to 19 files",
    "Review 2 critical security issues"
  ]
}
```

---

## 4. Project Health Score

### Comando: `boost:health`

Calcula um score de saúde do projeto baseado em múltiplas métricas.

```bash
php artisan boost:health
```

#### Opções

| Opção | Descrição |
|-------|-----------|
| `--format=text` | Formato de saída (`text`, `json`) |
| `--save` | Salva histórico de scores |

#### Métricas Avaliadas

##### Code Quality (30%)
- PSR compliance
- Type safety
- Doc blocks
- Code complexity

##### Testing (25%)
- Test coverage
- Número de testes
- Qualidade dos testes

##### Security (20%)
- Vulnerabilidades conhecidas
- Práticas inseguras
- Credenciais hardcoded

##### Performance (15%)
- N+1 queries
- Cache usage
- Memory leaks

##### Maintainability (10%)
- Code duplication
- Size de classes/métodos
- Coupling

#### Exemplo de Output

```
PHP Boost - Project Health Score
=================================

Analyzing project health...

📊 Health Score: 82/100 🟢

Category Scores:
  ✓ Code Quality: 85/100 (weight: 30%)
  ✓ Testing: 78/100 (weight: 25%)
  ✓ Security: 92/100 (weight: 20%)
  ✗ Performance: 65/100 (weight: 15%)
  ✓ Maintainability: 88/100 (weight: 10%)

💪 Strengths:
  1. Excellent security practices
  2. Good maintainability
  3. High code quality

⚠️ Weaknesses:
  1. Performance issues detected (N+1 queries)
  2. Test coverage could be improved
  3. Some complex methods need refactoring

📋 Recommendations:
  1. Run `php artisan boost:profile` to identify performance bottlenecks
  2. Add tests for Controllers (current coverage: 65%)
  3. Refactor UserService::process() (complexity: 12)
  4. Enable query logging to detect N+1 issues
  5. Consider caching frequently accessed data
```

#### Histórico de Scores

```bash
# Salvar score atual
php artisan boost:health --save
```

Os scores são salvos em `.php-boost/health-history.json`:

```json
{
  "history": [
    {
      "date": "2026-02-10 10:30:00",
      "score": 82,
      "categories": {
        "code_quality": 85,
        "testing": 78,
        "security": 92,
        "performance": 65,
        "maintainability": 88
      }
    }
  ]
}
```

#### Dashboard HTML (Futuro)

```bash
php artisan boost:health --dashboard
```

Gera dashboard interativo em `public/boost-health.html`.

---

## 5. Análise com Inteligência Artificial

### Comando: `boost:analyze`

Sistema avançado de análise de código com IA que detecta padrões, anti-patterns e sugere best practices.

```bash
php artisan boost:analyze
```

#### Opções

| Opção | Descrição |
|-------|-----------|
| `--learn` | Aprende padrões do codebase |
| `--suggest` | Gera sugestões de guidelines |
| `--export=FILE` | Exporta guidelines para arquivo |
| `--format=text` | Formato de saída (`text`, `json`) |

### 5.1 AI-Powered Suggestions

Detecção automática de 8 padrões de código:

#### Padrões Detectados

| Padrão | Severidade | Descrição |
|--------|-----------|-----------|
| `raw_sql` | 🟡 Medium | Uso de queries SQL diretas |
| `select_all` | 🟢 Low | `SELECT *` em queries |
| `n_plus_one` | 🟠 High | Queries N+1 potenciais |
| `missing_type_hints` | 🟢 Low | Falta de type hints |
| `fat_controller` | 🟡 Medium | Controllers com muita lógica |
| `hard_coded_credentials` | 🔴 Critical | Credenciais hardcoded |
| `god_object` | 🟠 High | Classes muito grandes |
| `unused_use_statements` | 🟢 Low | Imports não utilizados |

#### Uso Básico

```bash
# Análise com sugestões
php artisan boost:analyze --suggest
```

#### Output Exemplo

```
🤖 PHP Boost AI Analyzer
Analyzing your codebase...

🔍 Scanning codebase for patterns...

📊 Analysis Summary:
  - Files analyzed: 127
  - Total issues: 45

Issues by severity:
  - Critical: 2
  - High: 8
  - Medium: 15
  - Low: 20

💡 Suggestions:

🔴 Never hard-code credentials
   Found 2 occurrences
   Example: Use env("API_KEY") or config("services.api.key")

🟠 Always eager load relationships
   Found 8 occurrences
   Example: Model::with("relation")->get()

🟡 Prefer Eloquent over raw SQL
   Found 15 occurrences
   Example: Use Model::where()->get() instead of DB::raw()

🟢 Specify columns in SELECT queries
   Found 20 occurrences
   Example: Model::select(["id", "name"])->get()

📝 Recommended Guidelines:

✓ Always eager load relationships
  Priority: High
  Reason: Found 8 occurrences in your codebase

✓ Never hard-code credentials
  Priority: Critical
  Reason: Found 2 occurrences in your codebase

✓ Prefer Eloquent over raw SQL
  Priority: Medium
  Reason: Found 15 occurrences in your codebase

✓ Analysis complete!
```

### 5.2 Continuous Learning System

Aprende com o código do projeto e adapta guidelines automaticamente.

```bash
php artisan boost:analyze --learn
```

#### O que é aprendido

##### Naming Conventions
- Method naming (camelCase vs snake_case)
- Class naming (PascalCase)
- Variable naming

##### Code Style
- Prevalência de type hints
- Uso de doc blocks
- Early returns vs nested conditions

##### Architecture Patterns
- Service Layer pattern
- Repository pattern
- DTO pattern

##### Common Practices
- Form Requests vs inline validation
- Logging patterns
- Testing patterns

#### Output Exemplo

```
📚 Learning patterns from codebase...

✓ Naming Conventions:
  - method_naming: camelCase
  - class_naming: PascalCase
  - variable_naming: camelCase

✓ Code Style:
  - type_hints: 78.5%
  - doc_blocks: 45.2%
  - return_early: 62.1%

✓ Architecture Patterns:
  ✓ service_layer: 80% usage
  ✓ repository_pattern: 60% usage
  ✗ dto_pattern: not found

📊 Analyzing commit history...
  - Total commits (3 months): 142
  - Average per week: 11.83

💡 Recommended guideline adaptations:
  - [architecture] Use service layer pattern for business logic (confidence: 90%)
  - [naming] Follow camelCase naming for methods (confidence: 85%)
  - [style] Prefer early returns over nested conditions (confidence: 80%)
```

#### Storage

Padrões aprendidos são salvos em `.php-boost/learned-patterns.json`:

```json
{
  "naming_conventions": {
    "method_naming": "camelCase",
    "class_naming": "PascalCase"
  },
  "code_style": {
    "type_hints": {
      "prevalence": 78.5
    },
    "return_early": {
      "prevalence": 62.1
    }
  },
  "architecture_patterns": {
    "service_layer": {
      "found": true,
      "usage": 80
    }
  }
}
```

### 5.3 Export de Guidelines

```bash
php artisan boost:analyze --export=docs/AI_GUIDELINES.md
```

Gera arquivo Markdown com guidelines recomendadas:

```markdown
# Recommended Guidelines

Auto-generated based on codebase analysis.

---

## Never hard-code credentials

**Priority:** Critical

**Reason:** Found 2 occurrences in your codebase

**Example:**

\```php
Use env("API_KEY") or config("services.api.key")
\```

---

## Always eager load relationships

**Priority:** High

**Reason:** Found 8 occurrences in your codebase

**Example:**

\```php
Model::with("relation")->get()
\```
```

### 5.4 JSON Output

```bash
php artisan boost:analyze --format=json > analysis.json
```

```json
{
  "summary": {
    "files_analyzed": 127,
    "total_issues": 45,
    "issues_by_severity": {
      "critical": 2,
      "high": 8,
      "medium": 15,
      "low": 20
    }
  },
  "suggestions": [
    {
      "pattern": "hard_coded_credentials",
      "severity": "critical",
      "occurrences": 2,
      "guideline": "Never hard-code credentials",
      "example": "Use env(\"API_KEY\")"
    }
  ],
  "guidelines": [
    {
      "title": "Always eager load relationships",
      "priority": "high",
      "reason": "Found 8 occurrences in your codebase"
    }
  ]
}
```

---

## 6. Guias de Migração

### Comando: `boost:migrate-guide`

Gera guias detalhados para migração entre versões de frameworks.

```bash
php artisan boost:migrate-guide --from=laravel-8 --to=laravel-11
```

#### Opções

| Opção | Descrição |
|-------|-----------|
| `--from` | Versão atual (ex: `laravel-8`) |
| `--to` | Versão alvo (ex: `laravel-11`) |

#### Versões Suportadas

- Laravel 8, 9, 10, 11
- Lumen 8, 9, 10
- PHP 7.4, 8.0, 8.1, 8.2, 8.3

#### Output Exemplo

```
📋 Migration Guide: Laravel 8 → Laravel 11

Step 1: Update PHP to 8.1+
  - Current: PHP 7.4
  - Required: PHP 8.1+
  - Install: https://www.php.net/downloads

Step 2: Update Composer dependencies
  - Run: composer require laravel/framework:^11.0
  - Check compatibility of packages

Step 3: Remove HTTP Kernel and Console Kernel
  - Delete: app/Http/Kernel.php
  - Delete: app/Console/Kernel.php
  - Reason: Bootstrap changes in Laravel 11

Step 4: Update middleware configuration
  - Create: bootstrap/app.php
  - Migrate middleware from Kernel

Step 5: Migrate route service provider
  - Update route definitions
  - Remove RouteServiceProvider

Step 6: Update configuration files
  - Review config files for changes
  - New defaults in Laravel 11

Step 7: Update tests
  - PHPUnit 10+ required
  - Update phpunit.xml

Breaking Changes: 12
  1. HTTP/Console Kernel removal
  2. Middleware configuration changes
  3. Route registration changes
  ...

Estimated effort: 4-8 hours

Resources:
  - Official upgrade guide: https://laravel.com/docs/11.x/upgrade
  - Laravel Shift: https://laravelshift.com
  - Community forum: https://laracasts.com/discuss

Recommended approach:
  Given the large version jump (8 → 11), consider:
  1. Upgrade incrementally: 8 → 9 → 10 → 11
  2. Test thoroughly at each step
  3. Use Laravel Shift for automated migration
  4. Review all breaking changes before starting
```

#### Salvando o Guia

```bash
php artisan boost:migrate-guide --from=laravel-8 --to=laravel-11 > MIGRATION_GUIDE.md
```

---

## 7. Geração de Snippets

### Comando: `boost:snippet`

Gera código boilerplate seguindo as guidelines do projeto.

```bash
php artisan boost:snippet <type> <name> [options]
```

#### Tipos Disponíveis

| Tipo | Descrição |
|------|-----------|
| `controller` | Controller REST |
| `resource-controller` | Resource Controller |
| `model` | Eloquent Model |
| `service` | Service class |
| `repository` | Repository class |
| `request` | Form Request |
| `resource` | API Resource |
| `migration` | Database migration |
| `test` | PHPUnit test |

#### Exemplos

##### Controller

```bash
php artisan boost:snippet controller UserController
```

Gera:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json([]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->noContent();
    }
}
```

##### Model

```bash
php artisan boost:snippet model User --with-factory
```

Gera:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $hidden = [];

    protected $casts = [];
}
```

##### Service

```bash
php artisan boost:snippet service UserService
```

Gera:

```php
<?php

namespace App\Services;

class UserService
{
    public function __construct()
    {
        //
    }

    public function execute(): mixed
    {
        //
    }
}
```

##### Repository

```bash
php artisan boost:snippet repository UserRepository
```

Gera:

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function __construct(private User $model)
    {
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->find($id)?->update($data) ?? false;
    }

    public function delete(int $id): bool
    {
        return $this->model->find($id)?->delete() ?? false;
    }
}
```

---

## 8. Performance Profiling

### Comando: `boost:profile`

Analisa performance da aplicação e identifica gargalos.

```bash
php artisan boost:profile
```

#### O que é analisado

##### N+1 Queries
Detecta queries executadas dentro de loops.

##### Missing Eager Loading
Identifica relacionamentos que deveriam usar `with()`.

##### Slow Queries
Queries que demoram mais de 100ms.

##### Memory Usage
Consumo de memória por operação.

##### Cache Opportunities
Dados que poderiam ser cacheados.

#### Output Exemplo

```
🔍 PHP Boost - Performance Profiler

Analyzing application performance...

⚠️ N+1 Queries Detected: 3

  1. app/Http/Controllers/UserController.php:45
     Problem: Loading posts inside user loop
     Solution: User::with('posts')->get()
     Impact: 127 extra queries

  2. app/Http/Controllers/PostController.php:32
     Problem: Loading comments inside post loop
     Solution: Post::with('comments')->get()
     Impact: 89 extra queries

🐌 Slow Queries: 2

  1. SELECT * FROM users WHERE role = 'admin' (245ms)
     File: app/Services/UserService.php:78
     Suggestion: Add index on 'role' column

  2. SELECT * FROM posts ORDER BY created_at DESC (189ms)
     File: app/Http/Controllers/PostController.php:25
     Suggestion: Add index on 'created_at', limit results

💾 Cache Opportunities: 5

  1. User roles (accessed 234 times)
     Suggestion: Cache::remember('user.roles', 3600, ...)

  2. Categories (accessed 156 times)
     Suggestion: Cache::remember('categories', 7200, ...)

📊 Performance Score: 65/100

Recommendations:
  1. Fix 3 N+1 query issues (High priority)
  2. Add database indexes (Medium priority)
  3. Implement caching for frequently accessed data (Medium priority)
  4. Consider using Redis for session storage (Low priority)

Estimated improvement: 40-60% faster
```

#### Integração com Laravel Telescope

```bash
# Requer Laravel Telescope instalado
composer require laravel/telescope --dev
php artisan telescope:install

# Executar profiling com Telescope
php artisan boost:profile --telescope
```

---

## 9. Geração de Documentação

### Comando: `boost:docs`

Gera documentação técnica do projeto automaticamente.

```bash
php artisan boost:docs
```

#### Opções

| Opção | Descrição |
|-------|-----------|
| `--openapi` | Gera documentação OpenAPI |
| `--database` | Gera documentação do schema |
| `--architecture` | Gera diagramas de arquitetura |
| `--all` | Gera toda documentação |

#### Tipos de Documentação

##### OpenAPI/Swagger

```bash
php artisan boost:docs --openapi
```

Gera `docs/openapi.yaml`:

```yaml
openapi: 3.0.0
info:
  title: Project API
  version: 1.0.0
paths:
  /api/users:
    get:
      summary: List users
      responses:
        200:
          description: Success
  /api/users/{id}:
    get:
      summary: Get user
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
```

##### Database Schema

```bash
php artisan boost:docs --database
```

Gera `docs/database.md`:

```markdown
# Database Schema

## Tables

### users
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| id | bigint | NO | - | PRI |
| name | varchar(255) | NO | - | |
| email | varchar(255) | NO | - | UNI |
| created_at | timestamp | YES | NULL | |

### posts
| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| id | bigint | NO | - | PRI |
| user_id | bigint | NO | - | FOR |
| title | varchar(255) | NO | - | |
```

##### Architecture Diagrams

```bash
php artisan boost:docs --architecture
```

Gera `docs/architecture.md` com diagramas Mermaid.

---

## 10. MCP Tools

PHP Boost fornece ferramentas MCP (Model Context Protocol) para integração com agentes de IA.

### 10.1 DatabaseSchema

Obtém schema completo do banco de dados.

```json
{
  "tool": "DatabaseSchema",
  "arguments": {
    "table": "users"
  }
}
```

### 10.2 DatabaseQuery

Executa queries SELECT (somente leitura).

```json
{
  "tool": "DatabaseQuery",
  "arguments": {
    "query": "SELECT * FROM users WHERE role = ?",
    "bindings": ["admin"]
  }
}
```

### 10.3 GetConfig

Obtém configurações da aplicação.

```json
{
  "tool": "GetConfig",
  "arguments": {
    "key": "app.name"
  }
}
```

### 10.4 ReadLogEntries

Lê logs da aplicação.

```json
{
  "tool": "ReadLogEntries",
  "arguments": {
    "lines": 50,
    "level": "error"
  }
}
```

### 10.5 ListRoutes

Lista todas as rotas (Laravel/Lumen).

```json
{
  "tool": "ListRoutes",
  "arguments": {
    "method": "GET"
  }
}
```

### 10.6 ProjectInspector

Inspeciona estrutura do projeto.

```json
{
  "tool": "ProjectInspector",
  "arguments": {}
}
```

---

## 11. Integração com Git

### Auto-commit

PHP Boost pode criar commits automaticamente após gerar guidelines.

```json
{
  "extra": {
    "php-boost": {
      "git": {
        "auto-commit": true,
        "commit-message": "docs: update AI guidelines [skip ci]"
      }
    }
  }
}
```

### .gitattributes

Adicione ao `.gitattributes`:

```
CLAUDE.md export-ignore
AGENTS.md export-ignore
.php-boost/ export-ignore
```

### Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit

php artisan boost:validate --ci --threshold=70
if [ $? -ne 0 ]; then
    echo "Code validation failed. Commit aborted."
    exit 1
fi
```

---

## 12. Team Sync

Compartilhe configurações do PHP Boost entre membros do time.

### Export de Configurações

```bash
php artisan boost:export config.json
```

Gera:

```json
{
  "version": "1.0",
  "project": {
    "name": "my-project",
    "framework": "laravel",
    "php_version": "8.1"
  },
  "templates": {
    "custom": [
      "spatie-permission",
      "filament"
    ]
  },
  "settings": {
    "auto_update": true,
    "locale": "pt-BR"
  }
}
```

### Import de Configurações

```bash
php artisan boost:import config.json
```

### Webhook Integration

```json
{
  "extra": {
    "php-boost": {
      "webhooks": [
        {
          "url": "https://hooks.slack.com/...",
          "events": ["guidelines-updated"]
        }
      ]
    }
  }
}
```

---

## 13. Templates Customizados

Crie templates personalizados para seu projeto.

### Estrutura

```
.php-boost/
└── templates/
    └── custom/
        └── my-template.php
```

### Exemplo de Template

```php
<?php

return [
    'name' => 'My Custom Template',
    'description' => 'Custom guidelines for my project',
    'sections' => [
        'architecture' => [
            'title' => 'Architecture',
            'content' => [
                'Use CQRS pattern',
                'Implement event sourcing',
            ],
        ],
        'testing' => [
            'title' => 'Testing',
            'content' => [
                'Write unit tests for all services',
                'Use Pest for testing',
            ],
        ],
    ],
];
```

### Uso

```bash
php artisan boost:install --template=my-template
```

---

## 📚 Recursos Adicionais

### Documentação

- [FEATURES.md](FEATURES.md) - Roadmap completo
- [AGENTS.md](AGENTS.md) - Guidelines para desenvolvedores
- [CLAUDE.md](CLAUDE.md) - Guidelines para Claude AI

### Suporte

- GitHub Issues: https://github.com/felipereisdev/php-boost/issues
- Discussions: https://github.com/felipereisdev/php-boost/discussions

### Contribuindo

Veja [CONTRIBUTING.md](CONTRIBUTING.md) para guidelines de contribuição.

---

**Versão:** 1.0.0  
**Última atualização:** 2026-02-10  
**Autor:** Felipe Reis
