<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\MigrationPathGenerator;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostMigrateGuide extends AbstractTool
{
    public function getName()
    {
        return 'BoostMigrateGuide';
    }

    public function getDescription()
    {
        return 'Generate migration path between framework versions (MCP equivalent of boost:migrate-guide)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string'],
                'to' => ['type' => 'string'],
                'format' => ['type' => 'string', 'enum' => ['json', 'text'], 'default' => 'json'],
                'base_path' => ['type' => 'string'],
            ],
            'required' => ['from', 'to'],
        ];
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);
        $rootPath = $this->resolveBasePath($arguments);

        try {
            $this->validateArguments($arguments, ['from', 'to']);
            $composerPath = rtrim($rootPath, '/') . '/composer.json';

            $generator = new MigrationPathGenerator($rootPath, $composerPath);
            $migration = $generator->generatePath($arguments['from'], $arguments['to']);

            return ToolResult::success(
                $this->getName(),
                'Migration guide generated successfully',
                $migration,
                [
                    'base_path' => $rootPath,
                    'writes_performed' => false,
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                ]
            );
        } catch (\Exception $e) {
            return ToolResult::error(
                $this->getName(),
                'Migration guide generation failed',
                [],
                ['base_path' => $rootPath],
                [],
                [['message' => $e->getMessage()]]
            );
        }
    }
}
