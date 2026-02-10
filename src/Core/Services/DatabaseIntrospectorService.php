<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

use PDO;

class DatabaseIntrospectorService
{
    private $config;
    private $pdo;
    private $driverCapabilities;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->driverCapabilities = new DriverCapabilities();
        $this->initializePdo();
    }

    public function getDriver()
    {
        return $this->driverCapabilities->normalize($this->getConfig('database.driver', 'mysql'));
    }

    public function explain($query, array $options = [])
    {
        if (!$this->driverCapabilities->isReadOnlySql($query)) {
            throw new \InvalidArgumentException('Only SELECT/CTE read-only queries are allowed');
        }

        $driver = $this->getDriver();
        $warnings = [];

        if ($driver === 'pgsql') {
            $prefix = 'EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) ';
            $stmt = $this->pdo->query($prefix . $query);
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $plan = isset($row[0]) ? json_decode($row[0], true) : [];
            return ['plan' => $plan, 'warnings' => $warnings];
        }

        if ($driver === 'mysql') {
            $stmt = $this->pdo->query('EXPLAIN FORMAT=JSON ' . $query);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $json = isset($row['EXPLAIN']) ? $row['EXPLAIN'] : '{}';
            $plan = json_decode($json, true);
            return ['plan' => $plan, 'warnings' => $warnings];
        }

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('EXPLAIN QUERY PLAN ' . $query);
            $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $warnings[] = 'SQLite does not support ANALYZE/BUFFERS JSON explain equivalent';
            return ['plan' => $plan, 'warnings' => $warnings];
        }

        if ($driver === 'sqlsrv') {
            $stmt = $this->pdo->query('SET SHOWPLAN_TEXT ON; ' . $query . '; SET SHOWPLAN_TEXT OFF;');
            $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $warnings[] = 'SQL Server plan is text fallback';
            return ['plan' => $plan, 'warnings' => $warnings];
        }

        if ($driver === 'oracle') {
            $stmt = $this->pdo->query('EXPLAIN PLAN FOR ' . $query);
            $stmt = $this->pdo->query('SELECT PLAN_TABLE_OUTPUT FROM TABLE(DBMS_XPLAN.DISPLAY())');
            $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $warnings[] = 'Oracle plan is text fallback';
            return ['plan' => $plan, 'warnings' => $warnings];
        }

        throw new \RuntimeException('Unsupported database driver for explain: ' . $driver);
    }

    public function getObjectDdl($type, $name, $schema = null)
    {
        $type = strtolower((string) $type);
        $name = $this->driverCapabilities->sanitizeIdentifier($name);
        $driver = $this->getDriver();

        if ($driver === 'mysql') {
            if ($type === 'table') {
                $stmt = $this->pdo->query('SHOW CREATE TABLE ' . $name);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['Create Table']) ? $row['Create Table'] : '';
            }

            if ($type === 'view') {
                $stmt = $this->pdo->query('SHOW CREATE VIEW ' . $name);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['Create View']) ? $row['Create View'] : '';
            }
        }

        if ($driver === 'pgsql') {
            if ($type === 'view') {
                $stmt = $this->pdo->prepare('SELECT pg_get_viewdef(c.oid, true) AS ddl FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE c.relkind = :kind AND c.relname = :name');
                $stmt->execute([':kind' => 'v', ':name' => $name]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['ddl']) ? 'CREATE VIEW ' . $name . ' AS ' . $row['ddl'] : '';
            }

            if ($type === 'index') {
                $stmt = $this->pdo->prepare('SELECT pg_get_indexdef(indexrelid) AS ddl FROM pg_index i JOIN pg_class c ON c.oid = i.indexrelid WHERE c.relname = :name');
                $stmt->execute([':name' => $name]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['ddl']) ? $row['ddl'] : '';
            }

            if ($type === 'constraint') {
                $stmt = $this->pdo->prepare('SELECT pg_get_constraintdef(oid) AS ddl FROM pg_constraint WHERE conname = :name');
                $stmt->execute([':name' => $name]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['ddl']) ? 'ALTER TABLE ADD CONSTRAINT ' . $name . ' ' . $row['ddl'] : '';
            }

            if ($type === 'table') {
                $stmt = $this->pdo->prepare('SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = :name ORDER BY ordinal_position');
                $stmt->execute([':name' => $name]);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $parts = [];
                foreach ($columns as $column) {
                    $line = $column['column_name'] . ' ' . $column['data_type'];
                    if ($column['is_nullable'] === 'NO') {
                        $line .= ' NOT NULL';
                    }
                    if ($column['column_default'] !== null) {
                        $line .= ' DEFAULT ' . $column['column_default'];
                    }
                    $parts[] = $line;
                }

                return 'CREATE TABLE ' . $name . " (\n  " . implode(",\n  ", $parts) . "\n);";
            }
        }

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare("SELECT sql FROM sqlite_master WHERE type = :type AND name = :name");
            $stmt->execute([':type' => $type === 'table' ? 'table' : $type, ':name' => $name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($row['sql']) ? $row['sql'] : '';
        }

        if ($driver === 'sqlsrv') {
            if ($type === 'table' || $type === 'view') {
                $stmt = $this->pdo->prepare('SELECT OBJECT_DEFINITION(OBJECT_ID(:name)) AS ddl');
                $stmt->execute([':name' => $name]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['ddl']) ? $row['ddl'] : '';
            }
        }

        if ($driver === 'oracle') {
            if ($type === 'table' || $type === 'view') {
                $stmt = $this->pdo->prepare('SELECT DBMS_METADATA.GET_DDL(:type, :name) AS ddl FROM dual');
                $stmt->execute([':type' => strtoupper($type), ':name' => strtoupper($name)]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return isset($row['ddl']) ? $row['ddl'] : '';
            }
        }

        return '';
    }

    public function getCurrentSchemaSnapshot()
    {
        $driver = $this->getDriver();

        if ($driver === 'mysql') {
            $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            return $this->buildSnapshotFromTables($tables);
        }

        if ($driver === 'pgsql') {
            $tables = $this->pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
            return $this->buildSnapshotFromTables($tables);
        }

        if ($driver === 'sqlite') {
            $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            return $this->buildSnapshotFromTables($tables);
        }

        return ['tables' => []];
    }

    private function buildSnapshotFromTables(array $tables)
    {
        $result = ['tables' => []];

        foreach ($tables as $table) {
            try {
                $schema = $this->getTableColumns($table);
                $result['tables'][$table] = ['columns' => $schema];
            } catch (\Exception $e) {
                $result['tables'][$table] = ['columns' => [], 'error' => $e->getMessage()];
            }
        }

        return $result;
    }

    private function getTableColumns($table)
    {
        $driver = $this->getDriver();

        if ($driver === 'mysql') {
            $stmt = $this->pdo->query('DESCRIBE ' . $this->driverCapabilities->sanitizeIdentifier($table));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = [];
            foreach ($rows as $row) {
                $columns[$row['Field']] = strtolower($row['Type']);
            }
            return $columns;
        }

        if ($driver === 'pgsql') {
            $stmt = $this->pdo->prepare('SELECT column_name, data_type FROM information_schema.columns WHERE table_name = :name');
            $stmt->execute([':name' => $table]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = [];
            foreach ($rows as $row) {
                $columns[$row['column_name']] = strtolower($row['data_type']);
            }
            return $columns;
        }

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(' . $this->driverCapabilities->sanitizeIdentifier($table) . ')');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = [];
            foreach ($rows as $row) {
                $columns[$row['name']] = strtolower($row['type']);
            }
            return $columns;
        }

        return [];
    }

    private function initializePdo()
    {
        $dbConfig = $this->getConfig('database', []);

        if (empty($dbConfig)) {
            return;
        }

        $driver = $this->driverCapabilities->normalize(isset($dbConfig['driver']) ? $dbConfig['driver'] : 'mysql');
        $host = isset($dbConfig['host']) ? $dbConfig['host'] : 'localhost';
        $database = isset($dbConfig['database']) ? $dbConfig['database'] : '';
        $username = isset($dbConfig['username']) ? $dbConfig['username'] : '';
        $password = isset($dbConfig['password']) ? $dbConfig['password'] : '';
        $port = isset($dbConfig['port']) ? $dbConfig['port'] : $this->driverCapabilities->defaultPort($driver);
        $charset = isset($dbConfig['charset']) ? $dbConfig['charset'] : null;

        $dsn = $this->buildDsn($driver, $host, $port, $database, $charset);

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function buildDsn($driver, $host, $port, $database, $charset)
    {
        if ($driver === 'mysql') {
            $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database;
            if ($charset) {
                $dsn .= ';charset=' . $charset;
            }
            return $dsn;
        }

        if ($driver === 'pgsql') {
            $dsn = 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database;
            if ($charset) {
                $dsn .= ";options='--client_encoding=" . $charset . "'";
            }
            return $dsn;
        }

        if ($driver === 'oracle') {
            return 'oci:dbname=//' . $host . ':' . $port . '/' . $database;
        }

        if ($driver === 'sqlsrv') {
            return 'sqlsrv:Server=' . $host . ',' . $port . ';Database=' . $database;
        }

        if ($driver === 'sqlite') {
            return 'sqlite:' . $database;
        }

        return $driver . ':host=' . $host . ';port=' . $port . ';dbname=' . $database;
    }

    private function getConfig($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
