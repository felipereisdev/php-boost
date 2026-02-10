# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- MCP Tool infrastructure for phased roadmap:
  - `ToolResult` envelope (`tool`, `status`, `summary`, `meta`, `data`, optional `findings`/`errors`)
  - `DriverCapabilities` for multi-DB feature detection and identifier/query safety checks
  - `ToolRegistrar` with adapter-specific registration methods:
    - `registerCoreTools($registry, $config)`
    - `registerLaravelTools($registry, $config)`
    - `registerLumenTools($registry, $config)`
- New core services:
  - `DatabaseIntrospectorService`
  - `MigrationImpactAnalyzerService`
  - `LogFingerprintService`
  - `StaticAnalysisService`
  - `EloquentModelMapService`
  - `ApiContractService`
  - `QueueTelemetryService`
  - `EnvConfigDiffService`
- New MCP tools (read-only):
  - `ExplainQuery`
  - `TableDDL`
  - `LogErrorDigest`
  - `SchemaDiff`
  - `ListModels`
  - `ModelRelations`
  - `FindNPlusOneRisk`
  - `SafeMigrationPreview`
  - `QueueHealth`
  - `APIContractMap`
  - `FeatureFlagsConfig`
  - `PolicyAudit`
  - `DeadCodeHints`
- Phase 3 refinements:
  - `FindNPlusOneRisk`: richer static signals, severity, evidence, confidence scoring
  - `SafeMigrationPreview`: operation-level impact profiles and aggregate risk score
  - `QueueHealth`: DB queue metrics (pending/failed/retries/lag) + Horizon/Redis heuristics
  - `DeadCodeHints`: reference-aware confidence with route/console usage signals
- Phase 4 reliability hardening:
  - MCP server loop now exits cleanly on transport EOF (prevents idle spin on closed streams)
  - `tools/call` responses include execution `duration_ms` metadata when tool envelope has `meta`
  - improved tool result JSON encoding resilience for non-UTF8/encoding edge cases
  - unknown tool calls now return `METHOD_NOT_FOUND` (`-32601`)
  - end-to-end MCP tests for `initialize -> tools/list -> tools/call` flow
- Phase 5 release/operations:
  - Integration suite added (`tests/Integration`) with SQLite scenarios for `ExplainQuery`, `TableDDL`, `SchemaDiff`, `QueueHealth`
  - Golden fixtures added for `APIContractMap`, `PolicyAudit`, `FindNPlusOneRisk`, `DeadCodeHints`
  - Server-level response normalization now enforces stable envelope for legacy tools
  - Operational runbook and troubleshooting added to README
- Phase 6 MCP command-tools migration (breaking):
  - Added MCP tools:
    - `BoostValidate`
    - `BoostMigrateGuide`
    - `BoostHealth`
    - `BoostSnippet`
    - `BoostProfile`
    - `BoostDocs`
    - `BoostAnalyze`
  - Removed legacy Laravel CLI commands:
    - `boost:validate`
    - `boost:migrate-guide`
    - `boost:health`
    - `boost:snippet`
    - `boost:profile`
    - `boost:docs`
    - `boost:analyze`
  - Updated provider registration and documentation to point to MCP tool equivalents
- Provider/bootstrap updates:
  - Laravel provider now registers complete core and Laravel toolsets through `ToolRegistrar`
  - Lumen provider now registers complete core and Lumen toolsets through `ToolRegistrar`
  - Standalone bootstrap now registers complete core toolset through `ToolRegistrar`

### Planned
- IDE Integration (VSCode, PHPStorm)
- Template Marketplace
- HTTP Transport (SSE)
- Symfony support
- Web Dashboard for visualization

---

## [1.1.0] - 2026-02-10

### 🤖 Phase 5: Artificial Intelligence

#### Added
- **AI-Powered Code Analysis** - Intelligent code analysis system
  - `CodePatternDetector`: Automatically detects 8 code patterns
  - `GuidelineRecommender`: Recommends guidelines based on analysis
  - `PatternLearningSystem`: Continuous learning system
- **Command `boost:analyze`** - AI-powered analysis
  - `--learn`: Learn patterns from codebase
  - `--suggest`: Generate guideline suggestions
  - `--export`: Export guidelines to Markdown
  - `--format`: JSON output support
- **Pattern Detection**:
  - `raw_sql` (Medium): Raw SQL queries
  - `select_all` (Low): SELECT * in queries
  - `n_plus_one` (High): Potential N+1 queries
  - `missing_type_hints` (Low): Missing type hints
  - `fat_controller` (Medium): Large controllers
  - `hard_coded_credentials` (Critical): Hardcoded credentials
  - `god_object` (High): Very large classes
  - `unused_use_statements` (Low): Unused imports
- **Continuous Learning**:
  - Naming conventions analysis (camelCase, snake_case, PascalCase)
  - Code style analysis (type hints, doc blocks, early returns)
  - Architecture patterns detection (Service Layer, Repository, DTO)
  - Common practices analysis (Form Requests, logging, testing)
  - Commit history analysis (frequency, patterns, team style)
- **Pattern Storage**: `.php-boost/learned-patterns.json`

#### Tests
- 14 new tests for AI features
- 38 new assertions
- 100% coverage of AI features
- Pattern detection tests
- Machine learning tests
- Guideline export tests

#### Documentation
- **docs/GUIA_COMPLETO.md** (849 lines) - Complete guide in Portuguese
- **docs/PHASE5_AI.md** (240 lines) - Phase 5 technical documentation
- **docs/PHASE5_IMPLEMENTATION.md** (213 lines) - Implementation summary
- **README_DOCS.md** (494 lines) - Documentation index
- **docs/INDEX.md** (436 lines) - Index and statistics
- Total: 2,534 lines of Portuguese documentation

### 📊 Phase 1, 2 & 3: Implemented Features

#### Added
- **Guideline Validation** - `boost:validate`
  - Strict types validation
  - PSR compliance (PSR-1, PSR-12)
  - Type safety checks
  - Security validation
  - Database best practices
  - CI/CD mode with threshold
  - JSON output
- **Project Health Score** - `boost:health`
  - Score based on 5 categories
  - Code Quality (30%)
  - Testing (25%)
  - Security (20%)
  - Performance (15%)
  - Maintainability (10%)
  - Score history (`.php-boost/health-history.json`)
  - Automatic recommendations
- **Migration Path Generator** - `boost:migrate-guide`
  - Migration guides Laravel 8→9→10→11
  - Migration guides Lumen 8→9→10
  - Breaking changes detection
  - Effort estimation
  - Reference resources
- **Advanced MCP Tools**:
  - `Tinker`: PHP REPL in application context
  - `CacheManager`: Manage cache (clear, get, set, forget)
  - `QueueStatus`: Queue and job status
  - `ApplicationInfo`: Detailed application information
  - `ListArtisanCommands`: List available Artisan commands
- **Code Snippet Library** - `boost:snippet`
  - Controller, Resource Controller
  - Model (with optional factory)
  - Service, Repository
  - Request, Resource
  - Migration, Test
- **Performance Profiling** - `boost:profile`
  - N+1 queries detection
  - Slow queries identification
  - Missing eager loading
  - Cache opportunities
  - Memory usage analysis
  - Laravel Telescope integration
- **Documentation Generator** - `boost:docs`
  - OpenAPI/Swagger generation
  - Database schema documentation
  - Architecture diagrams (Mermaid)
  - Deployment guides
  - Onboarding guides

#### Services Added
- `CodeAnalyzer`: Static code analysis
- `GuidelineValidator`: Validation against guidelines
- `ProjectHealthScorer`: Health score calculation
- `MigrationPathGenerator`: Migration guide generation
- `SnippetGenerator`: Boilerplate code generation
- `PerformanceProfiler`: Performance analysis
- `DocumentationGenerator`: Documentation generation
- `AI/CodePatternDetector`: AI pattern detection
- `AI/GuidelineRecommender`: Guideline recommendation
- `AI/PatternLearningSystem`: Machine learning

#### Commands Added
- `boost:install` - Installation and guideline generation
- `boost:validate` - Code validation
- `boost:health` - Project health score
- `boost:migrate-guide` - Migration guides
- `boost:snippet` - Snippet generation
- `boost:profile` - Performance profiling
- `boost:docs` - Documentation generation
- `boost:analyze` - AI-powered analysis

### Enhanced
- `GuidelineGenerator`: Improved CLAUDE.md and AGENTS.md generation
- `GuidelineWriter`: Backup and merge system
- `LocaleManager`: Support for 5 languages (en, pt-BR, es, fr, de)
- `GitIntegration`: Auto-commit, .gitattributes, hooks
- Team Sync: Configuration export/import, webhooks

### Tests
- Total: 127 tests
- Total: 399 assertions
- Coverage: ~85%
- Unit tests for all services
- Integration tests for commands

---

## [1.0.0] - 2026-02-09

### Added - Initial Release
- **Core MCP Protocol** implementation (JSON-RPC 2.0)
- **STDIO transport** for MCP communication
- **Tool registry system** for custom tools
- **Core MCP Tools**:
  - `GetConfig`: Read configuration values
  - `DatabaseSchema`: View database schema
  - `DatabaseQuery`: Execute read-only SQL queries
  - `ReadLogEntries`: Read application logs
  - `ListRoutes`: List Laravel/Lumen routes
  - `ProjectInspector`: Inspect project structure
- **Laravel 8+ adapter** with ServiceProvider
- **Lumen 8+ adapter** with ServiceProvider
- **Standalone PHP bootstrap** for framework-agnostic usage
- **CLI command** `boost-server` for standalone execution
- **PHP 7.4+ compatibility** with polyfills
- **PSR-4 autoloading**
- **Unit tests** for core functionality

### Features
- Framework-agnostic core
- Support for Laravel 8+, 9, 10, 11
- Support for Lumen 8+, 9, 10
- Standalone PHP support
- Easy custom tool registration
- Environment variable configuration
- PDO-based database tools (MySQL, PostgreSQL, SQLite, SQL Server, Oracle)
- Read-only query validation
- JSON Schema input validation
- Auto-update via Composer hooks
- Package templates (22+ templates)
- Interactive CLI mode
- Custom templates system
- Version upgrade detector
- Multi-language support (5 languages)
- Git integration
- Team sync features

### Documentation
- README.md with getting started guide
- AGENTS.md for development guidelines
- FEATURES.md with roadmap
- CUSTOM_TEMPLATES.md for template creation

---

## Version History Summary

| Version | Date | Features | Changes |
|---------|------|----------|---------|
| 1.1.0 | 2026-02-10 | **Phase 5: AI** + Phases 1-3 | +9 commands, +9 services, +14 AI tests |
| 1.0.0 | 2026-02-09 | Core MCP + Basic Tools | Initial release |

---

## Statistics

### Version 1.1.0
- **Commands**: 9 Artisan commands
- **Services**: 15+ core services
- **MCP Tools**: 11 tools
- **Tests**: 127 tests (399 assertions)
- **Documentation**: 2,534 lines (Portuguese)
- **AI Patterns**: 8 patterns detected
- **Languages**: 5 languages supported
- **PHP**: 7.4+ compatible

### Total Code
- **Source Code**: ~6,500 lines
- **Tests**: ~3,200 lines
- **Documentation**: ~2,534 lines
- **Total**: ~12,234 lines

---

## Links

- **GitHub**: https://github.com/felipereisdev/php-boost
- **Documentation**: [docs/GUIA_COMPLETO.md](docs/GUIA_COMPLETO.md)
- **AI Features**: [docs/PHASE5_AI.md](docs/PHASE5_AI.md)
- **Issues**: https://github.com/felipereisdev/php-boost/issues

---

**Maintained by**: Felipe Reis  
**License**: MIT
