<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\GetConfig;
use PHPUnit\Framework\TestCase;

class GetConfigTest extends TestCase
{
    public function testGetConfigValue()
    {
        $config = [
            'database' => [
                'driver' => 'mysql',
                'host' => 'localhost',
            ],
        ];

        $tool = new GetConfig($config);

        $this->assertEquals('GetConfig', $tool->getName());

        $result = $tool->execute(['key' => 'database.driver']);

        $this->assertIsArray($result);
        $this->assertEquals('database.driver', $result['key']);
        $this->assertEquals('mysql', $result['value']);
    }

    public function testGetConfigNotFound()
    {
        $tool = new GetConfig([]);
        $result = $tool->execute(['key' => 'nonexistent', 'default' => 'fallback']);

        $this->assertIsArray($result);
        $this->assertEquals('fallback', $result['value']);
    }

    public function testMissingRequiredArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $tool = new GetConfig([]);
        $tool->execute([]);
    }
}
