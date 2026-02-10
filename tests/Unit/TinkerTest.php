<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Laravel\Tools\Tinker;
use PHPUnit\Framework\TestCase;

class TinkerTest extends TestCase
{
    public function testGetName()
    {
        $tool = new Tinker([]);
        $this->assertEquals('Tinker', $tool->getName());
    }

    public function testGetDescription()
    {
        $tool = new Tinker([]);
        $this->assertNotEmpty($tool->getDescription());
    }

    public function testIsNotReadOnly()
    {
        $tool = new Tinker([]);
        $this->assertFalse($tool->isReadOnly());
    }

    public function testGetInputSchema()
    {
        $tool = new Tinker([]);
        $schema = $tool->getInputSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('code', $schema['properties']);
        $this->assertContains('code', $schema['required']);
    }

    public function testMissingCodeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $tool = new Tinker([]);
        $tool->execute([]);
    }
}
