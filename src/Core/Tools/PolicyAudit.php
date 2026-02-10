<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\StaticAnalysisService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class PolicyAudit extends AbstractTool
{
    public function getName()
    {
        return 'PolicyAudit';
    }

    public function getDescription()
    {
        return 'Build endpoint -> policy/gate matrix and highlight missing explicit protection';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'route_prefix' => ['type' => 'string'],
                'include_implicit' => ['type' => 'boolean', 'default' => false],
                'base_path' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new StaticAnalysisService();
        $basePath = $this->resolveBasePath($arguments);
        $matrix = $service->policyMatrix($basePath, isset($arguments['route_prefix']) ? $arguments['route_prefix'] : null);
        $unprotected = array_values(array_filter($matrix, function ($item) {
            return $item['status'] !== 'protected';
        }));

        $data = [
            'matrix' => $matrix,
            'unprotected' => $unprotected,
        ];

        $summary = 'Policy audit completed';
        if (!empty($unprotected)) {
            return ToolResult::warning($this->getName(), $summary, $data, ['base_path' => $basePath]);
        }

        return ToolResult::success($this->getName(), $summary, $data, ['base_path' => $basePath]);
    }
}
