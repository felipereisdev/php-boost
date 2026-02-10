<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

class ProjectInspector extends AbstractTool
{
    private $basePath;
    private $composerPath;

    public function __construct($basePathOrConfig = null, $composerPath = null, array $config = [])
    {
        if (is_array($basePathOrConfig)) {
            $config = $basePathOrConfig;
            $basePath = $config['base_path'] ?? getcwd();
            $composerPath = $config['composer_path'] ?? null;
        } else {
            $basePath = $basePathOrConfig ?: getcwd();
        }

        parent::__construct($config);

        $this->basePath = rtrim($basePath, '/');
        $this->composerPath = $composerPath ?: $this->basePath . '/composer.json';
    }

    public function getName()
    {
        return 'ProjectInspector';
    }

    public function getDescription()
    {
        return 'Inspects project structure, framework, dependencies, and environment';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'full' => [
                    'type' => 'boolean',
                    'description' => 'Include full detailed inspection',
                    'default' => true,
                ],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $full = $arguments['full'] ?? true;

        return [
            'framework' => $this->detectFramework(),
            'php' => $this->detectPhpVersion(),
            'packages' => $this->detectPackages(),
            'database' => $this->detectDatabase(),
            'environment' => $this->detectEnvironment(),
            'tests' => $this->detectTests(),
            'structure' => $full ? $this->getProjectStructure() : null,
            'env_vars' => $full ? $this->getCommonEnvVars() : null,
        ];
    }

    public function inspect()
    {
        $composer = $this->readComposer();
        $framework = $this->detectFramework();
        $database = $this->detectDatabase();
        $environment = $this->detectEnvironment();
        $tests = $this->detectTests();
        $structure = $this->getProjectStructure();

        $packageMap = [];
        foreach ($this->detectPackages() as $package) {
            if (isset($package['name'], $package['version'])) {
                $packageMap[$package['name']] = $package['version'];
            }
        }

        $structureList = [];
        foreach ($structure as $dir => $children) {
            $structureList[] = $dir . '/';
        }

        return [
            'name' => $composer['name'] ?? basename($this->basePath),
            'framework' => [
                'name' => $this->formatFrameworkName($framework['name'] ?? 'standalone'),
                'version' => $framework['version'] ?: 'N/A',
            ],
            'php' => $this->detectPhpVersion(),
            'packages' => $packageMap,
            'database' => $database['driver'] ?? null,
            'environment' => $this->normalizeEnvironment($environment),
            'tests' => [
                'framework' => $this->formatTestFramework($tests['framework'] ?? null),
                'count' => $tests['count'] ?? 0,
                'has_tests' => $tests['has_tests'] ?? false,
            ],
            'structure' => $structureList,
            'env_vars' => $this->getCommonEnvVars(),
        ];
    }

    public function detectFramework()
    {
        $composerPath = $this->composerPath;
        
        if (!file_exists($composerPath)) {
            return ['name' => 'standalone', 'version' => null];
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        $require = $composer['require'] ?? [];

        if (isset($require['laravel/framework'])) {
            $version = $this->parseVersion($require['laravel/framework']);
            return ['name' => 'laravel', 'version' => $version];
        }

        if (isset($require['laravel/lumen-framework'])) {
            $version = $this->parseVersion($require['laravel/lumen-framework']);
            return ['name' => 'lumen', 'version' => $version];
        }

        return ['name' => 'standalone', 'version' => null];
    }

    public function detectPhpVersion()
    {
        $composerPath = $this->composerPath;
        $runtimeVersion = PHP_VERSION;
        $constraint = null;

        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $constraint = $composer['require']['php'] ?? null;
        }

        return [
            'runtime' => $runtimeVersion,
            'constraint' => $constraint,
            'major_minor' => substr($runtimeVersion, 0, 3),
        ];
    }

    public function detectPackages()
    {
        $composerPath = $this->composerPath;
        
        if (!file_exists($composerPath)) {
            return [];
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        $require = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        $packages = [];
        foreach ($require as $name => $version) {
            if ($name === 'php' || str_starts_with($name, 'ext-')) {
                continue;
            }

            $packages[] = [
                'name' => $name,
                'version' => $version,
                'major_version' => $this->getMajorVersion($version),
            ];
        }

        return $packages;
    }

    public function detectDatabase()
    {
        $envPath = $this->basePath . '/.env';
        
        if (!file_exists($envPath)) {
            return ['driver' => null];
        }

        $envContent = file_get_contents($envPath);
        $driver = null;

        if (preg_match('/DB_CONNECTION=([^\s]+)/', $envContent, $matches)) {
            $driver = trim($matches[1]);
        }

        return ['driver' => $driver];
    }

    public function detectEnvironment()
    {
        $env = [
            'herd' => false,
            'sail' => false,
            'docker' => false,
        ];

        $envPath = $this->basePath . '/.env';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            if (preg_match('/APP_URL=.*\.test/', $envContent)) {
                $env['herd'] = true;
            }
        }

        if (file_exists($this->basePath . '/vendor/bin/sail')) {
            $env['sail'] = true;
        }

        if (file_exists($this->basePath . '/docker-compose.yml')) {
            $env['docker'] = true;
        }

        return $env;
    }

    public function detectTests()
    {
        $phpunitPath = $this->basePath . '/vendor/bin/phpunit';
        $pestPath = $this->basePath . '/vendor/bin/pest';
        
        $framework = null;
        $count = 0;

        if (file_exists($pestPath)) {
            $framework = 'pest';
        } elseif (file_exists($phpunitPath)) {
            $framework = 'phpunit';
        }

        if ($framework && file_exists($this->basePath . '/tests')) {
            $count = $this->countTestFiles($this->basePath . '/tests');
        }

        return [
            'framework' => $framework,
            'count' => $count,
            'has_tests' => $count > 0,
        ];
    }

    public function getProjectStructure()
    {
        $structure = [];
        $importantDirs = [
            'app',
            'src',
            'database',
            'tests',
            'routes',
            'config',
            'resources',
        ];

        foreach ($importantDirs as $dir) {
            $fullPath = $this->basePath . '/' . $dir;
            if (is_dir($fullPath)) {
                $structure[$dir] = $this->scanDirectory($fullPath, 2);
            }
        }

        return $structure;
    }

    public function getCommonEnvVars()
    {
        $envExamplePath = $this->basePath . '/.env.example';
        
        if (!file_exists($envExamplePath)) {
            return [];
        }

        $content = file_get_contents($envExamplePath);
        $lines = explode("\n", $content);
        $vars = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $vars[] = trim($key);
            }
        }

        return $vars;
    }

    private function parseVersion($versionString)
    {
        preg_match('/\d+\.\d+/', $versionString, $matches);
        return $matches[0] ?? null;
    }

    private function getMajorVersion($versionString)
    {
        preg_match('/\d+/', $versionString, $matches);
        return $matches[0] ?? null;
    }

    private function countTestFiles($directory)
    {
        $count = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                $count++;
            }
        }

        return $count;
    }

    private function scanDirectory($path, $depth = 1, $currentDepth = 0)
    {
        if ($currentDepth >= $depth) {
            return [];
        }

        $items = [];
        
        if (!is_dir($path)) {
            return $items;
        }

        $iterator = new \DirectoryIterator($path);
        
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isDir()) {
                $items[] = $item->getFilename() . '/';
            }
        }

        return $items;
    }

    private function readComposer()
    {
        if (!file_exists($this->composerPath)) {
            return [];
        }

        $content = file_get_contents($this->composerPath);
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function formatFrameworkName($framework)
    {
        $framework = strtolower((string) $framework);

        if ($framework === 'laravel') {
            return 'Laravel';
        }

        if ($framework === 'lumen') {
            return 'Lumen';
        }

        return 'Standalone';
    }

    private function normalizeEnvironment(array $environment)
    {
        if (!empty($environment['herd'])) {
            return 'herd';
        }

        if (!empty($environment['sail'])) {
            return 'sail';
        }

        if (!empty($environment['docker'])) {
            return 'docker';
        }

        return null;
    }

    private function formatTestFramework($framework)
    {
        if ($framework === 'pest') {
            return 'Pest';
        }

        if ($framework === 'phpunit') {
            return 'PHPUnit';
        }

        return null;
    }
}
