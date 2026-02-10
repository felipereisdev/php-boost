<?php

namespace FelipeReisDev\PhpBoost\Laravel\Tools;

use FelipeReisDev\PhpBoost\Core\Tools\AbstractTool;
use Illuminate\Support\Facades\File;

class ApplicationInfo extends AbstractTool
{
    public function getName()
    {
        return 'ApplicationInfo';
    }

    public function getDescription()
    {
        return 'Get detailed information about the Laravel application (models, controllers, routes, providers)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'section' => [
                    'type' => 'string',
                    'enum' => ['all', 'framework', 'environment', 'models', 'controllers', 'providers', 'routes', 'dependencies'],
                    'description' => 'Section of information to retrieve',
                    'default' => 'all',
                ],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $section = $arguments['section'] ?? 'all';

        try {
            $info = [];

            if ($section === 'all' || $section === 'framework') {
                $info['framework'] = $this->getFrameworkInfo();
            }

            if ($section === 'all' || $section === 'environment') {
                $info['environment'] = $this->getEnvironmentInfo();
            }

            if ($section === 'all' || $section === 'models') {
                $info['models'] = $this->getModels();
            }

            if ($section === 'all' || $section === 'controllers') {
                $info['controllers'] = $this->getControllers();
            }

            if ($section === 'all' || $section === 'providers') {
                $info['providers'] = $this->getProviders();
            }

            if ($section === 'all' || $section === 'routes') {
                $info['routes'] = $this->getRoutesCount();
            }

            if ($section === 'all' || $section === 'dependencies') {
                $info['dependencies'] = $this->getDependencies();
            }

            return $info;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getFrameworkInfo()
    {
        return [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'debug' => config('app.debug'),
        ];
    }

    private function getEnvironmentInfo()
    {
        return [
            'app_env' => config('app.env'),
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
        ];
    }

    private function getModels()
    {
        $modelsPath = app_path('Models');
        
        if (!File::isDirectory($modelsPath)) {
            $modelsPath = app_path();
        }

        $models = [];
        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            
            if (preg_match('/class\s+(\w+)\s+extends\s+Model/', $content, $matches)) {
                $models[] = [
                    'name' => $matches[1],
                    'path' => str_replace(base_path() . '/', '', $file->getPathname()),
                ];
            }
        }

        return [
            'count' => count($models),
            'list' => $models,
        ];
    }

    private function getControllers()
    {
        $controllersPath = app_path('Http/Controllers');
        
        if (!File::isDirectory($controllersPath)) {
            return [
                'count' => 0,
                'list' => [],
            ];
        }

        $controllers = [];
        $files = File::allFiles($controllersPath);

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            
            if (preg_match('/class\s+(\w+)\s+extends\s+Controller/', $content, $matches)) {
                $controllers[] = [
                    'name' => $matches[1],
                    'path' => str_replace(base_path() . '/', '', $file->getPathname()),
                ];
            }
        }

        return [
            'count' => count($controllers),
            'list' => $controllers,
        ];
    }

    private function getProviders()
    {
        $config = config('app.providers', []);
        
        $providers = [];
        foreach ($config as $provider) {
            if (class_exists($provider)) {
                $providers[] = class_basename($provider);
            }
        }

        return [
            'count' => count($providers),
            'list' => $providers,
        ];
    }

    private function getRoutesCount()
    {
        $routes = app('router')->getRoutes();
        
        $methodsCount = [];
        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                if (!isset($methodsCount[$method])) {
                    $methodsCount[$method] = 0;
                }
                $methodsCount[$method]++;
            }
        }

        return [
            'total' => count($routes),
            'by_method' => $methodsCount,
        ];
    }

    private function getDependencies()
    {
        $composerPath = base_path('composer.json');
        
        if (!File::exists($composerPath)) {
            return [
                'require' => [],
                'require-dev' => [],
            ];
        }

        $composer = json_decode(File::get($composerPath), true);
        
        return [
            'require' => $composer['require'] ?? [],
            'require-dev' => $composer['require-dev'] ?? [],
        ];
    }
}
