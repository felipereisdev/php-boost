<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostProfile;
use PHPUnit\Framework\TestCase;

class BoostProfileToolTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/boost_profile_' . uniqid('', true);
        @mkdir($this->tmpDir . '/app', 0777, true);
        file_put_contents($this->tmpDir . '/app/Demo.php', "<?php\nclass Demo { public function x(){ foreach([1] as \$i){ \$u = \$this->user->name; } } }");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/app/Demo.php');
        @unlink($this->tmpDir . '/report.json');
        @rmdir($this->tmpDir . '/app');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testGenerateAndExportReport()
    {
        $tool = new BoostProfile([]);
        $result = $tool->execute([
            'base_path' => $this->tmpDir,
            'min_severity' => 'low',
            'export' => 'report.json',
        ]);

        $this->assertSame('BoostProfile', $result['tool']);
        $this->assertFalse($tool->isReadOnly());
        $this->assertFileExists($this->tmpDir . '/report.json');
    }
}
