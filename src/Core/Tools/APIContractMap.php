<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\ApiContractService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class APIContractMap extends AbstractTool
{
    public function getName()
    {
        return 'APIContractMap';
    }

    public function getDescription()
    {
        return 'Map API contracts from routes + requests + resources, with Orion fallback';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'route_prefix' => ['type' => 'string'],
                'format' => ['type' => 'string', 'enum' => ['json'], 'default' => 'json'],
                'base_path' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new ApiContractService();
        $basePath = $this->resolveBasePath($arguments);
        $routePrefix = isset($arguments['route_prefix']) ? $arguments['route_prefix'] : null;

        $endpoints = $service->map($basePath, $routePrefix);
        $orionDetected = class_exists('Orion\\OrionServiceProvider') || is_dir($basePath . '/vendor/tailflow/laravel-orion');

        $meta = [
            'orion_detected' => $orionDetected,
            'fallback_mode' => !$orionDetected,
            'route_prefix' => $routePrefix,
        ];

        $summary = $orionDetected
            ? 'API contract map generated with Orion context'
            : 'API contract map generated in fallback mode (without Orion)';

        if (!$orionDetected) {
            return ToolResult::warning($this->getName(), $summary, ['endpoints' => $endpoints], $meta);
        }

        return ToolResult::success($this->getName(), $summary, ['endpoints' => $endpoints], $meta);
    }
}
