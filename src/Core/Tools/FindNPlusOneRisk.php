<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\StaticAnalysisService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class FindNPlusOneRisk extends AbstractTool
{
    public function getName()
    {
        return 'FindNPlusOneRisk';
    }

    public function getDescription()
    {
        return 'Scan controllers/resources for loop + lazy relation access risks';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'base_path' => ['type' => 'string'],
                'paths' => ['type' => 'array', 'items' => ['type' => 'string']],
                'severity_threshold' => ['type' => 'number', 'default' => 0.6],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new StaticAnalysisService();
        $base = $this->resolveBasePath($arguments);
        $paths = isset($arguments['paths']) && is_array($arguments['paths'])
            ? $arguments['paths']
            : [$base . '/app/Http/Controllers', $base . '/app/Http/Resources'];

        $files = $service->listPhpFiles($paths);
        $risks = $service->findNPlusOneRisks($files);

        $threshold = isset($arguments['severity_threshold']) ? (float) $arguments['severity_threshold'] : 0.6;
        $risks = array_values(array_filter($risks, function ($risk) use ($threshold) {
            return $risk['confidence'] >= $threshold;
        }));

        $meta = ['paths' => $paths, 'analysis_mode' => 'static-heuristic'];
        if (!empty($risks)) {
            return ToolResult::warning(
                $this->getName(),
                'N+1 risk scan completed with findings',
                ['risks' => $risks, 'count' => count($risks)],
                $meta
            );
        }

        return ToolResult::success($this->getName(), 'N+1 risk scan completed', ['risks' => [], 'count' => 0], $meta);
    }
}
