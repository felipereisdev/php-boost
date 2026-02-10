<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostHealth;
use PHPUnit\Framework\TestCase;

class BoostHealthToolTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/boost_health_' . uniqid('', true);
        @mkdir($this->tmpDir, 0777, true);
        file_put_contents($this->tmpDir . '/composer.json', '{"name":"tmp/test","require":{"php":"^8.0"}}');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir . '/.php-boost')) {
            @unlink($this->tmpDir . '/.php-boost/health-history.json');
            @rmdir($this->tmpDir . '/.php-boost');
        }
        @unlink($this->tmpDir . '/composer.json');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testSaveWritesHistory()
    {
        $tool = new BoostHealth([]);
        $result = $tool->execute(['base_path' => $this->tmpDir, 'save' => true]);

        $this->assertSame('BoostHealth', $result['tool']);
        $this->assertFalse($tool->isReadOnly());
        $this->assertArrayHasKey('written_files', $result['data']);
        $this->assertFileExists($this->tmpDir . '/.php-boost/health-history.json');
    }
}
