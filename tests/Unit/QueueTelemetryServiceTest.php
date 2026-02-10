<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Services\QueueTelemetryService;
use PDO;
use PHPUnit\Framework\TestCase;

class QueueTelemetryServiceTest extends TestCase
{
    private $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/php_boost_queue_' . uniqid('', true) . '.sqlite';

        $pdo = new PDO('sqlite:' . $this->dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('CREATE TABLE jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT, attempts INTEGER, available_at INTEGER)');
        $pdo->exec('CREATE TABLE failed_jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue TEXT, failed_at TEXT)');
        $pdo->exec('CREATE TABLE horizon_supervisors (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $now = time();
        $pdo->exec("INSERT INTO jobs (queue, attempts, available_at) VALUES ('default', 0, " . ($now - 30) . ")");
        $pdo->exec("INSERT INTO jobs (queue, attempts, available_at) VALUES ('default', 2, " . ($now - 60) . ")");
        $pdo->exec("INSERT INTO failed_jobs (queue, failed_at) VALUES ('default', datetime('now'))");
        $pdo->exec("INSERT INTO horizon_supervisors (name) VALUES ('sup-1')");
    }

    protected function tearDown(): void
    {
        if ($this->dbPath && file_exists($this->dbPath)) {
            @unlink($this->dbPath);
        }

        parent::tearDown();
    }

    public function testCollectReturnsQueueMetricsFromDatabase()
    {
        $service = new QueueTelemetryService([
            'database' => [
                'driver' => 'sqlite',
                'database' => $this->dbPath,
            ],
        ]);

        $result = $service->collect(['queue' => 'default', 'window_minutes' => 120]);

        $this->assertSame(2, $result['stats']['pending']);
        $this->assertSame(1, $result['stats']['failed']);
        $this->assertSame(1, $result['stats']['retries']);
        $this->assertSame(1, $result['stats']['workers']);
        $this->assertIsInt($result['stats']['lag']);
    }
}
