<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Laravel\Tools\ApplicationInfo;
use PHPUnit\Framework\TestCase;

class ApplicationInfoTest extends TestCase
{
    public function testGetName()
    {
        $tool = new ApplicationInfo([]);
        $this->assertEquals('ApplicationInfo', $tool->getName());
    }

    public function testGetDescription()
    {
        $tool = new ApplicationInfo([]);
        $this->assertNotEmpty($tool->getDescription());
    }

    public function testIsReadOnly()
    {
        $tool = new ApplicationInfo([]);
        $this->assertTrue($tool->isReadOnly());
    }

    public function testGetInputSchema()
    {
        $tool = new ApplicationInfo([]);
        $schema = $tool->getInputSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('section', $schema['properties']);
    }
}
