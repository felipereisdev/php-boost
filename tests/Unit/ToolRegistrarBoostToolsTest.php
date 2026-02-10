<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Mcp\Registry\ToolRegistry;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;
use PHPUnit\Framework\TestCase;

class ToolRegistrarBoostToolsTest extends TestCase
{
    public function testRegistersAllBoostTools()
    {
        $registry = new ToolRegistry();
        ToolRegistrar::registerCoreTools($registry, []);

        $all = $registry->all();

        $this->assertArrayHasKey('BoostValidate', $all);
        $this->assertArrayHasKey('BoostMigrateGuide', $all);
        $this->assertArrayHasKey('BoostHealth', $all);
        $this->assertArrayHasKey('BoostSnippet', $all);
        $this->assertArrayHasKey('BoostProfile', $all);
        $this->assertArrayHasKey('BoostDocs', $all);
        $this->assertArrayHasKey('BoostAnalyze', $all);
    }
}
