# PHP Boost

Framework-agnostic MCP Server for PHP 7.4+ with support for Laravel, Lumen and standalone PHP applications.

## Features

- **PHP 7.4+ Compatible**: Works with legacy PHP versions
- **Framework Agnostic**: Core implementation works with any PHP application
- **Multiple Adapters**: Laravel, Lumen, and standalone PHP support
- **MCP Protocol**: Full Model Context Protocol implementation via JSON-RPC 2.0
- **Rich Tools**: Database queries, config reading, log reading, and more
- **Auto-Generated Guidelines**: Automatically generates AI assistant guidelines (CLAUDE.md, AGENTS.md) based on your project
- **Code Quality Validation**: Integrates with PHP_CodeSniffer and PHPStan for automated code quality checks
- **Migration Path Generator**: Generates step-by-step guides for framework version upgrades
- **Project Health Score**: Comprehensive health analysis with actionable recommendations

## Requirements

- PHP 7.4 or higher
- PDO extension
- JSON extension

## Installation

### Laravel (8.x to 11.x)

```bash
composer require --dev felipereisdev/php-boost
```

The service provider will be automatically registered.

### Lumen (8.x to 11.x)

```bash
composer require --dev felipereisdev/php-boost
```

Register the service provider in `bootstrap/app.php`:

```php
$app->register(FelipeReisDev\PhpBoost\Lumen\BoostServiceProvider::class);
```

### Standalone PHP

```bash
composer require --dev felipereisdev/php-boost
```

> Install this package as a dev dependency inside each project. Global installation is not supported.

## Usage

### Generate AI Guidelines

After installation, generate AI assistant guidelines for your project:

**Laravel:**
```bash
php artisan boost:install
```

**Lumen / Standalone:**
```bash
./vendor/bin/boost-install
```

This will create:
- `CLAUDE.md` - Guidelines for Claude Desktop
- `AGENTS.md` - Guidelines for AI coding agents

The guidelines are auto-generated based on:
- Framework and version (Laravel, Lumen, Standalone)
- PHP version and constraints
- Database driver (MySQL, PostgreSQL, SQLite, Oracle, SQL Server)
- Environment (Herd, Sail, Docker)
- Installed packages (Sanctum, Livewire, Inertia, etc.)
- Test framework (PHPUnit, Pest)
- Project structure

**Options:**
- `--claude-only` - Generate only CLAUDE.md
- `--agents-only` - Generate only AGENTS.md
- `--interactive` - Interactive mode with template selection
- `--git-commit` - Auto-commit generated files to git
- `--git-setup` - Setup git integration (.gitattributes, hooks)
- `--force` - Overwrite without backup

### Custom Templates

Create custom templates to extend or override default guidelines:

```bash
./vendor/bin/boost-template new package my-package
./vendor/bin/boost-template new database oracle
./vendor/bin/boost-template list
```

Templates are stored in `.php-boost/templates/` and support merge strategies:
- `replace` - Replace default template
- `append` - Add content after default template
- `prepend` - Add content before default template

See [docs/CUSTOM_TEMPLATES.md](docs/CUSTOM_TEMPLATES.md) for details.

### Code Quality & Validation (MCP Tool)

Validate your code against best practices and guidelines via `BoostValidate` tool call.

Auto-fix code style using project formatter:

```bash
php artisan boost:fix
```

Options:
- `--format=json` - Output in JSON format
- `--ci` - CI mode (exits with error code on low score)
- `--threshold=70` - Minimum score threshold for CI mode

The validation includes:
- **PHP Best Practices**: strict_types, type hints, PSR compliance
- **Framework Conventions**: Laravel/Lumen specific patterns
- **Code Style**: Formatting, line length, whitespace
- **Security**: eval() usage, weak hashing, SQL injection risks
- **Performance**: N+1 queries, pagination, nested loops

### Migration Path Generator (MCP Tool)

Generate step-by-step migration guides via `BoostMigrateGuide` tool call.

Provides:
- **Migration Steps**: Detailed checklist for upgrade
- **Breaking Changes**: Critical, high, medium priority changes
- **Effort Estimation**: Time and complexity estimates
- **Recommended Approach**: Incremental vs direct upgrade
- **Resources**: Documentation links and tools

### Project Health Score (MCP Tool)

Get comprehensive health analysis via `BoostHealth` tool call.

Options:
- `--format=json` - JSON output for integrations
- `--save` - Save score to history for tracking trends

Analyzes:
- **Code Quality**: PSR compliance, type safety, patterns (25%)
- **Security**: Security packages, vulnerabilities (20%)
- **Performance**: Caching, queues, optimization (15%)
- **Testing**: Test coverage and framework (15%)
- **Documentation**: README, AI guidelines (10%)
- **Dependencies**: Lock file, outdated packages (10%)
- **Architecture**: Clean patterns, service layer (5%)

### MCP Client Auto-Start

The MCP client should start the server process automatically when needed.  
Do not run `boost:start` or `boost-server` manually in a terminal for normal MCP usage.

### Laravel

Command used by MCP client:

```bash
php artisan boost:start
```

### Lumen

```bash
php artisan boost:start
```

### Standalone PHP

```bash
./vendor/bin/boost-server
```

Or create a custom bootstrap:

```php
<?php

require 'vendor/autoload.php';

use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Mcp\Transport\StdioTransport;

$config = [
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'mydb',
        'username' => 'root',
        'password' => '',
    ],
];

$server = new Server(new StdioTransport(), $config);
$server->start();
```

## Available Tools

### Core Tools (Registered by Default)

| Tool | Description | Framework | Read-Only |
|------|-------------|-----------|-----------|
| `GetConfig` | Read configuration values | All | ✓ |
| `DatabaseSchema` | Get database schema | All | ✓ |
| `DatabaseQuery` | Execute read-only SQL queries | All | ✓ |
| `ReadLogEntries` | Read application logs | All | ✓ |
| `ExplainQuery` | EXPLAIN plans + bottleneck summary | All | ✓ |
| `TableDDL` | Real DDL for table/view/index/constraint | All | ✓ |
| `LogErrorDigest` | Error fingerprint/frequency digest | All | ✓ |
| `SchemaDiff` | Schema drift vs pending migrations | All | ✓ |
| `ListModels` | Discover Eloquent-like models | All | ✓ |
| `ModelRelations` | Map model relations | All | ✓ |
| `FindNPlusOneRisk` | Heuristic static N+1 risk scan | All | ✓ |
| `SafeMigrationPreview` | Migration impact preview (safe/read-only) | All | ✓ |
| `QueueHealth` | Queue health summary with current coverage | All | ✓ |
| `APIContractMap` | Endpoint contract map (Orion fallback) | All | ✓ |
| `FeatureFlagsConfig` | Feature flags + env/config divergence checks | All | ✓ |
| `PolicyAudit` | Endpoint -> policy/gate audit matrix | All | ✓ |
| `DeadCodeHints` | Unreferenced-code heuristic hints | All | ✓ |

### Framework Tools (Registered by Default)

| Tool | Description | Framework | Read-Only |
|------|-------------|-----------|-----------|
| `ListRoutes` | List application routes | Laravel/Lumen | ✓ |
| `ApplicationInfo` | Application metadata and environment details | Laravel | ✓ |
| `ListArtisanCommands` | List available Artisan commands | Laravel | ✓ |
| `QueueStatus` | Queue status and retry operations | Laravel | ✗ |
| `CacheManager` | Cache inspection and mutation utilities | Laravel | ✗ |
| `Tinker` | Execute PHP expressions in app context | Laravel | ✗ |

### Driver Support Matrix (Phase 1 Tools)

| Tool | MySQL | PostgreSQL | SQLite | SQL Server | Oracle |
|------|-------|------------|--------|------------|--------|
| `ExplainQuery` | JSON explain | ANALYZE+BUFFERS+JSON | Fallback plan | Fallback plan | Fallback plan |
| `TableDDL` | Native SHOW CREATE | Catalog + pg_get_* | sqlite_master | OBJECT_DEFINITION fallback | DBMS_METADATA fallback |
| `SchemaDiff` | Heuristic pending migrations | Heuristic pending migrations | Heuristic pending migrations | Limited | Limited |
| `LogErrorDigest` | N/A (filesystem) | N/A | N/A | N/A | N/A |

Notes:
- Fallback modes return `status=warning` with explicit limitations in `meta.limitations`.
- `SchemaDiff` parser is heuristic and may degrade for dynamic migration logic.

## Production Operations

### Response Contract

All tool calls now return a stable JSON envelope:

- `tool`
- `status` (`ok`, `warning`, `error`)
- `summary`
- `meta` (includes `duration_ms`)
- `data`
- optional `findings` and `errors`

Legacy tool outputs are automatically normalized to this envelope at the MCP server layer.

### Reliability Checklist

- Keep MCP transport output clean (JSON-RPC only on stdout)
- Validate with MCP flow tests (`initialize -> tools/list -> tools/call`)
- Prefer read-only tools in production automation
- Monitor warnings from fallback modes (`meta.limitations`)

### Troubleshooting

- `Server not initialized`
  - Ensure client sends `initialize` before `tools/list` or `tools/call`.
- `Tool 'X' not found`
  - Verify adapter registration (`ToolRegistrar`) and call `tools/list`.
- `Queue telemetry collected with limitations`
  - Confirm `jobs` / `failed_jobs` tables exist and horizon tables for worker metrics.
- `SchemaDiff` low confidence / partial drift
  - Check for dynamic migrations or raw SQL (`DB::statement`) and review manually.

## Available Commands

### Laravel

| Command | Description |
|---------|-------------|
| `boost:install` | Generate AI guidelines (CLAUDE.md, AGENTS.md) |
| `boost:start` | MCP server entrypoint (auto-started by MCP client) |
| `boost:fix` | Auto-fix code style issues |

### Lumen

| Command | Description |
|---------|-------------|
| `boost:start` | MCP server entrypoint (auto-started by MCP client) |

### Standalone PHP

| Command | Description |
|---------|-------------|
| `boost-server` | MCP server entrypoint (auto-started by MCP client) |
| `boost-install` | Generate AI guidelines |
| `boost-template` | Manage custom templates |
| `boost-sync` | Export/import configuration |
| `boost-validate` | Validate code quality |

## MCP Tool Equivalents (Breaking Change)

The following CLI commands were removed and replaced by MCP tools:

| Removed CLI | MCP Tool |
|------------|----------|
| `boost:validate` | `BoostValidate` |
| `boost:migrate-guide` | `BoostMigrateGuide` |
| `boost:health` | `BoostHealth` |
| `boost:snippet` | `BoostSnippet` |
| `boost:profile` | `BoostProfile` |
| `boost:docs` | `BoostDocs` |
| `boost:analyze` | `BoostAnalyze` |

Example MCP payload:

```json
{
  "name": "BoostValidate",
  "arguments": {
    "ci": true,
    "threshold": 75
  }
}
```

## Configuration

Publish the configuration file (Laravel only):

```bash
php artisan vendor:publish --tag=boost-config
```

## Architecture

```
php-boost/
├── src/
│   ├── Core/          # Framework-agnostic MCP implementation
│   ├── Laravel/       # Laravel 8.x-11.x adapter
│   ├── Lumen/         # Lumen 8.x-11.x adapter
│   └── Standalone/    # Standalone PHP bootstrap
```

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.

## Inspired By

This project is inspired by [Laravel Boost](https://github.com/laravel/boost) but adapted for PHP 7.4+ compatibility and framework-agnostic usage.
