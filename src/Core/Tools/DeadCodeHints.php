<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\StaticAnalysisService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class DeadCodeHints extends AbstractTool
{
    public function getName()
    {
        return 'DeadCodeHints';
    }

    public function getDescription()
    {
        return 'Find classes/commands/routes with low reference signals';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'base_path' => ['type' => 'string'],
                'paths' => ['type' => 'array', 'items' => ['type' => 'string']],
                'min_confidence' => ['type' => 'number', 'default' => 0.6],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new StaticAnalysisService();
        $basePath = $this->resolveBasePath($arguments);

        $paths = isset($arguments['paths']) && is_array($arguments['paths'])
            ? $arguments['paths']
            : [$basePath . '/app', $basePath . '/src'];

        $files = $service->listPhpFiles($paths);
        $hints = $service->deadCodeHints($files);

        $minConfidence = isset($arguments['min_confidence']) ? (float) $arguments['min_confidence'] : 0.6;
        $hints = array_values(array_filter($hints, function ($item) use ($minConfidence) {
            return $item['confidence'] >= $minConfidence;
        }));

        if (!empty($hints)) {
            return ToolResult::warning(
                $this->getName(),
                'Dead code hint scan completed with findings',
                ['hints' => $hints, 'count' => count($hints)],
                ['paths' => $paths]
            );
        }

        return ToolResult::success($this->getName(), 'Dead code hint scan completed', ['hints' => [], 'count' => 0], ['paths' => $paths]);
    }
}
