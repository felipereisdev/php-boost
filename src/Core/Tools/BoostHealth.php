<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\ProjectHealthScorer;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostHealth extends AbstractTool
{
    public function getName()
    {
        return 'BoostHealth';
    }

    public function getDescription()
    {
        return 'Calculate project health score (MCP equivalent of boost:health)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'format' => ['type' => 'string', 'enum' => ['json', 'text'], 'default' => 'json'],
                'save' => ['type' => 'boolean', 'default' => false],
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
            $composerPath = rtrim($rootPath, '/') . '/composer.json';
            $projectInfo = $this->inspectProject($rootPath, $composerPath);

            $scorer = new ProjectHealthScorer($rootPath, $projectInfo);
            $healthScore = $scorer->calculateScore();

            $written = [];
            if (!empty($arguments['save'])) {
                $scorer->saveScore($healthScore);
                $written[] = rtrim($rootPath, '/') . '/.php-boost/health-history.json';
            }

            $data = $healthScore;
            if (!empty($written)) {
                $data['written_files'] = $written;
            }

            return ToolResult::success(
                $this->getName(),
                'Health score calculated successfully',
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
                'Health score calculation failed',
                [],
                ['base_path' => isset($arguments['base_path']) ? $arguments['base_path'] : getcwd()],
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
