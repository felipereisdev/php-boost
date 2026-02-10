<?php

namespace Tests\Integration;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\TransportInterface;
use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;
use FelipeReisDev\PhpBoost\Core\Tools\ExplainQuery;
use FelipeReisDev\PhpBoost\Core\Tools\QueueHealth;
use FelipeReisDev\PhpBoost\Core\Tools\SchemaDiff;
use FelipeReisDev\PhpBoost\Core\Tools\TableDDL;
use PDO;
use PHPUnit\Framework\TestCase;

class SqliteToolsIntegrationTest extends TestCase
{
    private $tmpDir;
    private $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/php_boost_it_' . uniqid('', true);
        $this->dbPath = $this->tmpDir . '/database.sqlite';

        @mkdir($this->tmpDir . '/database/migrations', 0777, true);

        $pdo = new PDO('sqlite:' . $this->dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, user_id INTEGER)');
        $pdo->exec("INSERT INTO posts (title, user_id) VALUES ('a', 1), ('b', 2)");

        $pdo->exec('CREATE TABLE jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT, attempts INTEGER, available_at INTEGER)');
        $pdo->exec('CREATE TABLE failed_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT, failed_at TEXT)');
        $pdo->exec('CREATE TABLE horizon_supervisors (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $now = time();
        $pdo->exec("INSERT INTO jobs (queue, attempts, available_at) VALUES ('default', 0, " . ($now - 10) . ")");
        $pdo->exec("INSERT INTO jobs (queue, attempts, available_at) VALUES ('default', 2, " . ($now - 20) . ")");
        $pdo->exec("INSERT INTO failed_jobs (queue, failed_at) VALUES ('default', datetime('now'))");
        $pdo->exec("INSERT INTO horizon_supervisors (name) VALUES ('sup-1')");

        file_put_contents($this->tmpDir . '/database/migrations/2026_01_01_000000_alter_posts.php', <<<'PHPF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('status')->nullable();
            $table->dropColumn('title');
        });
    }
};
PHPF
);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    public function testSqlitePhaseOneAndThreeTools()
    {
        $config = [
            'database' => [
                'driver' => 'sqlite',
                'database' => $this->dbPath,
            ],
        ];

        $explain = new ExplainQuery($config);
        $explainResult = $explain->execute(['query' => 'SELECT * FROM posts']);
        $this->assertArrayHasKey('data', $explainResult);
        $this->assertArrayHasKey('plan_raw', $explainResult['data']);

        $ddl = new TableDDL($config);
        $ddlResult = $ddl->execute(['object_type' => 'table', 'name' => 'posts']);
        $this->assertArrayHasKey('ddl', $ddlResult['data']);
        $this->assertStringContainsString('CREATE TABLE', strtoupper((string) $ddlResult['data']['ddl']));

        $schemaDiff = new SchemaDiff($config);
        $diffResult = $schemaDiff->execute(['base_path' => $this->tmpDir]);
        $this->assertArrayHasKey('pending_migrations', $diffResult['data']);
        $this->assertNotEmpty($diffResult['data']['pending_migrations']);
        $this->assertArrayHasKey('risk_score', $diffResult['data']);

        $queue = new QueueHealth($config);
        $queueResult = $queue->execute(['queue' => 'default', 'window_minutes' => 120]);
        $this->assertArrayHasKey('stats', $queueResult['data']);
        $this->assertSame(2, $queueResult['data']['stats']['pending']);
        $this->assertSame(1, $queueResult['data']['stats']['failed']);
    }

    public function testLegacyToolOutputIsNormalizedByServerEnvelope()
    {
        $transport = new ArrayTransport([
            json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => (object) []]),
            json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'GetConfig',
                    'arguments' => ['key' => 'database.driver', 'default' => 'sqlite'],
                ],
            ]),
        ]);

        $server = new Server($transport, ['database' => ['driver' => 'sqlite']]);
        ToolRegistrar::registerCoreTools($server->getToolRegistry(), ['database' => ['driver' => 'sqlite']]);

        $server->start();

        $messages = $transport->decodedWrites();
        $toolCallText = $messages[1]['result']['content'][0]['text'];
        $payload = json_decode($toolCallText, true);

        $this->assertSame('GetConfig', $payload['tool']);
        $this->assertSame('ok', $payload['status']);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertTrue($payload['meta']['normalized_legacy_output']);
    }

    private function removeDir($dir)
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}

class ArrayTransport implements TransportInterface
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
