<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\BoostDocs;
use PHPUnit\Framework\TestCase;

class BoostDocsToolTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/boost_docs_' . uniqid('', true);
        @mkdir($this->tmpDir . '/routes', 0777, true);
        file_put_contents($this->tmpDir . '/composer.json', '{"name":"tmp/test","require":{"php":"^8.0"}}');
        file_put_contents($this->tmpDir . '/routes/api.php', "<?php\nRoute::get('/ok', 'DemoController@index');");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/composer.json');
        @unlink($this->tmpDir . '/routes/api.php');
        @unlink($this->tmpDir . '/docs.json');
        @rmdir($this->tmpDir . '/routes');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testGenerateAndWriteDocs()
    {
        $tool = new BoostDocs([]);
        $result = $tool->execute([
            'base_path' => $this->tmpDir,
            'type' => 'openapi',
            'output' => 'docs.json',
        ]);

        $this->assertSame('BoostDocs', $result['tool']);
        $this->assertFalse($tool->isReadOnly());
        $this->assertFileExists($this->tmpDir . '/docs.json');
    }
}
