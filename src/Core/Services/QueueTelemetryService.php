<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

use PDO;

class QueueTelemetryService
{
    private $config;
    private $pdo;
    private $driver;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->driver = $this->normalizeDriver($this->getConfig('database.driver', 'mysql'));
        $this->initializePdo();
    }

    public function collect(array $arguments = [])
    {
        $queue = isset($arguments['queue']) ? $arguments['queue'] : null;
        $windowMinutes = isset($arguments['window_minutes']) ? max(1, (int) $arguments['window_minutes']) : 60;

        $stats = [
            'pending' => null,
            'failed' => null,
            'retries' => null,
            'workers' => null,
            'lag' => null,
            'driver_coverage' => 'database + optional redis/horizon heuristics',
        ];

        $alerts = [];

        if (!$this->pdo) {
            $alerts[] = 'Database connection unavailable for queue telemetry';
            return ['stats' => $stats, 'alerts' => $alerts];
        }

        try {
            if ($this->tableExists('jobs')) {
                $stats['pending'] = $this->countJobs('jobs', $queue);
                $stats['retries'] = $this->countRetries($queue);
                $stats['lag'] = $this->computeLagSeconds($queue);
            } else {
                $alerts[] = 'jobs table not found';
            }

            if ($this->tableExists('failed_jobs')) {
                $stats['failed'] = $this->countFailedJobs($queue, $windowMinutes);
            } else {
                $alerts[] = 'failed_jobs table not found';
            }

            $workers = $this->detectHorizonWorkers();
            if ($workers !== null) {
                $stats['workers'] = $workers;
            } else {
                $alerts[] = 'Horizon worker data not available';
            }

            $redisDepth = $this->detectRedisQueueDepth($queue);
            if ($redisDepth !== null) {
                $stats['redis_depth'] = $redisDepth;
            }
        } catch (\Exception $e) {
            $alerts[] = 'Queue telemetry partial failure: ' . $e->getMessage();
        }

        return [
            'stats' => $stats,
            'alerts' => $alerts,
        ];
    }

    private function countJobs($table, $queue = null)
    {
        $sql = 'SELECT COUNT(*) AS c FROM ' . $table;
        $params = [];

        if ($queue) {
            $sql .= ' WHERE queue = :queue';
            $params[':queue'] = $queue;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['c']) ? (int) $row['c'] : 0;
    }

    private function countRetries($queue = null)
    {
        if (!$this->tableExists('jobs')) {
            return null;
        }

        $sql = 'SELECT COUNT(*) AS c FROM jobs WHERE attempts > 1';
        $params = [];

        if ($queue) {
            $sql .= ' AND queue = :queue';
            $params[':queue'] = $queue;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['c']) ? (int) $row['c'] : 0;
    }

    private function countFailedJobs($queue = null, $windowMinutes = 60)
    {
        if (!$this->tableExists('failed_jobs')) {
            return null;
        }

        $params = [];
        $sql = 'SELECT COUNT(*) AS c FROM failed_jobs';

        if ($this->hasColumn('failed_jobs', 'failed_at')) {
            $threshold = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
            $sql .= ' WHERE failed_at >= :threshold';
            $params[':threshold'] = $threshold;

            if ($queue && $this->hasColumn('failed_jobs', 'queue')) {
                $sql .= ' AND queue = :queue';
                $params[':queue'] = $queue;
            }
        } elseif ($queue && $this->hasColumn('failed_jobs', 'queue')) {
            $sql .= ' WHERE queue = :queue';
            $params[':queue'] = $queue;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['c']) ? (int) $row['c'] : 0;
    }

    private function computeLagSeconds($queue = null)
    {
        if (!$this->tableExists('jobs') || !$this->hasColumn('jobs', 'available_at')) {
            return null;
        }

        $sql = 'SELECT MIN(available_at) AS min_available FROM jobs';
        $params = [];

        if ($queue && $this->hasColumn('jobs', 'queue')) {
            $sql .= ' WHERE queue = :queue';
            $params[':queue'] = $queue;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!isset($row['min_available']) || $row['min_available'] === null) {
            return 0;
        }

        $minAvailable = (int) $row['min_available'];

        if ($minAvailable > 1000000000) {
            return max(0, time() - $minAvailable);
        }

        return max(0, time() - $minAvailable);
    }

    private function detectHorizonWorkers()
    {
        if ($this->tableExists('horizon_supervisors')) {
            $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM horizon_supervisors');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($row['c']) ? (int) $row['c'] : null;
        }

        return null;
    }

    private function detectRedisQueueDepth($queue = null)
    {
        if (!class_exists('Illuminate\\Support\\Facades\\Redis')) {
            return null;
        }

        try {
            $key = 'queues:' . ($queue ?: 'default');
            $depth = \Illuminate\Support\Facades\Redis::llen($key);
            return $depth !== null ? (int) $depth : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function tableExists($table)
    {
        $table = trim((string) $table);

        if ($this->driver === 'mysql') {
            $stmt = $this->pdo->prepare('SHOW TABLES LIKE :table');
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'pgsql') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = :table");
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'sqlite') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name = :table");
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'sqlsrv') {
            $stmt = $this->pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = :table');
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'oracle') {
            $stmt = $this->pdo->prepare('SELECT 1 FROM USER_TABLES WHERE TABLE_NAME = :table');
            $stmt->execute([':table' => strtoupper($table)]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        return false;
    }

    private function hasColumn($table, $column)
    {
        if ($this->driver === 'mysql') {
            $stmt = $this->pdo->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE :column');
            $stmt->execute([':column' => $column]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'pgsql') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = :table AND column_name = :column");
            $stmt->execute([':table' => $table, ':column' => $column]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(' . $table . ')');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (isset($row['name']) && $row['name'] === $column) {
                    return true;
                }
            }
            return false;
        }

        if ($this->driver === 'sqlsrv') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table AND COLUMN_NAME = :column");
            $stmt->execute([':table' => $table, ':column' => $column]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        if ($this->driver === 'oracle') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM USER_TAB_COLUMNS WHERE TABLE_NAME = :table AND COLUMN_NAME = :column");
            $stmt->execute([':table' => strtoupper($table), ':column' => strtoupper($column)]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        }

        return false;
    }

    private function initializePdo()
    {
        $dbConfig = $this->getConfig('database', []);
        if (empty($dbConfig)) {
            return;
        }

        $driver = $this->driver;
        $host = isset($dbConfig['host']) ? $dbConfig['host'] : 'localhost';
        $database = isset($dbConfig['database']) ? $dbConfig['database'] : '';
        $username = isset($dbConfig['username']) ? $dbConfig['username'] : '';
        $password = isset($dbConfig['password']) ? $dbConfig['password'] : '';
        $port = isset($dbConfig['port']) ? (int) $dbConfig['port'] : $this->defaultPort($driver);

        try {
            $dsn = $this->buildDsn($driver, $host, $port, $database);
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\Exception $e) {
            $this->pdo = null;
        }
    }

    private function buildDsn($driver, $host, $port, $database)
    {
        if ($driver === 'mysql') {
            return 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database;
        }

        if ($driver === 'pgsql') {
            return 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database;
        }

        if ($driver === 'sqlite') {
            return 'sqlite:' . $database;
        }

        if ($driver === 'sqlsrv') {
            return 'sqlsrv:Server=' . $host . ',' . $port . ';Database=' . $database;
        }

        if ($driver === 'oracle') {
            return 'oci:dbname=//' . $host . ':' . $port . '/' . $database;
        }

        return $driver . ':host=' . $host . ';port=' . $port . ';dbname=' . $database;
    }

    private function defaultPort($driver)
    {
        $ports = [
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlsrv' => 1433,
            'oracle' => 1521,
        ];

        return isset($ports[$driver]) ? $ports[$driver] : 3306;
    }

    private function normalizeDriver($driver)
    {
        $driver = strtolower((string) $driver);
        if ($driver === 'mssql') {
            return 'sqlsrv';
        }
        if ($driver === 'oci') {
            return 'oracle';
        }
        return $driver;
    }

    private function getConfig($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
