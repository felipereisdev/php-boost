<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\PerformanceProfiler;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostProfile extends AbstractTool
{
    public function getName()
    {
        return 'BoostProfile';
    }

    public function getDescription()
    {
        return 'Analyze performance and detect issues (MCP equivalent of boost:profile)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'format' => ['type' => 'string', 'enum' => ['json', 'table'], 'default' => 'json'],
                'category' => ['type' => 'string'],
                'min_severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                'export' => ['type' => 'string'],
                'base_path' => ['type' => 'string'],
            ],
        ];
    }

    public function isReadOnly()
    {
        return false;
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);

        try {
            $rootPath = isset($arguments['base_path']) ? $arguments['base_path'] : getcwd();
            $profiler = new PerformanceProfiler($rootPath);
            $report = $profiler->analyze();

            $report = $this->applyFilters($report, $arguments);

            $written = [];
            if (!empty($arguments['export'])) {
                $outputPath = $this->resolveOutputPath($rootPath, $arguments['export']);
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($outputPath, json_encode($report, JSON_PRETTY_PRINT));
                $written[] = $outputPath;
            }

            $data = $report;
            if (!empty($written)) {
                $data['written_files'] = $written;
            }

            return ToolResult::success(
                $this->getName(),
                'Performance profile generated',
                $data,
                [
                    'base_path' => $rootPath,
                    'writes_performed' => !empty($written),
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                ]
            );
        } catch (\Exception $e) {
            return ToolResult::error(
                $this->getName(),
                'Performance profile failed',
                [],
                ['base_path' => isset($arguments['base_path']) ? $arguments['base_path'] : getcwd()],
                [],
                [['message' => $e->getMessage()]]
            );
        }
    }

    private function applyFilters(array $report, array $arguments)
    {
        $category = isset($arguments['category']) ? $arguments['category'] : null;
        $minSeverity = isset($arguments['min_severity']) ? $arguments['min_severity'] : null;

        if ($category && isset($report['categories'])) {
            $report['categories'] = array_filter(
                $report['categories'],
                function ($key) use ($category) {
                    return $key === $category;
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        if ($minSeverity && isset($report['categories'])) {
            $levels = ['low' => 1, 'medium' => 2, 'high' => 3];
            $minLevel = isset($levels[$minSeverity]) ? $levels[$minSeverity] : 1;

            foreach ($report['categories'] as $cat => $payload) {
                if (!isset($payload['issues']) || !is_array($payload['issues'])) {
                    continue;
                }

                $issues = array_values(array_filter($payload['issues'], function ($issue) use ($levels, $minLevel) {
                    $severity = isset($issue['severity']) ? $issue['severity'] : 'low';
                    $level = isset($levels[$severity]) ? $levels[$severity] : 1;
                    return $level >= $minLevel;
                }));

                $report['categories'][$cat]['issues'] = $issues;
                $report['categories'][$cat]['count'] = count($issues);
            }
        }

        return $report;
    }

    private function resolveOutputPath($basePath, $path)
    {
        if (strpos($path, '/') === 0) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}
