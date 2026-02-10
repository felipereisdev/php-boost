<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class DriverCapabilities
{
    public function normalize($driver)
    {
        $value = strtolower((string) $driver);

        if ($value === 'mssql') {
            return 'sqlsrv';
        }

        if ($value === 'oci') {
            return 'oracle';
        }

        return $value;
    }

    public function supportsExplainJson($driver)
    {
        $driver = $this->normalize($driver);

        return in_array($driver, ['pgsql', 'mysql'], true);
    }

    public function supportsAnalyzeBuffers($driver)
    {
        return $this->normalize($driver) === 'pgsql';
    }

    public function defaultPort($driver)
    {
        $driver = $this->normalize($driver);

        $ports = [
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlite' => 0,
            'sqlsrv' => 1433,
            'oracle' => 1521,
        ];

        return isset($ports[$driver]) ? $ports[$driver] : 3306;
    }

    public function sanitizeIdentifier($name)
    {
        if (!is_string($name) || $name === '') {
            throw new \InvalidArgumentException('Identifier cannot be empty');
        }

        if (!preg_match('/^[A-Za-z0-9_\.]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid identifier format');
        }

        return $name;
    }

    public function isReadOnlySql($query)
    {
        $sql = strtoupper(trim((string) $query));

        if ($sql === '') {
            return false;
        }

        if (!str_starts_with($sql, 'SELECT') && !str_starts_with($sql, 'WITH')) {
            return false;
        }

        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE|MERGE|GRANT|REVOKE)\b/i', $sql)) {
            return false;
        }

        return true;
    }
}
