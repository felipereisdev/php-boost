<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\DatabaseIntrospectorService;
use FelipeReisDev\PhpBoost\Core\Services\MigrationImpactAnalyzerService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class SchemaDiff extends AbstractTool
{
    public function getName()
    {
        return 'SchemaDiff';
    }

    public function getDescription()
    {
        return 'Compare current schema with expected state inferred from pending migrations';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'connection' => ['type' => 'string'],
                'include_indexes' => ['type' => 'boolean', 'default' => true],
                'include_constraints' => ['type' => 'boolean', 'default' => true],
                'pending_only' => ['type' => 'boolean', 'default' => true],
                'base_path' => ['type' => 'string', 'description' => 'Project base path'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);
        $db = new DatabaseIntrospectorService($this->config);
        $migrationAnalyzer = new MigrationImpactAnalyzerService();

        $basePath = $this->resolveBasePath($arguments);
        $snapshot = $db->getCurrentSchemaSnapshot();
        $pending = $migrationAnalyzer->pendingMigrations($basePath);
        $drift = $migrationAnalyzer->toDrift($snapshot, $pending, $arguments);

        $riskScore = $migrationAnalyzer->riskScore($drift);

        $meta = [
            'duration_ms' => round((microtime(true) - $start) * 1000),
            'driver' => $db->getDriver(),
            'limitations' => ['Migration parser is heuristic and may miss dynamic migration logic'],
        ];

        $data = [
            'drift' => $drift,
            'pending_migrations' => array_map(function ($migration) {
                return [
                    'name' => $migration['name'],
                    'path' => $migration['path'],
                    'operations' => $migration['analysis']['operations'],
                    'risks' => $migration['analysis']['risks'],
                ];
            }, $pending),
            'risk_score' => $riskScore,
        ];

        if (empty($pending)) {
            return ToolResult::warning($this->getName(), 'No pending migrations found for analysis', $data, $meta);
        }

        return ToolResult::success($this->getName(), 'Schema drift analysis completed', $data, $meta);
    }
}
