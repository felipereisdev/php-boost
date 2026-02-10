<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\DatabaseIntrospectorService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class TableDDL extends AbstractTool
{
    public function getName()
    {
        return 'TableDDL';
    }

    public function getDescription()
    {
        return 'Return real DDL for table/view/index/constraint';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'object_type' => ['type' => 'string', 'enum' => ['table', 'view', 'index', 'constraint']],
                'name' => ['type' => 'string'],
                'schema' => ['type' => 'string'],
                'include_dependencies' => ['type' => 'boolean', 'default' => false],
            ],
            'required' => ['object_type', 'name'],
        ];
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);
        $this->validateArguments($arguments, ['object_type', 'name']);

        $service = new DatabaseIntrospectorService($this->config);
        $ddl = $service->getObjectDdl(
            $arguments['object_type'],
            $arguments['name'],
            isset($arguments['schema']) ? $arguments['schema'] : null
        );

        $data = [
            'ddl' => $ddl,
            'resolved_object' => [
                'type' => $arguments['object_type'],
                'name' => $arguments['name'],
                'schema' => isset($arguments['schema']) ? $arguments['schema'] : null,
            ],
        ];

        if (!empty($arguments['include_dependencies'])) {
            $data['dependencies'] = [];
        }

        if ($ddl === '') {
            return ToolResult::warning(
                $this->getName(),
                'DDL not found for requested object',
                $data,
                ['duration_ms' => round((microtime(true) - $start) * 1000), 'driver' => $service->getDriver()]
            );
        }

        return ToolResult::success(
            $this->getName(),
            'DDL resolved successfully',
            $data,
            ['duration_ms' => round((microtime(true) - $start) * 1000), 'driver' => $service->getDriver()]
        );
    }
}
