<?php

namespace FelipeReisDev\PhpBoost\Core\Services\AI;

class PatternLearningSystem
{
    private $basePath;
    private $storagePath;
    private $patterns;

    public function __construct($basePath, $storagePath = null)
    {
        $this->basePath = $basePath;
        $this->storagePath = $storagePath ?: $basePath . '/.php-boost/learned-patterns.json';
        $this->patterns = $this->loadLearnedPatterns();
    }

    public function learnFromCodebase()
    {
        $learnings = [
            'naming_conventions' => $this->learnNamingConventions(),
            'code_style' => $this->learnCodeStyle(),
            'architecture_patterns' => $this->learnArchitecturePatterns(),
            'common_practices' => $this->learnCommonPractices(),
        ];

        $this->patterns = array_merge($this->patterns, $learnings);
        $this->saveLearnedPatterns();

        return $learnings;
    }

    public function analyzeCommitHistory()
    {
        if (!is_dir($this->basePath . '/.git')) {
            return ['error' => 'Not a git repository'];
        }

        $commitPatterns = [
            'commit_frequency' => $this->analyzeCommitFrequency(),
            'common_changes' => $this->analyzeCommonChanges(),
            'team_style' => $this->analyzeTeamStyle(),
        ];

        return $commitPatterns;
    }

    public function adaptGuidelines($currentGuidelines)
    {
        $adaptations = [];

        if (isset($this->patterns['naming_conventions']['method_naming'])) {
            $conventionStyle = $this->patterns['naming_conventions']['method_naming'];
            $adaptations[] = [
                'type' => 'naming',
                'guideline' => "Follow {$conventionStyle} naming for methods",
                'confidence' => 0.85,
            ];
        }

        if (isset($this->patterns['architecture_patterns']['service_layer']) && 
            $this->patterns['architecture_patterns']['service_layer']['usage'] > 70) {
            $adaptations[] = [
                'type' => 'architecture',
                'guideline' => 'Use service layer pattern for business logic',
                'confidence' => 0.9,
            ];
        }

        if (isset($this->patterns['code_style']['return_early']) &&
            $this->patterns['code_style']['return_early']['prevalence'] > 60) {
            $adaptations[] = [
                'type' => 'style',
                'guideline' => 'Prefer early returns over nested conditions',
                'confidence' => 0.8,
            ];
        }

        return $adaptations;
    }

    private function learnNamingConventions()
    {
        $conventions = [
            'method_naming' => 'camelCase',
            'class_naming' => 'PascalCase',
            'variable_naming' => 'camelCase',
        ];

        $phpFiles = $this->findPhpFiles($this->basePath);
        $methodNames = [];
        $classNames = [];
        $variableNames = [];

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);

            preg_match_all('/function\s+(\w+)\s*\(/', $content, $methods);
            $methodNames = array_merge($methodNames, $methods[1]);

            preg_match_all('/class\s+(\w+)/', $content, $classes);
            $classNames = array_merge($classNames, $classes[1]);

            preg_match_all('/\$(\w+)\s*=/', $content, $variables);
            $variableNames = array_merge($variableNames, $variables[1]);
        }

        if (count($methodNames) > 0) {
            $camelCaseCount = 0;
            $snake_caseCount = 0;
            foreach ($methodNames as $name) {
                if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name)) {
                    $camelCaseCount++;
                } elseif (preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                    $snake_caseCount++;
                }
            }
            $conventions['method_naming'] = $camelCaseCount > $snake_caseCount ? 'camelCase' : 'snake_case';
        }

        return $conventions;
    }

    private function learnCodeStyle()
    {
        $styles = [
            'return_early' => ['count' => 0, 'total' => 0, 'prevalence' => 0],
            'type_hints' => ['count' => 0, 'total' => 0, 'prevalence' => 0],
            'doc_blocks' => ['count' => 0, 'total' => 0, 'prevalence' => 0],
        ];

        $phpFiles = $this->findPhpFiles($this->basePath);

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);

            preg_match_all('/function\s+\w+\s*\([^)]*\)\s*:\s*\w+/', $content, $returnTypes);
            $styles['type_hints']['count'] += count($returnTypes[0]);

            preg_match_all('/\/\*\*[\s\S]*?\*\/\s*(?:public|private|protected)\s+function/', $content, $docBlocks);
            $styles['doc_blocks']['count'] += count($docBlocks[0]);

            preg_match_all('/function\s+\w+/', $content, $functions);
            $totalFunctions = count($functions[0]);
            $styles['type_hints']['total'] += $totalFunctions;
            $styles['doc_blocks']['total'] += $totalFunctions;

            preg_match_all('/if\s*\([^)]+\)\s*\{\s*return/', $content, $earlyReturns);
            $styles['return_early']['count'] += count($earlyReturns[0]);
            $styles['return_early']['total'] += $totalFunctions;
        }

        foreach ($styles as $key => $data) {
            if ($data['total'] > 0) {
                $styles[$key]['prevalence'] = round(($data['count'] / $data['total']) * 100, 2);
            }
        }

        return $styles;
    }

    private function learnArchitecturePatterns()
    {
        $patterns = [
            'service_layer' => ['found' => false, 'usage' => 0],
            'repository_pattern' => ['found' => false, 'usage' => 0],
            'dto_pattern' => ['found' => false, 'usage' => 0],
        ];

        $serviceDir = $this->basePath . '/app/Services';
        $repositoryDir = $this->basePath . '/app/Repositories';
        $dtoDir = $this->basePath . '/app/DTO';

        if (is_dir($serviceDir)) {
            $serviceFiles = glob($serviceDir . '/*.php');
            $patterns['service_layer']['found'] = true;
            $patterns['service_layer']['usage'] = count($serviceFiles) * 10;
        }

        if (is_dir($repositoryDir)) {
            $repositoryFiles = glob($repositoryDir . '/*.php');
            $patterns['repository_pattern']['found'] = true;
            $patterns['repository_pattern']['usage'] = count($repositoryFiles) * 10;
        }

        if (is_dir($dtoDir)) {
            $dtoFiles = glob($dtoDir . '/*.php');
            $patterns['dto_pattern']['found'] = true;
            $patterns['dto_pattern']['usage'] = count($dtoFiles) * 10;
        }

        return $patterns;
    }

    private function learnCommonPractices()
    {
        $practices = [
            'validation' => ['form_requests' => 0, 'inline_validation' => 0],
            'testing' => ['has_tests' => false, 'coverage' => 0],
            'logging' => ['uses_logging' => false, 'log_count' => 0],
        ];

        $phpFiles = $this->findPhpFiles($this->basePath);

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);

            if (strpos($content, 'FormRequest') !== false) {
                $practices['validation']['form_requests']++;
            }

            if (preg_match('/->validate\(/', $content)) {
                $practices['validation']['inline_validation']++;
            }

            if (preg_match('/Log::/', $content) || preg_match('/logger\(/', $content)) {
                $practices['logging']['uses_logging'] = true;
                $practices['logging']['log_count']++;
            }
        }

        $testDir = $this->basePath . '/tests';
        if (is_dir($testDir)) {
            $practices['testing']['has_tests'] = true;
        }

        return $practices;
    }

    private function analyzeCommitFrequency()
    {
        $gitLog = shell_exec('cd ' . escapeshellarg($this->basePath) . ' && git log --pretty=format:"%ai" --since="3 months ago" 2>/dev/null');
        if (!$gitLog) {
            return ['error' => 'Could not read git log'];
        }

        $commits = explode("\n", trim($gitLog));
        return [
            'total_commits' => count($commits),
            'average_per_week' => round(count($commits) / 12, 2),
        ];
    }

    private function analyzeCommonChanges()
    {
        $gitLog = shell_exec('cd ' . escapeshellarg($this->basePath) . ' && git log --name-only --pretty=format: --since="3 months ago" 2>/dev/null');
        if (!$gitLog) {
            return ['error' => 'Could not read git log'];
        }

        $files = array_filter(explode("\n", trim($gitLog)));
        $fileChanges = array_count_values($files);
        arsort($fileChanges);

        return array_slice($fileChanges, 0, 10, true);
    }

    private function analyzeTeamStyle()
    {
        $gitLog = shell_exec('cd ' . escapeshellarg($this->basePath) . ' && git log --pretty=format:"%s" --since="3 months ago" 2>/dev/null');
        if (!$gitLog) {
            return ['error' => 'Could not read git log'];
        }

        $messages = explode("\n", trim($gitLog));
        $patterns = [
            'conventional' => 0,
            'imperative' => 0,
            'descriptive' => 0,
        ];

        foreach ($messages as $message) {
            if (preg_match('/^(feat|fix|docs|style|refactor|test|chore)(\(.+\))?:/', $message)) {
                $patterns['conventional']++;
            } elseif (preg_match('/^[A-Z][a-z]+/', $message)) {
                $patterns['imperative']++;
            } else {
                $patterns['descriptive']++;
            }
        }

        return $patterns;
    }

    private function loadLearnedPatterns()
    {
        if (!file_exists($this->storagePath)) {
            return [];
        }

        $content = file_get_contents($this->storagePath);
        return json_decode($content, true) ?: [];
    }

    private function saveLearnedPatterns()
    {
        $dir = dirname($this->storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->storagePath, json_encode($this->patterns, JSON_PRETTY_PRINT));
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
}
