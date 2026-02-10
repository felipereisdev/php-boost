<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\CodeAnalyzer;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineValidator;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostValidate extends AbstractTool
{
    public function getName()
    {
        return 'BoostValidate';
    }

    public function getDescription()
    {
        return 'Validate code against guidelines (MCP equivalent of boost:validate)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'format' => ['type' => 'string', 'enum' => ['json', 'text'], 'default' => 'json'],
                'ci' => ['type' => 'boolean', 'default' => false],
                'threshold' => ['type' => 'integer', 'default' => 70],
                'base_path' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);
        $rootPath = $this->resolveBasePath($arguments);

        try {
            $composerPath = rtrim($rootPath, '/') . '/composer.json';
            $projectInfo = $this->inspectProject($rootPath, $composerPath);

            $validator = new GuidelineValidator($rootPath, $projectInfo);
            $results = $validator->validate();

            $analyzer = new CodeAnalyzer($rootPath, $projectInfo);
            $results['code_quality'] = $analyzer->analyze();

            $ci = isset($arguments['ci']) ? (bool) $arguments['ci'] : false;
            $threshold = isset($arguments['threshold']) ? (int) $arguments['threshold'] : 70;
            $exitCode = ($ci && (int) $results['score'] < $threshold) ? 1 : 0;
            $results['exit_code'] = $exitCode;

            $status = $exitCode === 1 ? 'warning' : 'ok';
            $summary = $exitCode === 1
                ? 'Validation completed: score below CI threshold'
                : 'Validation completed successfully';

            $meta = [
                'base_path' => $rootPath,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'ci' => $ci,
                'threshold' => $threshold,
                'writes_performed' => false,
            ];

            if ($status === 'warning') {
                return ToolResult::warning($this->getName(), $summary, $results, $meta);
            }

            return ToolResult::success($this->getName(), $summary, $results, $meta);
        } catch (\Exception $e) {
            return ToolResult::error(
                $this->getName(),
                'Validation failed',
                [],
                ['base_path' => $rootPath],
                [],
                [['message' => $e->getMessage()]]
            );
        }
    }

    private function inspectProject($rootPath, $composerPath)
    {
        $inspector = new ProjectInspector($rootPath, $composerPath);
        return $inspector->inspect();
    }
}
