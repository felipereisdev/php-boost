<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class GuidelineValidator
{
    private $projectRoot;
    private $projectInfo;
    private $violations;
    private $score;

    public function __construct($projectRoot, $projectInfo)
    {
        $this->projectRoot = $projectRoot;
        $this->projectInfo = $projectInfo;
        $this->violations = [];
        $this->score = 0;
    }

    public function validate()
    {
        $results = [
            'php_best_practices' => $this->validatePhpBestPractices(),
            'framework_conventions' => $this->validateFrameworkConventions(),
            'code_style' => $this->validateCodeStyle(),
            'security' => $this->validateSecurity(),
            'performance' => $this->validatePerformance(),
        ];

        $this->calculateScore($results);

        return [
            'score' => $this->score,
            'max_score' => 100,
            'results' => $results,
            'violations' => $this->violations,
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    private function validatePhpBestPractices()
    {
        $score = 100;
        $issues = [];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (strpos($content, 'declare(strict_types=1)') === false) {
                $issues[] = "Missing strict_types declaration in {$file}";
                $score -= 5;
            }

            if (preg_match('/\bSELECT\s+\*/i', $content)) {
                $issues[] = "Using SELECT * in {$file}";
                $this->violations[] = [
                    'file' => $file,
                    'type' => 'performance',
                    'message' => 'Avoid SELECT * queries',
                ];
                $score -= 3;
            }

            if (preg_match('/\$_GET|\$_POST|\$_REQUEST/i', $content)) {
                if (!preg_match('/filter_input|filter_var/i', $content)) {
                    $issues[] = "Unsafe input handling in {$file}";
                    $this->violations[] = [
                        'file' => $file,
                        'type' => 'security',
                        'message' => 'Direct use of superglobals without validation',
                    ];
                    $score -= 10;
                }
            }
        }

        return [
            'score' => max(0, $score),
            'max_score' => 100,
            'issues' => $issues,
        ];
    }

    private function validateFrameworkConventions()
    {
        $framework = strtolower($this->projectInfo['framework']['name']);

        if ($framework === 'standalone') {
            return [
                'score' => 100,
                'max_score' => 100,
                'issues' => [],
            ];
        }

        $score = 100;
        $issues = [];

        if ($framework === 'laravel' || $framework === 'lumen') {
            $controllersPath = $this->projectRoot . '/app/Http/Controllers';

            if (is_dir($controllersPath)) {
                $controllers = glob($controllersPath . '/*.php');

                foreach ($controllers as $controller) {
                    $content = file_get_contents($controller);
                    $lines = substr_count($content, "\n");

                    if ($lines > 200) {
                        $issues[] = "Controller too large: " . basename($controller);
                        $this->violations[] = [
                            'file' => $controller,
                            'type' => 'architecture',
                            'message' => 'Controller exceeds 200 lines - consider using Services',
                        ];
                        $score -= 5;
                    }
                }
            }

            $modelsPath = $this->projectRoot . '/app/Models';

            if (is_dir($modelsPath)) {
                $models = glob($modelsPath . '/*.php');

                foreach ($models as $model) {
                    $content = file_get_contents($model);

                    if (!preg_match('/protected \$fillable|protected \$guarded/', $content)) {
                        $issues[] = "Model missing fillable/guarded: " . basename($model);
                        $this->violations[] = [
                            'file' => $model,
                            'type' => 'security',
                            'message' => 'Model must define $fillable or $guarded',
                        ];
                        $score -= 5;
                    }
                }
            }
        }

        return [
            'score' => max(0, $score),
            'max_score' => 100,
            'issues' => $issues,
        ];
    }

    private function validateCodeStyle()
    {
        $score = 100;
        $issues = [];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match('/\t/', $content)) {
                $issues[] = "Tabs found in {$file} (should use spaces)";
                $score -= 2;
            }

            if (preg_match('/\r\n/', $content)) {
                $issues[] = "Windows line endings in {$file}";
                $score -= 1;
            }

            if (preg_match('/\s+$/', $content)) {
                $issues[] = "Trailing whitespace in {$file}";
                $score -= 1;
            }
        }

        return [
            'score' => max(0, $score),
            'max_score' => 100,
            'issues' => $issues,
        ];
    }

    private function validateSecurity()
    {
        $score = 100;
        $issues = [];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match('/eval\s*\(/', $content)) {
                $issues[] = "Dangerous eval() usage in {$file}";
                $this->violations[] = [
                    'file' => $file,
                    'type' => 'security',
                    'message' => 'Avoid using eval()',
                ];
                $score -= 20;
            }

            if (preg_match('/md5\s*\(.*password/i', $content)) {
                $issues[] = "Weak password hashing in {$file}";
                $this->violations[] = [
                    'file' => $file,
                    'type' => 'security',
                    'message' => 'Use password_hash() instead of md5() for passwords',
                ];
                $score -= 15;
            }

            if (preg_match('/mysql_query|mysqli_query.*\$_(GET|POST|REQUEST)/', $content)) {
                $issues[] = "Potential SQL injection in {$file}";
                $this->violations[] = [
                    'file' => $file,
                    'type' => 'security',
                    'message' => 'Use prepared statements to prevent SQL injection',
                ];
                $score -= 20;
            }
        }

        return [
            'score' => max(0, $score),
            'max_score' => 100,
            'issues' => $issues,
        ];
    }

    private function validatePerformance()
    {
        $score = 100;
        $issues = [];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match_all('/->get\(\)|->all\(\)/', $content, $matches) > 5) {
                $issues[] = "Multiple queries without pagination in {$file}";
                $this->violations[] = [
                    'file' => $file,
                    'type' => 'performance',
                    'message' => 'Consider using pagination for large datasets',
                ];
                $score -= 5;
            }

            if (preg_match('/foreach.*foreach.*foreach/', $content)) {
                $issues[] = "Nested loops (3+ levels) in {$file}";
                $this->violations[] = [
                    'file' => $file,
                    'type' => 'performance',
                    'message' => 'Deep nested loops - consider refactoring',
                ];
                $score -= 5;
            }
        }

        return [
            'score' => max(0, $score),
            'max_score' => 100,
            'issues' => $issues,
        ];
    }

    private function calculateScore($results)
    {
        $totalScore = 0;
        $maxScore = 0;

        foreach ($results as $category => $result) {
            $totalScore += $result['score'];
            $maxScore += $result['max_score'];
        }

        $this->score = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;
    }

    private function generateRecommendations()
    {
        $recommendations = [];

        if ($this->score < 50) {
            $recommendations[] = 'Critical: Project health is below 50%. Immediate action required.';
        }

        $categoryScores = [];

        if ($this->score < 80) {
            $recommendations[] = 'Run phpstan for static analysis';
            $recommendations[] = 'Run php-cs-fixer for code style fixes';
        }

        if (count($this->violations) > 0) {
            $securityViolations = array_filter($this->violations, function ($v) {
                return $v['type'] === 'security';
            });

            if (count($securityViolations) > 0) {
                $recommendations[] = 'Security issues detected - review immediately';
            }
        }

        return $recommendations;
    }

    private function getPhpFiles()
    {
        $files = [];
        $excludeDirs = ['vendor', 'node_modules', 'storage', 'bootstrap/cache'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectRoot)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();
                $shouldExclude = false;

                foreach ($excludeDirs as $dir) {
                    if (strpos($path, '/' . $dir . '/') !== false) {
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
