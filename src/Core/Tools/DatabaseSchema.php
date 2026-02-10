<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use PDO;

class DatabaseSchema extends AbstractTool
{
    private $pdo;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->initializePdo();
    }

    private function initializePdo()
    {
        $dbConfig = $this->getConfig('database', []);
        
        if (empty($dbConfig)) {
            return;
        }

        $driver = $dbConfig['driver'] ?? 'mysql';
        $host = $dbConfig['host'] ?? 'localhost';
        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';
        $port = $dbConfig['port'] ?? $this->getDefaultPort($driver);
        $charset = $dbConfig['charset'] ?? null;

        try {
            $dsn = $this->buildDsn($driver, $host, $port, $database, $charset);
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function getDefaultPort($driver)
    {
        $ports = [
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlsrv' => 1433,
            'mssql' => 1433,
            'oci' => 1521,
            'oracle' => 1521,
        ];
        
        return $ports[$driver] ?? 3306;
    }
    
    private function buildDsn($driver, $host, $port, $database, $charset)
    {
        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host={$host};port={$port};dbname={$database}";
                if ($charset) {
                    $dsn .= ";charset={$charset}";
                }
                return $dsn;
            
            case 'pgsql':
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                if ($charset) {
                    $dsn .= ";options='--client_encoding={$charset}'";
                }
                return $dsn;
            
            case 'oci':
            case 'oracle':
                return "oci:dbname=//{$host}:{$port}/{$database}";
            
            case 'sqlsrv':
            case 'mssql':
                return "sqlsrv:Server={$host},{$port};Database={$database}";
            
            case 'sqlite':
                return "sqlite:{$database}";
            
            default:
                return "{$driver}:host={$host};port={$port};dbname={$database}";
        }
    }

    public function getName()
    {
        return 'DatabaseSchema';
    }

    public function getDescription()
    {
        return 'Get database schema information (tables, columns, indexes)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'table' => [
                    'type' => 'string',
                    'description' => 'Table name (optional, returns all tables if not provided)',
                ],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        if (!$this->pdo) {
            throw new \RuntimeException('Database not configured');
        }

        $table = $arguments['table'] ?? null;

        if ($table) {
            return $this->getTableSchema($table);
        }

        return $this->getAllTables();
    }

    private function getAllTables()
    {
        $driver = $this->getConfig('database.driver', 'mysql');

        if ($driver === 'mysql') {
            $stmt = $this->pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($driver === 'pgsql') {
            $stmt = $this->pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($driver === 'sqlite') {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($driver === 'oci' || $driver === 'oracle') {
            $stmt = $this->pdo->query("SELECT table_name FROM user_tables ORDER BY table_name");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($driver === 'sqlsrv' || $driver === 'mssql') {
            $stmt = $this->pdo->query("SELECT name FROM sys.tables ORDER BY name");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }

        return ['tables' => $tables];
    }

    private function getTableSchema($table)
    {
        $driver = $this->getConfig('database.driver', 'mysql');

        if ($driver === 'mysql') {
            $stmt = $this->pdo->prepare("DESCRIBE {$table}");
            $stmt->execute();
            $columns = $stmt->fetchAll();
        } elseif ($driver === 'pgsql') {
            $stmt = $this->pdo->prepare("
                SELECT column_name, data_type, is_nullable, column_default
                FROM information_schema.columns
                WHERE table_name = ?
            ");
            $stmt->execute([$table]);
            $columns = $stmt->fetchAll();
        } elseif ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare("PRAGMA table_info({$table})");
            $stmt->execute();
            $columns = $stmt->fetchAll();
        } elseif ($driver === 'oci' || $driver === 'oracle') {
            $stmt = $this->pdo->prepare("
                SELECT column_name, data_type, nullable, data_default
                FROM user_tab_columns
                WHERE table_name = :table
                ORDER BY column_id
            ");
            $stmt->execute([':table' => strtoupper($table)]);
            $columns = $stmt->fetchAll();
        } elseif ($driver === 'sqlsrv' || $driver === 'mssql') {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.name AS column_name,
                    t.name AS data_type,
                    c.is_nullable,
                    dc.definition AS column_default
                FROM sys.columns c
                INNER JOIN sys.types t ON c.user_type_id = t.user_type_id
                LEFT JOIN sys.default_constraints dc ON c.default_object_id = dc.object_id
                WHERE c.object_id = OBJECT_ID(?)
                ORDER BY c.column_id
            ");
            $stmt->execute([$table]);
            $columns = $stmt->fetchAll();
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }

        return [
            'table' => $table,
            'columns' => $columns,
        ];
    }
}
