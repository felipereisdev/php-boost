<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostAnalyze;
use PHPUnit\Framework\TestCase;

class BoostAnalyzeToolTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/boost_analyze_' . uniqid('', true);
        @mkdir($this->tmpDir . '/app', 0777, true);
        file_put_contents($this->tmpDir . '/app/Demo.php', "<?php\nclass Demo { public function x(){ return DB::select('select * from users'); } }");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/app/Demo.php');
        @unlink($this->tmpDir . '/guidelines.md');
        @rmdir($this->tmpDir . '/app');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testAnalyzeAndExport()
    {
        $tool = new BoostAnalyze([]);
        $result = $tool->execute([
            'base_path' => $this->tmpDir,
            'suggest' => true,
            'export' => 'guidelines.md',
        ]);

        $this->assertSame('BoostAnalyze', $result['tool']);
        $this->assertFalse($tool->isReadOnly());
        $this->assertFileExists($this->tmpDir . '/guidelines.md');
        $this->assertArrayHasKey('analysis', $result['data']);
    }
}
