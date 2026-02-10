<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Registry;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\ToolInterface;

class ToolRegistry
{
    private $tools = [];

    public function register(ToolInterface $tool)
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function get($name)
    {
        if (!isset($this->tools[$name])) {
            throw new \RuntimeException("Tool '{$name}' not found");
        }

        return $this->tools[$name];
    }

    public function has($name)
    {
        return isset($this->tools[$name]);
    }

    public function all()
    {
        return $this->tools;
    }

    public function list()
    {
        $list = [];

        foreach ($this->tools as $name => $tool) {
            $list[] = [
                'name' => $name,
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema(),
            ];
        }

        return $list;
    }
}
