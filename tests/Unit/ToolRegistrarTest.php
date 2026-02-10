<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Mcp\Registry\ToolRegistry;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;
use PHPUnit\Framework\TestCase;

class ToolRegistrarTest extends TestCase
{
    public function testRegisterCoreTools()
    {
        $registry = new ToolRegistry();
        ToolRegistrar::registerCoreTools($registry, []);

        $tools = $registry->all();

        $this->assertArrayHasKey('ExplainQuery', $tools);
        $this->assertArrayHasKey('TableDDL', $tools);
        $this->assertArrayHasKey('LogErrorDigest', $tools);
        $this->assertArrayHasKey('SchemaDiff', $tools);
        $this->assertArrayHasKey('PolicyAudit', $tools);
        $this->assertArrayHasKey('DeadCodeHints', $tools);
    }
}
