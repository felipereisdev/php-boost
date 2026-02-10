<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\Node;

class DocumentationGenerator
{
    private $rootPath;
    private $parser;
    private $projectInfo;

    public function __construct($rootPath, array $projectInfo = [])
    {
        $this->rootPath = $rootPath;
        $this->projectInfo = $projectInfo;
        $factory = new ParserFactory();
        $this->parser = $factory->createForHostVersion();
    }

    public function generateOpenApi()
    {
        $routes = $this->extractRoutes();
        $controllers = $this->extractControllers();
        
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $this->projectInfo['name'] ?? 'API Documentation',
                'version' => '1.0.0',
                'description' => 'Auto-generated API documentation',
            ],
            'servers' => [
                ['url' => '/api', 'description' => 'API Server'],
            ],
            'paths' => $this->generatePaths($routes, $controllers),
            'components' => [
                'schemas' => $this->generateSchemas(),
            ],
        ];
        
        return $spec;
    }

    public function generateDatabaseDocs()
    {
        $tables = $this->extractDatabaseTables();
        
        $docs = [
            'database' => $this->projectInfo['database'] ?? 'unknown',
            'tables' => [],
        ];
        
        foreach ($tables as $table) {
            $docs['tables'][] = [
                'name' => $table['name'],
                'columns' => $table['columns'],
                'indexes' => $table['indexes'] ?? [],
                'relationships' => $table['relationships'] ?? [],
            ];
        }
        
        return $docs;
    }

    public function generateArchitectureDocs()
    {
        $structure = [
            'controllers' => $this->countFiles('app/Http/Controllers'),
            'models' => $this->countFiles('app/Models'),
            'services' => $this->countFiles('app/Services'),
            'repositories' => $this->countFiles('app/Repositories'),
            'middleware' => $this->countFiles('app/Http/Middleware'),
            'requests' => $this->countFiles('app/Http/Requests'),
            'resources' => $this->countFiles('app/Http/Resources'),
        ];
        
        $docs = [
            'framework' => $this->projectInfo['framework']['name'] ?? 'Laravel',
            'version' => $this->projectInfo['framework']['version'] ?? 'unknown',
            'structure' => $structure,
            'patterns' => $this->detectArchitecturalPatterns(),
            'layers' => $this->identifyLayers(),
        ];
        
        return $docs;
    }

    public function generateDeploymentGuide()
    {
        $guide = [
            'requirements' => [
                'php' => $this->projectInfo['php']['constraint'] ?? '>=7.4',
                'extensions' => $this->detectPhpExtensions(),
                'database' => $this->projectInfo['database'] ?? 'mysql',
            ],
            'steps' => $this->generateDeploymentSteps(),
            'configuration' => $this->generateConfigurationGuide(),
            'troubleshooting' => $this->generateTroubleshooting(),
        ];
        
        return $guide;
    }

    public function generateOnboardingGuide()
    {
        $guide = [
            'setup' => $this->generateSetupGuide(),
            'structure' => $this->generateStructureOverview(),
            'conventions' => $this->generateConventions(),
            'common_tasks' => $this->generateCommonTasks(),
        ];
        
        return $guide;
    }

    private function extractRoutes()
    {
        $routesFile = $this->rootPath . '/routes/api.php';
        
        if (!file_exists($routesFile)) {
            return [];
        }
        
        $content = file_get_contents($routesFile);
        $routes = [];
        
        if (preg_match_all('/Route::(get|post|put|patch|delete)\s*\(\s*[\'"](.+?)[\'"]\s*,\s*(.+?)\)/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $routes[] = [
                    'method' => strtoupper($match[1]),
                    'path' => $match[2],
                    'action' => $match[3],
                ];
            }
        }
        
        return $routes;
    }

    private function extractControllers()
    {
        $controllersPath = $this->rootPath . '/app/Http/Controllers';
        
        if (!is_dir($controllersPath)) {
            return [];
        }
        
        $controllers = [];
        $files = $this->findPhpFiles($controllersPath);
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            
            try {
                $ast = $this->parser->parse($code);
                $controllers[basename($file, '.php')] = $this->extractMethodsFromAst($ast);
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return $controllers;
    }

    private function extractMethodsFromAst($ast)
    {
        $nodeFinder = new NodeFinder();
        $methods = [];
        
        $classNodes = $nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);
        
        foreach ($classNodes as $class) {
            $methodNodes = $nodeFinder->findInstanceOf([$class], Node\Stmt\ClassMethod::class);
            
            foreach ($methodNodes as $method) {
                if ($method->isPublic()) {
                    $methods[] = [
                        'name' => $method->name->toString(),
                        'parameters' => $this->extractParameters($method),
                    ];
                }
            }
        }
        
        return $methods;
    }

    private function extractParameters($method)
    {
        $parameters = [];
        
        foreach ($method->params as $param) {
            $parameters[] = [
                'name' => $param->var->name,
                'type' => $param->type ? $param->type->toString() : 'mixed',
            ];
        }
        
        return $parameters;
    }

    private function generatePaths($routes, $controllers)
    {
        $paths = [];
        
        foreach ($routes as $route) {
            $path = $route['path'];
            $method = strtolower($route['method']);
            
            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }
            
            $paths[$path][$method] = [
                'summary' => ucfirst($method) . ' ' . $path,
                'responses' => [
                    '200' => ['description' => 'Successful response'],
                    '400' => ['description' => 'Bad request'],
                    '404' => ['description' => 'Not found'],
                    '500' => ['description' => 'Server error'],
                ],
            ];
        }
        
        return $paths;
    }

    private function generateSchemas()
    {
        $modelsPath = $this->rootPath . '/app/Models';
        
        if (!is_dir($modelsPath)) {
            return [];
        }
        
        $schemas = [];
        $files = $this->findPhpFiles($modelsPath);
        
        foreach ($files as $file) {
            $modelName = basename($file, '.php');
            $schemas[$modelName] = [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
            ];
        }
        
        return $schemas;
    }

    private function extractDatabaseTables()
    {
        $migrationsPath = $this->rootPath . '/database/migrations';
        
        if (!is_dir($migrationsPath)) {
            return [];
        }
        
        $tables = [];
        $files = scandir($migrationsPath);
        
        foreach ($files as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            
            $content = file_get_contents($migrationsPath . '/' . $file);
            
            if (preg_match('/Schema::create\\([\'"](.+?)[\'"]/', $content, $matches)) {
                $tableName = $matches[1];
                $columns = $this->extractColumnsFromMigration($content);
                
                $tables[] = [
                    'name' => $tableName,
                    'columns' => $columns,
                ];
            }
        }
        
        return $tables;
    }

    private function extractColumnsFromMigration($content)
    {
        $columns = [];
        
        if (preg_match_all('/\$table->(\w+)\([\'"]?(\w+)?[\'"]?\)/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $match[1];
                $name = $match[2] ?? $type;
                
                if (!in_array($type, ['timestamps', 'softDeletes', 'rememberToken'])) {
                    $columns[] = [
                        'name' => $name,
                        'type' => $type,
                    ];
                }
            }
        }
        
        return $columns;
    }

    private function countFiles($relativePath)
    {
        $fullPath = $this->rootPath . '/' . $relativePath;
        
        if (!is_dir($fullPath)) {
            return 0;
        }
        
        return count($this->findPhpFiles($fullPath));
    }

    private function detectArchitecturalPatterns()
    {
        $patterns = [];
        
        if (is_dir($this->rootPath . '/app/Services')) {
            $patterns[] = 'Service Layer';
        }
        
        if (is_dir($this->rootPath . '/app/Repositories')) {
            $patterns[] = 'Repository Pattern';
        }
        
        if (is_dir($this->rootPath . '/app/Http/Resources')) {
            $patterns[] = 'API Resources';
        }
        
        if (is_dir($this->rootPath . '/app/Actions')) {
            $patterns[] = 'Action Pattern';
        }
        
        return $patterns;
    }

    private function identifyLayers()
    {
        return [
            'presentation' => ['Controllers', 'Views', 'Resources'],
            'application' => ['Services', 'Actions', 'Jobs'],
            'domain' => ['Models', 'Events', 'Rules'],
            'infrastructure' => ['Repositories', 'Providers', 'Database'],
        ];
    }

    private function detectPhpExtensions()
    {
        $extensions = [];
        $composerJson = $this->rootPath . '/composer.json';
        
        if (file_exists($composerJson)) {
            $composer = json_decode(file_get_contents($composerJson), true);
            
            if (isset($composer['require'])) {
                foreach ($composer['require'] as $package => $version) {
                    if (strpos($package, 'ext-') === 0) {
                        $extensions[] = str_replace('ext-', '', $package);
                    }
                }
            }
        }
        
        return $extensions;
    }

    private function generateDeploymentSteps()
    {
        return [
            'Clone repository',
            'Install dependencies: composer install --no-dev --optimize-autoloader',
            'Copy environment: cp .env.example .env',
            'Generate key: php artisan key:generate',
            'Run migrations: php artisan migrate --force',
            'Cache config: php artisan config:cache',
            'Cache routes: php artisan route:cache',
            'Cache views: php artisan view:cache',
            'Link storage: php artisan storage:link',
        ];
    }

    private function generateConfigurationGuide()
    {
        return [
            'APP_ENV' => 'Set to "production"',
            'APP_DEBUG' => 'Set to "false"',
            'APP_URL' => 'Your application URL',
            'DB_*' => 'Database credentials',
            'CACHE_DRIVER' => 'Recommended: redis',
            'QUEUE_CONNECTION' => 'Recommended: redis',
            'SESSION_DRIVER' => 'Recommended: redis',
        ];
    }

    private function generateTroubleshooting()
    {
        return [
            '500 Error' => 'Check storage/logs/laravel.log',
            'Permission Denied' => 'Run: chmod -R 775 storage bootstrap/cache',
            'Cache Issues' => 'Run: php artisan cache:clear',
            'Config Issues' => 'Run: php artisan config:clear',
        ];
    }

    private function generateSetupGuide()
    {
        return [
            'Clone the repository',
            'Run composer install',
            'Copy .env.example to .env',
            'Generate application key',
            'Configure database',
            'Run migrations',
            'Run seeders (if applicable)',
        ];
    }

    private function generateStructureOverview()
    {
        return [
            'app/' => 'Application logic',
            'app/Http/Controllers/' => 'HTTP controllers',
            'app/Models/' => 'Eloquent models',
            'routes/' => 'Route definitions',
            'database/migrations/' => 'Database migrations',
            'resources/views/' => 'Blade templates',
            'tests/' => 'Application tests',
        ];
    }

    private function generateConventions()
    {
        return [
            'PSR-12 coding standard',
            'Naming: PascalCase for classes, camelCase for methods',
            'Controllers should be thin',
            'Use service layer for business logic',
            'Write tests for new features',
        ];
    }

    private function generateCommonTasks()
    {
        return [
            'Create controller' => 'php artisan make:controller NameController',
            'Create model' => 'php artisan make:model Name -m',
            'Run tests' => 'php artisan test',
            'Create migration' => 'php artisan make:migration create_table_name',
        ];
    }

    private function findPhpFiles($directory)
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}
