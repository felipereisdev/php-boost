<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\EloquentModelMapService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class ListModels extends AbstractTool
{
    public function getName()
    {
        return 'ListModels';
    }

    public function getDescription()
    {
        return 'List Eloquent-like models and metadata';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string'],
                'namespace_root' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new EloquentModelMapService();
        $basePath = isset($arguments['path']) ? $arguments['path'] : getcwd();
        $namespaceRoot = isset($arguments['namespace_root']) ? $arguments['namespace_root'] : null;

        $models = $service->listModels($basePath, $namespaceRoot);

        return ToolResult::success(
            $this->getName(),
            'Model map generated',
            ['models' => $models, 'count' => count($models)],
            ['base_path' => $basePath, 'namespace_root' => $namespaceRoot]
        );
    }
}
