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
composer require felipereisdev/php-boost
```

The service provider will be automatically registered.

### Lumen (8.x to 11.x)

```bash
composer require felipereisdev/php-boost
```

Register the service provider in `bootstrap/app.php`:

```php
$app->register(FelipeReisDev\PhpBoost\Lumen\BoostServiceProvider::class);
```

### Standalone PHP

```bash
composer require felipereisdev/php-boost
```

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

### Code Quality & Validation (Laravel Command)

Validate your code against best practices and guidelines:

```bash
php artisan boost:validate
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

### Migration Path Generator (Laravel Command)

Generate step-by-step migration guides for framework upgrades:

```bash
php artisan boost:migrate-guide --from=laravel-8 --to=laravel-11
```

Provides:
- **Migration Steps**: Detailed checklist for upgrade
- **Breaking Changes**: Critical, high, medium priority changes
- **Effort Estimation**: Time and complexity estimates
- **Recommended Approach**: Incremental vs direct upgrade
- **Resources**: Documentation links and tools

### Project Health Score (Laravel Command)

Get comprehensive health analysis of your project:

```bash
php artisan boost:health
```

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

### Start MCP Server

### Laravel

Start the MCP server:

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

### Framework Tools (Registered by Default)

| Tool | Description | Framework | Read-Only |
|------|-------------|-----------|-----------|
| `ListRoutes` | List application routes | Laravel/Lumen | ✓ |

Other framework tools exist in the codebase and can be registered manually when needed.

## Available Commands

### Laravel

| Command | Description |
|---------|-------------|
| `boost:install` | Generate AI guidelines (CLAUDE.md, AGENTS.md) |
| `boost:start` | Start MCP server |
| `boost:validate` | Validate code against guidelines |
| `boost:migrate-guide` | Generate migration path between versions |
| `boost:health` | Calculate project health score |
| `boost:snippet` | Generate code snippets following project guidelines |
| `boost:profile` | Analyze performance and detect issues |
| `boost:docs` | Generate project documentation |
| `boost:analyze` | AI-powered codebase analysis and suggestions |

### Lumen

| Command | Description |
|---------|-------------|
| `boost:start` | Start MCP server |

### Standalone PHP

| Command | Description |
|---------|-------------|
| `boost-server` | Start MCP server |
| `boost-install` | Generate AI guidelines |
| `boost-template` | Manage custom templates |
| `boost-sync` | Export/import configuration |
| `boost-validate` | Validate code quality |

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
