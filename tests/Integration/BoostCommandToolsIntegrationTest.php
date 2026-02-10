<?php

namespace Tests\Integration;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\TransportInterface;
use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;
use FelipeReisDev\PhpBoost\Core\Tools\BoostAnalyze;
use FelipeReisDev\PhpBoost\Core\Tools\BoostDocs;
use FelipeReisDev\PhpBoost\Core\Tools\BoostHealth;
use FelipeReisDev\PhpBoost\Core\Tools\BoostMigrateGuide;
use FelipeReisDev\PhpBoost\Core\Tools\BoostProfile;
use FelipeReisDev\PhpBoost\Core\Tools\BoostSnippet;
use FelipeReisDev\PhpBoost\Core\Tools\BoostValidate;
use PHPUnit\Framework\TestCase;

class BoostCommandToolsIntegrationTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/boost_cmd_tools_' . uniqid('', true);
        @mkdir($this->tmpDir . '/app', 0777, true);
        @mkdir($this->tmpDir . '/routes', 0777, true);
        file_put_contents($this->tmpDir . '/composer.json', '{"name":"tmp/test","require":{"php":"^8.0"}}');
        file_put_contents($this->tmpDir . '/app/Demo.php', "<?php\nclass Demo { public function x(){ return DB::select('select * from users'); } }");
        file_put_contents($this->tmpDir . '/routes/api.php', "<?php\nRoute::get('/ok', 'DemoController@index');");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpDir . '/composer.json');
        @unlink($this->tmpDir . '/app/Demo.php');
        @unlink($this->tmpDir . '/routes/api.php');
        @unlink($this->tmpDir . '/snippet.php');
        @unlink($this->tmpDir . '/docs.json');
        @unlink($this->tmpDir . '/analysis.md');
        @unlink($this->tmpDir . '/profile.json');
        @unlink($this->tmpDir . '/.php-boost/health-history.json');
        @rmdir($this->tmpDir . '/.php-boost');
        @rmdir($this->tmpDir . '/app');
        @rmdir($this->tmpDir . '/routes');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    public function testToolsListContainsBoostCommandTools()
    {
        $transport = new ArrayTransportBoostCommand([
            json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => (object) []]),
            json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => (object) []]),
        ]);

        $server = new Server($transport, []);
        ToolRegistrar::registerCoreTools($server->getToolRegistry(), []);
        $server->start();

        $messages = $transport->decodedWrites();
        $tools = $messages[1]['result']['tools'];
        $names = array_map(function ($tool) { return $tool['name']; }, $tools);

        $this->assertContains('BoostValidate', $names);
        $this->assertContains('BoostMigrateGuide', $names);
        $this->assertContains('BoostHealth', $names);
        $this->assertContains('BoostSnippet', $names);
        $this->assertContains('BoostProfile', $names);
        $this->assertContains('BoostDocs', $names);
        $this->assertContains('BoostAnalyze', $names);
    }

    public function testEachBoostToolRunsWithMinimumProjectFixture()
    {
        $validate = new BoostValidate([]);
        $validateResult = $validate->execute(['base_path' => $this->tmpDir]);
        $this->assertSame('BoostValidate', $validateResult['tool']);

        $migrate = new BoostMigrateGuide([]);
        $migrateResult = $migrate->execute(['base_path' => $this->tmpDir, 'from' => 'laravel-8', 'to' => 'laravel-11']);
        $this->assertSame('BoostMigrateGuide', $migrateResult['tool']);

        $health = new BoostHealth([]);
        $healthResult = $health->execute(['base_path' => $this->tmpDir, 'save' => true]);
        $this->assertFileExists($this->tmpDir . '/.php-boost/health-history.json');
        $this->assertSame('BoostHealth', $healthResult['tool']);

        $snippet = new BoostSnippet([]);
        $snippetResult = $snippet->execute(['base_path' => $this->tmpDir, 'type' => 'service', 'name' => 'DemoService', 'output' => 'snippet.php']);
        $this->assertFileExists($this->tmpDir . '/snippet.php');
        $this->assertSame('BoostSnippet', $snippetResult['tool']);

        $profile = new BoostProfile([]);
        $profileResult = $profile->execute(['base_path' => $this->tmpDir, 'export' => 'profile.json']);
        $this->assertFileExists($this->tmpDir . '/profile.json');
        $this->assertSame('BoostProfile', $profileResult['tool']);

        $docs = new BoostDocs([]);
        $docsResult = $docs->execute(['base_path' => $this->tmpDir, 'type' => 'openapi', 'output' => 'docs.json']);
        $this->assertFileExists($this->tmpDir . '/docs.json');
        $this->assertSame('BoostDocs', $docsResult['tool']);

        $analyze = new BoostAnalyze([]);
        $analyzeResult = $analyze->execute(['base_path' => $this->tmpDir, 'suggest' => true, 'export' => 'analysis.md']);
        $this->assertFileExists($this->tmpDir . '/analysis.md');
        $this->assertSame('BoostAnalyze', $analyzeResult['tool']);
    }
}

class ArrayTransportBoostCommand implements TransportInterface
{
    private $queue = [];
    private $writes = [];

    public function __construct(array $messages)
    {
        $this->queue = array_values($messages);
    }

    public function read()
    {
        if (empty($this->queue)) {
            return null;
        }

        return array_shift($this->queue);
    }

    public function write($data)
    {
        $this->writes[] = $data;
    }

    public function close()
    {
    }

    public function decodedWrites()
    {
        return array_map(function ($line) {
            return json_decode($line, true);
        }, $this->writes);
    }
}
