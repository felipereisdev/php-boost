<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Laravel\Tools\QueueStatus;
use PHPUnit\Framework\TestCase;

class QueueStatusTest extends TestCase
{
    public function testGetName()
    {
        $tool = new QueueStatus([]);
        $this->assertEquals('QueueStatus', $tool->getName());
    }

    public function testGetDescription()
    {
        $tool = new QueueStatus([]);
        $this->assertNotEmpty($tool->getDescription());
    }

    public function testIsNotReadOnly()
    {
        $tool = new QueueStatus([]);
        $this->assertFalse($tool->isReadOnly());
    }

    public function testGetInputSchema()
    {
        $tool = new QueueStatus([]);
        $schema = $tool->getInputSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('type', $schema['properties']);
    }

    public function testMissingTypeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $tool = new QueueStatus([]);
        $tool->execute([]);
    }

    public function testInvalidTypeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $tool = new QueueStatus([]);
        $tool->execute(['type' => 'invalid']);
    }
}
