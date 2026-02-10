<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostMigrateGuide;
use PHPUnit\Framework\TestCase;

class BoostMigrateGuideToolTest extends TestCase
{
    public function testRequiredArgsValidation()
    {
        $tool = new BoostMigrateGuide([]);
        $result = $tool->execute([]);

        $this->assertSame('error', $result['status']);
    }

    public function testHappyPath()
    {
        $tool = new BoostMigrateGuide([]);
        $result = $tool->execute(['from' => 'laravel-8', 'to' => 'laravel-11', 'base_path' => getcwd()]);

        $this->assertSame('BoostMigrateGuide', $result['tool']);
        $this->assertArrayHasKey('steps', $result['data']);
    }
}
