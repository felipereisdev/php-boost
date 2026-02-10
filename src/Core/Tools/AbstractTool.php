<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\ToolInterface;

abstract class AbstractTool implements ToolInterface
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    abstract public function getName();

    abstract public function getDescription();

    abstract public function getInputSchema();

    abstract public function execute(array $arguments);

    public function isReadOnly()
    {
        return true;
    }

    protected function validateArguments(array $arguments, array $required = [])
    {
        foreach ($required as $field) {
            if (!isset($arguments[$field])) {
                throw new \InvalidArgumentException("Missing required argument: {$field}");
            }
        }
    }

    protected function getConfig($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    protected function resolveBasePath(array $arguments, array $argumentKeys = ['base_path', 'path'])
    {
        foreach ($argumentKeys as $key) {
            if (isset($arguments[$key]) && is_string($arguments[$key]) && trim($arguments[$key]) !== '') {
                return rtrim($arguments[$key], '/');
            }
        }

        $configured = $this->config['base_path'] ?? null;
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim($configured, '/');
        }

        return getcwd();
    }
}
