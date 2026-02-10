<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Laravel\Tools\CacheManager;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    public function testGetName()
    {
        $tool = new CacheManager([]);
        $this->assertEquals('CacheManager', $tool->getName());
    }

    public function testGetDescription()
    {
        $tool = new CacheManager([]);
        $this->assertNotEmpty($tool->getDescription());
    }

    public function testIsNotReadOnly()
    {
        $tool = new CacheManager([]);
        $this->assertFalse($tool->isReadOnly());
    }

    public function testGetInputSchema()
    {
        $tool = new CacheManager([]);
        $schema = $tool->getInputSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('operation', $schema['properties']);
    }

    public function testMissingOperationThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $tool = new CacheManager([]);
        $tool->execute([]);
    }

    public function testInvalidOperationThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $tool = new CacheManager([]);
        $tool->execute(['operation' => 'invalid']);
    }
}
