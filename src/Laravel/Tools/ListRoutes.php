<?php

namespace FelipeReisDev\PhpBoost\Laravel\Tools;

use FelipeReisDev\PhpBoost\Core\Tools\AbstractTool;
use Illuminate\Support\Facades\Route;

class ListRoutes extends AbstractTool
{
    public function getName()
    {
        return 'ListRoutes';
    }

    public function getDescription()
    {
        return 'List all registered routes in the Laravel application';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'method' => [
                    'type' => 'string',
                    'description' => 'Filter by HTTP method (GET, POST, etc.)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Filter by route name',
                ],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $methodFilter = $arguments['method'] ?? null;
        $nameFilter = $arguments['name'] ?? null;

        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $methods = $route->methods();
            $name = $route->getName();
            $uri = $route->uri();
            $action = $route->getActionName();

            if ($methodFilter && !in_array(strtoupper($methodFilter), $methods)) {
                continue;
            }

            if ($nameFilter && (!$name || strpos($name, $nameFilter) === false)) {
                continue;
            }

            $routes[] = [
                'methods' => $methods,
                'uri' => $uri,
                'name' => $name,
                'action' => $action,
            ];
        }

        return [
            'routes' => $routes,
            'count' => count($routes),
        ];
    }
}
