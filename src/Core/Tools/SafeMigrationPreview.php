<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\MigrationImpactAnalyzerService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class SafeMigrationPreview extends AbstractTool
{
    public function getName()
    {
        return 'SafeMigrationPreview';
    }

    public function getDescription()
    {
        return 'Analyze pending migrations and report potential lock/rewrite/drop risks';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string'],
                'connection' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new MigrationImpactAnalyzerService();
        $basePath = $this->resolveBasePath($arguments, ['path', 'base_path']);
        $pending = $service->pendingMigrations($basePath);
        $impacts = $service->previewImpacts($pending);

        $status = empty($impacts) ? 'warning' : 'ok';
        $summary = empty($impacts) ? 'No pending migration impacts found' : 'Migration preview generated';
        $riskScore = $service->riskScore($service->toDrift([], $pending));
        $data = ['impacts' => $impacts, 'risk_score' => $riskScore];

        if ($status === 'warning') {
            return ToolResult::warning($this->getName(), $summary, $data);
        }

        if ($riskScore >= 45) {
            return ToolResult::warning($this->getName(), 'Migration preview generated with elevated risk', $data);
        }

        return ToolResult::success($this->getName(), $summary, $data);
    }
}
