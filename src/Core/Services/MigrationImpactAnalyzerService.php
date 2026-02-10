<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class MigrationImpactAnalyzerService
{
    public function pendingMigrations($basePath)
    {
        $migrationDir = rtrim($basePath, '/') . '/database/migrations';
        if (!is_dir($migrationDir)) {
            return [];
        }

        $files = glob($migrationDir . '/*.php');
        if (!$files) {
            return [];
        }

        sort($files);
        $pending = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $pending[] = [
                'name' => $name,
                'path' => $file,
                'analysis' => $this->analyzeMigrationFile($file),
            ];
        }

        return $pending;
    }

    public function analyzeMigrationFile($file)
    {
        $content = @file_get_contents($file);
        if ($content === false) {
            return [
                'operations' => [],
                'operation_details' => [],
                'risks' => ['Unable to read migration file'],
                'parse_mode' => 'unreadable',
            ];
        }

        $operationPatterns = [
            'create_table' => '/Schema::create\s*\(/',
            'drop_table' => '/Schema::drop(?:IfExists)?\s*\(/',
            'rename_table' => '/Schema::rename\s*\(/',
            'drop_column' => '/->dropColumn\s*\(/',
            'rename_column' => '/->renameColumn\s*\(/',
            'change_column' => '/->change\s*\(/',
            'add_index' => '/->(index|unique|fullText|spatialIndex)\s*\(/',
            'drop_index' => '/->drop(Index|Unique|FullText|SpatialIndex|Primary)\s*\(/',
            'add_foreign' => '/->foreign\s*\(/',
            'foreign_id' => '/->foreignId\s*\(/',
            'drop_foreign' => '/->dropForeign\s*\(/',
            'raw_sql' => '/DB::statement\s*\(/',
        ];

        $operations = [];
        foreach ($operationPatterns as $type => $regex) {
            if (preg_match($regex, $content)) {
                $operations[] = $type;
            }
        }

        $operationDetails = $this->collectOperationDetails($content);
        $risks = $this->collectRiskSignals($operations, $operationDetails);

        return [
            'operations' => array_values(array_unique($operations)),
            'operation_details' => $operationDetails,
            'risks' => array_values(array_unique($risks)),
            'parse_mode' => 'heuristic',
        ];
    }

    public function previewImpacts(array $pendingMigrations)
    {
        $impacts = [];

        foreach ($pendingMigrations as $migration) {
            $analysis = $migration['analysis'];
            foreach ($analysis['operations'] as $operation) {
                $profile = $this->riskProfileForOperation($operation, $analysis);
                $impacts[] = [
                    'migration' => $migration['name'],
                    'operation' => $operation,
                    'lock_risk' => $profile['lock_risk'],
                    'rewrite_risk' => $profile['rewrite_risk'],
                    'rollback_risk' => $profile['rollback_risk'],
                    'missing_index_hint' => $profile['missing_index_hint'],
                    'notes' => $profile['notes'],
                ];
            }
        }

        return $impacts;
    }

    public function toDrift(array $schemaSnapshot, array $pendingMigrations, array $options = [])
    {
        $drift = [];

        foreach ($pendingMigrations as $migration) {
            $name = $migration['name'];
            $analysis = $migration['analysis'];

            foreach ($analysis['operations'] as $operation) {
                if ($operation === 'create_table') {
                    $drift[] = [
                        'type' => 'missing_table',
                        'migration' => $name,
                        'message' => 'Migration may create new table not present yet',
                    ];
                }

                if ($operation === 'drop_column') {
                    $drift[] = [
                        'type' => 'potential_column_drop',
                        'migration' => $name,
                        'message' => 'Pending migration includes dropColumn',
                    ];
                }

                if ($operation === 'change_column' || $operation === 'rename_column') {
                    $drift[] = [
                        'type' => 'type_mismatch_possible',
                        'migration' => $name,
                        'message' => 'Pending migration may alter or rename column types',
                    ];
                }

                if ($operation === 'drop_index') {
                    $drift[] = [
                        'type' => 'missing_index_possible',
                        'migration' => $name,
                        'message' => 'Pending migration drops an index',
                    ];
                }

                if ($operation === 'raw_sql') {
                    $drift[] = [
                        'type' => 'raw_sql_uncertain',
                        'migration' => $name,
                        'message' => 'Raw SQL detected; drift inference may be partial',
                    ];
                }
            }
        }

        return $drift;
    }

    public function riskScore(array $drift)
    {
        $score = 0;
        foreach ($drift as $item) {
            if (strpos($item['type'], 'drop') !== false) {
                $score += 25;
            } elseif (strpos($item['type'], 'type_mismatch') !== false) {
                $score += 18;
            } elseif (strpos($item['type'], 'missing_index') !== false) {
                $score += 12;
            } elseif (strpos($item['type'], 'raw_sql') !== false) {
                $score += 16;
            } else {
                $score += 8;
            }
        }

        if ($score > 100) {
            $score = 100;
        }

        return $score;
    }

    private function collectOperationDetails($content)
    {
        $details = [
            'tables' => [],
            'columns' => [],
            'foreign_ids_without_index_hint' => [],
            'uses_transactions' => strpos($content, 'DB::transaction(') !== false,
        ];

        if (preg_match_all('/Schema::(?:create|table|drop|dropIfExists|rename)\s*\(\s*[\"\']([^\"\']+)[\"\']/', $content, $matches)) {
            $details['tables'] = array_values(array_unique($matches[1]));
        }

        if (preg_match_all('/->(\w+)\s*\(\s*[\"\']([^\"\']+)[\"\']/', $content, $columnMatches, PREG_SET_ORDER)) {
            foreach ($columnMatches as $match) {
                $method = $match[1];
                $column = $match[2];
                $details['columns'][] = ['method' => $method, 'column' => $column];
            }
        }

        if (preg_match_all('/->foreignId\s*\(\s*[\"\']([^\"\']+)[\"\']\s*\)(?!\s*->(?:constrained|index|references))/', $content, $foreignMatches)) {
            $details['foreign_ids_without_index_hint'] = array_values(array_unique($foreignMatches[1]));
        }

        return $details;
    }

    private function collectRiskSignals(array $operations, array $operationDetails)
    {
        $risks = [];

        if (in_array('drop_table', $operations, true) || in_array('drop_column', $operations, true)) {
            $risks[] = 'destructive_change';
        }

        if (in_array('change_column', $operations, true) || in_array('rename_column', $operations, true)) {
            $risks[] = 'table_rewrite_possible';
        }

        if (in_array('drop_index', $operations, true)) {
            $risks[] = 'index_regression_possible';
        }

        if (in_array('raw_sql', $operations, true)) {
            $risks[] = 'raw_sql_uncertain';
        }

        if (!empty($operationDetails['foreign_ids_without_index_hint'])) {
            $risks[] = 'foreign_id_without_index_hint';
        }

        return $risks;
    }

    private function riskProfileForOperation($operation, array $analysis)
    {
        $highLock = ['drop_table', 'drop_column', 'change_column', 'rename_column', 'rename_table'];
        $highRollback = ['drop_table', 'drop_column', 'raw_sql'];
        $rewriteOps = ['change_column', 'rename_column', 'rename_table'];

        $notes = [];
        $missingIndexHint = null;

        if (!empty($analysis['operation_details']['foreign_ids_without_index_hint'])) {
            $missingIndexHint = 'foreignId without constrained()/index(): ' . implode(', ', $analysis['operation_details']['foreign_ids_without_index_hint']);
        }

        if (in_array('raw_sql', $analysis['operations'], true)) {
            $notes[] = 'Raw SQL migration detected; manual review recommended';
        }

        if (empty($analysis['operation_details']['uses_transactions'])) {
            $notes[] = 'Migration does not appear to use explicit transaction';
        }

        return [
            'lock_risk' => in_array($operation, $highLock, true) ? 'high' : 'low',
            'rewrite_risk' => in_array($operation, $rewriteOps, true) ? 'high' : 'low',
            'rollback_risk' => in_array($operation, $highRollback, true) ? 'high' : 'medium',
            'missing_index_hint' => $missingIndexHint,
            'notes' => $notes,
        ];
    }
}
