<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use PDO;

class DatabaseQuery extends AbstractTool
{
    private $pdo;
    private $connectionError;

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
            $this->connectionError = null;
        } catch (\PDOException $e) {
            $this->pdo = null;
            $this->connectionError = "Database connection failed: " . $e->getMessage();
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
        return 'DatabaseQuery';
    }

    public function getDescription()
    {
        return 'Execute read-only SQL queries (SELECT only)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'SQL query to execute (SELECT only)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of rows to return',
                    'default' => 100,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments)
    {
        if (!$this->pdo) {
            throw new \RuntimeException($this->connectionError ?: 'Database not configured');
        }

        $this->validateArguments($arguments, ['query']);

        $query = trim($arguments['query']);
        $limit = $arguments['limit'] ?? 100;

        if (!str_starts_with(strtoupper($query), 'SELECT')) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed');
        }

        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE)\b/i', $query)) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed');
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) > $limit) {
                $results = array_slice($results, 0, $limit);
            }

            return [
                'rows' => $results,
                'count' => count($results),
            ];
        } catch (\PDOException $e) {
            throw new \RuntimeException("Query failed: " . $e->getMessage());
        }
    }
}
