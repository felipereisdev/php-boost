<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Laravel\Tools\ListArtisanCommands;
use PHPUnit\Framework\TestCase;

class ListArtisanCommandsTest extends TestCase
{
    public function testGetName()
    {
        $tool = new ListArtisanCommands([]);
        $this->assertEquals('ListArtisanCommands', $tool->getName());
    }

    public function testGetDescription()
    {
        $tool = new ListArtisanCommands([]);
        $this->assertNotEmpty($tool->getDescription());
    }

    public function testIsReadOnly()
    {
        $tool = new ListArtisanCommands([]);
        $this->assertTrue($tool->isReadOnly());
    }

    public function testGetInputSchema()
    {
        $tool = new ListArtisanCommands([]);
        $schema = $tool->getInputSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }
}
