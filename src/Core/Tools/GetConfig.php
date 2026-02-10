<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

class GetConfig extends AbstractTool
{
    public function getName()
    {
        return 'GetConfig';
    }

    public function getDescription()
    {
        return 'Read configuration values using dot notation (e.g., "database.default")';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'key' => [
                    'type' => 'string',
                    'description' => 'Configuration key using dot notation',
                ],
                'default' => [
                    'type' => 'string',
                    'description' => 'Default value if key not found',
                ],
            ],
            'required' => ['key'],
        ];
    }

    public function execute(array $arguments)
    {
        $this->validateArguments($arguments, ['key']);

        $key = $arguments['key'];
        $default = $arguments['default'] ?? null;

        $value = $this->getConfig($key, $default);

        if ($value === null) {
            return "Configuration key '{$key}' not found";
        }

        return [
            'key' => $key,
            'value' => $value,
        ];
    }
}
