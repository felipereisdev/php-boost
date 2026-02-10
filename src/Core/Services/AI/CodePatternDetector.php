<?php

namespace FelipeReisDev\PhpBoost\Core\Services\AI;

class CodePatternDetector
{
    private $basePath;
    private $patterns;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->patterns = [
            'raw_sql' => [
                'pattern' => '/\bDB::raw\(|->raw\(|rawQuery\(/',
                'severity' => 'medium',
                'message' => 'Raw SQL detected. Consider using Eloquent or Query Builder.',
            ],
            'select_all' => [
                'pattern' => '/SELECT\s+\*\s+FROM|->select\(\s*\*\s*\)/',
                'severity' => 'low',
                'message' => 'SELECT * detected. Specify only needed columns for better performance.',
            ],
            'n_plus_one' => [
                'pattern' => '/foreach\s*\([^)]*->get\(\)[^)]*\)/',
                'severity' => 'high',
                'message' => 'Potential N+1 query detected. Consider using eager loading.',
            ],
            'missing_type_hints' => [
                'pattern' => '/function\s+\w+\s*\([^:)]*\)\s*\{/',
                'severity' => 'low',
                'message' => 'Missing type hints. Add parameter and return types for better code quality.',
            ],
            'fat_controller' => [
                'pattern' => '/class\s+\w+Controller.*\{[\s\S]{500,}/',
                'severity' => 'medium',
                'message' => 'Fat controller detected. Consider moving logic to services.',
            ],
            'hard_coded_credentials' => [
                'pattern' => '/password\s*=\s*["\']|api_key\s*=\s*["\']|secret\s*=\s*["\']/',
                'severity' => 'critical',
                'message' => 'Hard-coded credentials detected. Use environment variables.',
            ],
            'unused_use_statements' => [
                'pattern' => '/^use\s+[^;]+;$/m',
                'severity' => 'low',
                'message' => 'Unused use statement detected. Clean up imports.',
            ],
            'god_object' => [
                'pattern' => '/class\s+\w+.*\{[\s\S]{2000,}/',
                'severity' => 'high',
                'message' => 'God object detected. Consider breaking into smaller classes.',
            ],
        ];
    }

    public function scan()
    {
        $results = [
            'patterns_found' => [],
            'files_analyzed' => 0,
            'total_issues' => 0,
        ];

        $phpFiles = $this->findPhpFiles($this->basePath);
        $results['files_analyzed'] = count($phpFiles);

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $relativeFile = str_replace($this->basePath . '/', '', $file);

            foreach ($this->patterns as $patternName => $patternConfig) {
                if (preg_match_all($patternConfig['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $lineNumbers = $this->getLineNumbers($content, $matches[0]);
                    
                    if (!isset($results['patterns_found'][$patternName])) {
                        $results['patterns_found'][$patternName] = [
                            'count' => 0,
                            'severity' => $patternConfig['severity'],
                            'message' => $patternConfig['message'],
                            'occurrences' => [],
                        ];
                    }

                    foreach ($lineNumbers as $line) {
                        $results['patterns_found'][$patternName]['occurrences'][] = [
                            'file' => $relativeFile,
                            'line' => $line,
                        ];
                        $results['patterns_found'][$patternName]['count']++;
                        $results['total_issues']++;
                    }
                }
            }
        }

        return $results;
    }

    public function generateSuggestions($scanResults)
    {
        $suggestions = [];

        foreach ($scanResults['patterns_found'] as $patternName => $data) {
            $suggestion = $this->getSuggestionForPattern($patternName, $data);
            if ($suggestion) {
                $suggestions[] = $suggestion;
            }
        }

        return $suggestions;
    }

    private function getSuggestionForPattern($patternName, $data)
    {
        $suggestions = [
            'raw_sql' => [
                'guideline' => 'Prefer Eloquent over raw SQL',
                'example' => 'Use Model::where()->get() instead of DB::raw()',
                'priority' => 'medium',
            ],
            'select_all' => [
                'guideline' => 'Always specify columns in SELECT queries',
                'example' => 'Model::select(["id", "name"])->get()',
                'priority' => 'low',
            ],
            'n_plus_one' => [
                'guideline' => 'Always eager load relationships',
                'example' => 'Model::with("relation")->get()',
                'priority' => 'high',
            ],
            'missing_type_hints' => [
                'guideline' => 'Use type hints for all parameters and return types',
                'example' => 'function getName(int $id): string',
                'priority' => 'low',
            ],
            'fat_controller' => [
                'guideline' => 'Keep controllers thin, move logic to services',
                'example' => 'Create a service class for business logic',
                'priority' => 'medium',
            ],
            'hard_coded_credentials' => [
                'guideline' => 'Never hard-code credentials',
                'example' => 'Use env("API_KEY") or config("services.api.key")',
                'priority' => 'critical',
            ],
            'god_object' => [
                'guideline' => 'Follow Single Responsibility Principle',
                'example' => 'Break class into smaller, focused classes',
                'priority' => 'high',
            ],
        ];

        if (!isset($suggestions[$patternName])) {
            return null;
        }

        return array_merge($suggestions[$patternName], [
            'pattern' => $patternName,
            'occurrences' => $data['count'],
            'severity' => $data['severity'],
        ]);
    }

    private function findPhpFiles($directory)
    {
        $files = [];
        $excludeDirs = ['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'public'];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();
                $shouldExclude = false;

                foreach ($excludeDirs as $excludeDir) {
                    if (strpos($path, '/' . $excludeDir . '/') !== false) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if (!$shouldExclude) {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    private function getLineNumbers($content, $matches)
    {
        $lines = [];
        foreach ($matches as $match) {
            $offset = $match[1];
            $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
            $lines[] = $lineNumber;
        }
        return $lines;
    }
}
