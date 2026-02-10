<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\EloquentModelMapService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class ModelRelations extends AbstractTool
{
    public function getName()
    {
        return 'ModelRelations';
    }

    public function getDescription()
    {
        return 'Map model relations (belongsTo, hasMany, etc.)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'model' => ['type' => 'string'],
                'depth' => ['type' => 'integer', 'default' => 1],
                'path' => ['type' => 'string'],
                'namespace_root' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new EloquentModelMapService();
        $basePath = $this->resolveBasePath($arguments, ['path', 'base_path']);
        $filter = isset($arguments['model']) ? $arguments['model'] : null;
        $namespaceRoot = isset($arguments['namespace_root']) ? $arguments['namespace_root'] : null;

        $relations = $service->listRelations($basePath, $filter, $namespaceRoot);

        return ToolResult::success(
            $this->getName(),
            'Relations mapped',
            ['relations' => $relations, 'count' => count($relations)],
            [
                'base_path' => $basePath,
                'depth' => isset($arguments['depth']) ? (int) $arguments['depth'] : 1,
                'namespace_root' => $namespaceRoot,
            ]
        );
    }
}
