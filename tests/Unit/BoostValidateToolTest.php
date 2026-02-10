<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostValidate;
use PHPUnit\Framework\TestCase;

class BoostValidateToolTest extends TestCase
{
    public function testContractAndExecution()
    {
        $tool = new BoostValidate([]);

        $this->assertSame('BoostValidate', $tool->getName());
        $this->assertTrue($tool->isReadOnly());

        $result = $tool->execute(['base_path' => getcwd(), 'ci' => true, 'threshold' => 0]);

        $this->assertSame('BoostValidate', $result['tool']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('exit_code', $result['data']);
    }
}
