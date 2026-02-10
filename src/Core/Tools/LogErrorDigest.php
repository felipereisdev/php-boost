<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\LogFingerprintService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class LogErrorDigest extends AbstractTool
{
    public function getName()
    {
        return 'LogErrorDigest';
    }

    public function getDescription()
    {
        return 'Group log errors by fingerprint, frequency, and first/last occurrence';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string'],
                'window_minutes' => ['type' => 'integer', 'default' => 60],
                'limit' => ['type' => 'integer', 'default' => 1000],
                'group_by' => ['type' => 'string', 'enum' => ['message', 'exception', 'stack'], 'default' => 'exception'],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);
        $service = new LogFingerprintService();

        $path = isset($arguments['path']) ? $arguments['path'] : $this->getConfig('log_path', 'storage/logs/laravel.log');
        $windowMinutes = isset($arguments['window_minutes']) ? (int) $arguments['window_minutes'] : 60;
        $limit = isset($arguments['limit']) ? (int) $arguments['limit'] : 1000;
        $groupBy = isset($arguments['group_by']) ? $arguments['group_by'] : 'exception';

        $groups = $service->digest($path, $windowMinutes, $limit, $groupBy);
        $topErrors = array_slice($groups, 0, 5);

        return ToolResult::success(
            $this->getName(),
            'Log digest generated',
            [
                'groups' => $groups,
                'top_errors' => $topErrors,
            ],
            [
                'duration_ms' => round((microtime(true) - $start) * 1000),
                'path' => $path,
                'window_minutes' => $windowMinutes,
            ]
        );
    }
}
