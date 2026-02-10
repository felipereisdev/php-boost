<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Contracts;

interface ToolInterface
{
    public function getName();

    public function getDescription();

    public function getInputSchema();

    public function execute(array $arguments);

    public function isReadOnly();
}
