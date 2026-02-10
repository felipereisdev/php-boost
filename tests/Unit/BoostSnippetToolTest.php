<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostSnippet;
use PHPUnit\Framework\TestCase;

class BoostSnippetToolTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/boost_snippet_' . uniqid('', true);
        @mkdir($this->tmpDir, 0777, true);
        file_put_contents($this->tmpDir . '/composer.json', '{"name":"tmp/test","require":{"php":"^8.0"}}');
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/composer.json');
        @unlink($this->tmpDir . '/snippet.php');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testListAndWriteOutput()
    {
        $tool = new BoostSnippet([]);

        $listResult = $tool->execute(['base_path' => $this->tmpDir, 'list' => true]);
        $this->assertArrayHasKey('types', $listResult['data']);

        $writeResult = $tool->execute([
            'base_path' => $this->tmpDir,
            'type' => 'service',
            'name' => 'BillingService',
            'output' => 'snippet.php',
        ]);

        $this->assertFalse($tool->isReadOnly());
        $this->assertFileExists($this->tmpDir . '/snippet.php');
        $this->assertArrayHasKey('written_files', $writeResult['data']);
    }
}
