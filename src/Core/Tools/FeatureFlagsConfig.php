<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\EnvConfigDiffService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class FeatureFlagsConfig extends AbstractTool
{
    public function getName()
    {
        return 'FeatureFlagsConfig';
    }

    public function getDescription()
    {
        return 'List feature flags/sensitive config and detect env vs config divergences';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'environments' => ['type' => 'array', 'items' => ['type' => 'string']],
                'keys_pattern' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new EnvConfigDiffService();
        $result = $service->analyze(
            getcwd(),
            isset($arguments['keys_pattern']) ? $arguments['keys_pattern'] : null,
            isset($arguments['environments']) && is_array($arguments['environments']) ? $arguments['environments'] : []
        );

        $summary = 'Feature/config analysis completed';
        if (!empty($result['sensitive_exposure'])) {
            return ToolResult::warning(
                $this->getName(),
                $summary,
                $result,
                ['sensitive_exposure_count' => count($result['sensitive_exposure'])]
            );
        }

        return ToolResult::success(
            $this->getName(),
            $summary,
            $result,
            ['environments_scanned' => array_keys($result['environments'])]
        );
    }
}
