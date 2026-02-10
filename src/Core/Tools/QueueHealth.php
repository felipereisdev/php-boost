<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\QueueTelemetryService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class QueueHealth extends AbstractTool
{
    public function getName()
    {
        return 'QueueHealth';
    }

    public function getDescription()
    {
        return 'Summarize queue health (pending/failed/retries/workers/lag) with current coverage';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'queue' => ['type' => 'string'],
                'window_minutes' => ['type' => 'integer', 'default' => 60],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $service = new QueueTelemetryService($this->config);
        $result = $service->collect($arguments);
        $queue = isset($arguments['queue']) ? $arguments['queue'] : null;
        $window = isset($arguments['window_minutes']) ? (int) $arguments['window_minutes'] : 60;

        $summary = empty($result['alerts']) ? 'Queue telemetry collected' : 'Queue telemetry collected with limitations';

        if (!empty($result['alerts'])) {
            return ToolResult::warning(
                $this->getName(),
                $summary,
                $result,
                ['queue' => $queue, 'window_minutes' => $window]
            );
        }

        return ToolResult::success(
            $this->getName(),
            $summary,
            $result,
            ['queue' => $queue, 'window_minutes' => $window]
        );
    }
}
