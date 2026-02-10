<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class ProjectHealthScorer
{
    private $projectRoot;
    private $projectInfo;
    private $historyFile;

    public function __construct($projectRoot, $projectInfo)
    {
        $this->projectRoot = $projectRoot;
        $this->projectInfo = $projectInfo;
        $this->historyFile = $projectRoot . '/.php-boost/health-history.json';
    }

    public function calculateScore()
    {
        $categories = [
            'code_quality' => [
                'weight' => 0.25,
                'calculator' => 'calculateCodeQuality',
            ],
            'security' => [
                'weight' => 0.20,
                'calculator' => 'calculateSecurity',
            ],
            'performance' => [
                'weight' => 0.15,
                'calculator' => 'calculatePerformance',
            ],
            'testing' => [
                'weight' => 0.15,
                'calculator' => 'calculateTesting',
            ],
            'documentation' => [
                'weight' => 0.10,
                'calculator' => 'calculateDocumentation',
            ],
            'dependencies' => [
                'weight' => 0.10,
                'calculator' => 'calculateDependencies',
            ],
            'architecture' => [
                'weight' => 0.05,
                'calculator' => 'calculateArchitecture',
            ],
        ];

        $categoryScores = [];
        $totalScore = 0;

        foreach ($categories as $category => $config) {
            $calculator = $config['calculator'];
            $result = $this->{$calculator}();

            $categoryScores[$category] = [
                'score' => $result['score'],
                'weight' => $config['weight'],
                'weighted_score' => $result['score'] * $config['weight'],
                'details' => $result['details'] ?? [],
            ];

            $totalScore += $result['score'] * $config['weight'];
        }

        $overallScore = (int) round($totalScore);

        $strengths = $this->identifyStrengths($categoryScores);
        $weaknesses = $this->identifyWeaknesses($categoryScores);
        $recommendations = $this->generateRecommendations($categoryScores);

        return [
            'overall_score' => $overallScore,
            'categories' => $categoryScores,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'recommendations' => $recommendations,
            'timestamp' => time(),
            'history' => $this->loadHistory(),
        ];
    }

    public function saveScore($healthScore)
    {
        $history = $this->loadHistory();

        $history[] = [
            'score' => $healthScore['overall_score'],
            'timestamp' => $healthScore['timestamp'],
        ];

        $history = array_slice($history, -30);

        $dir = dirname($this->historyFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    private function calculateCodeQuality()
    {
        $score = 100;
        $details = [];

        $validator = new GuidelineValidator($this->projectRoot, $this->projectInfo);
        $validationResult = $validator->validate();

        $validationScore = $validationResult['score'];
        $score = $validationScore;

        if ($validationScore >= 90) {
            $details[] = 'Excellent code quality';
        } elseif ($validationScore >= 70) {
            $details[] = 'Good code quality with room for improvement';
        } else {
            $details[] = 'Code quality needs significant improvement';
        }

        if (!empty($validationResult['violations'])) {
            $count = count($validationResult['violations']);
            $details[] = "{$count} violations detected";
        }

        return [
            'score' => $score,
            'details' => $details,
        ];
    }

    private function calculateSecurity()
    {
        $score = 100;
        $details = [];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (preg_match('/eval\s*\(/', $content)) {
                $score -= 20;
                $details[] = 'Critical: eval() usage detected';
                break;
            }
        }

        $hasSecurityPackage = $this->hasPackage('security');
        if (!$hasSecurityPackage) {
            $score -= 10;
            $details[] = 'No security packages detected';
        } else {
            $details[] = 'Security packages installed';
        }

        if ($score >= 90) {
            $details[] = 'Strong security posture';
        }

        return [
            'score' => max(0, $score),
            'details' => $details,
        ];
    }

    private function calculatePerformance()
    {
        $score = 80;
        $details = [];

        $hasCachePackage = $this->hasPackage('cache');
        if ($hasCachePackage) {
            $score += 10;
            $details[] = 'Caching mechanism in place';
        } else {
            $details[] = 'No caching detected';
        }

        $hasQueuePackage = $this->hasPackage('queue');
        if ($hasQueuePackage) {
            $score += 10;
            $details[] = 'Queue system configured';
        }

        return [
            'score' => min(100, $score),
            'details' => $details,
        ];
    }

    private function calculateTesting()
    {
        $score = 0;
        $details = [];

        $testCount = $this->projectInfo['tests']['count'] ?? 0;

        if ($testCount === 0) {
            $details[] = 'No tests found';
        } elseif ($testCount < 10) {
            $score = 30;
            $details[] = "Limited test coverage ({$testCount} tests)";
        } elseif ($testCount < 50) {
            $score = 60;
            $details[] = "Moderate test coverage ({$testCount} tests)";
        } else {
            $score = 90;
            $details[] = "Good test coverage ({$testCount} tests)";
        }

        if (!empty($this->projectInfo['tests']['framework'])) {
            $score += 10;
            $details[] = 'Test framework: ' . $this->projectInfo['tests']['framework'];
        }

        return [
            'score' => min(100, $score),
            'details' => $details,
        ];
    }

    private function calculateDocumentation()
    {
        $score = 50;
        $details = [];

        $hasReadme = file_exists($this->projectRoot . '/README.md');
        if ($hasReadme) {
            $score += 20;
            $details[] = 'README.md present';
        } else {
            $details[] = 'README.md missing';
        }

        $hasClaudeMd = file_exists($this->projectRoot . '/CLAUDE.md');
        $hasAgentsMd = file_exists($this->projectRoot . '/AGENTS.md');

        if ($hasClaudeMd || $hasAgentsMd) {
            $score += 30;
            $details[] = 'AI guidelines present';
        } else {
            $details[] = 'AI guidelines missing';
        }

        return [
            'score' => $score,
            'details' => $details,
        ];
    }

    private function calculateDependencies()
    {
        $score = 100;
        $details = [];

        if (!file_exists($this->projectRoot . '/composer.lock')) {
            $score -= 30;
            $details[] = 'composer.lock missing';
        } else {
            $details[] = 'Dependencies locked';
        }

        $packages = $this->projectInfo['packages'] ?? [];
        $outdatedCount = 0;

        if ($outdatedCount > 10) {
            $score -= 30;
            $details[] = 'Many outdated dependencies';
        } elseif ($outdatedCount > 5) {
            $score -= 15;
            $details[] = 'Some outdated dependencies';
        } else {
            $details[] = 'Dependencies up to date';
        }

        return [
            'score' => max(0, $score),
            'details' => $details,
        ];
    }

    private function calculateArchitecture()
    {
        $score = 70;
        $details = [];

        $framework = $this->projectInfo['framework']['name'] ?? 'standalone';

        if ($framework !== 'standalone') {
            $score += 15;
            $details[] = 'Using modern framework: ' . $framework;
        }

        $hasServices = is_dir($this->projectRoot . '/app/Services');
        $hasRepositories = is_dir($this->projectRoot . '/app/Repositories');

        if ($hasServices || $hasRepositories) {
            $score += 15;
            $details[] = 'Clean architecture patterns detected';
        } else {
            $details[] = 'Consider implementing service layer';
        }

        return [
            'score' => min(100, $score),
            'details' => $details,
        ];
    }

    private function identifyStrengths($categoryScores)
    {
        $strengths = [];

        foreach ($categoryScores as $category => $data) {
            if ($data['score'] >= 85) {
                $label = ucwords(str_replace('_', ' ', $category));
                $strengths[] = "{$label} is excellent ({$data['score']}/100)";
            }
        }

        return $strengths;
    }

    private function identifyWeaknesses($categoryScores)
    {
        $weaknesses = [];

        foreach ($categoryScores as $category => $data) {
            if ($data['score'] < 60) {
                $label = ucwords(str_replace('_', ' ', $category));
                $weaknesses[] = "{$label} needs improvement ({$data['score']}/100)";
            }
        }

        return $weaknesses;
    }

    private function generateRecommendations($categoryScores)
    {
        $recommendations = [];

        foreach ($categoryScores as $category => $data) {
            if ($data['score'] < 60) {
                $priority = $data['score'] < 40 ? 'critical' : 'high';
            } elseif ($data['score'] < 80) {
                $priority = 'medium';
            } else {
                continue;
            }

            $recommendation = $this->getRecommendationForCategory($category, $data['score']);

            if ($recommendation) {
                $recommendation['priority'] = $priority;
                $recommendations[] = $recommendation;
            }
        }

        usort($recommendations, function ($a, $b) {
            $priorities = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return $priorities[$a['priority']] - $priorities[$b['priority']];
        });

        return $recommendations;
    }

    private function getRecommendationForCategory($category, $score)
    {
        $recommendations = [
            'testing' => [
                'action' => 'Add comprehensive test coverage',
                'impact' => 'Prevents bugs and regressions',
                'commands' => ['composer require --dev phpunit/phpunit', 'php artisan test'],
            ],
            'security' => [
                'action' => 'Implement security best practices',
                'impact' => 'Protects against vulnerabilities',
                'commands' => ['composer require --dev roave/security-advisories:dev-latest'],
            ],
            'documentation' => [
                'action' => 'Improve project documentation',
                'impact' => 'Easier onboarding and maintenance',
                'commands' => ['php artisan boost:install'],
            ],
            'code_quality' => [
                'action' => 'Run code quality tools',
                'impact' => 'Cleaner, more maintainable code',
                'commands' => ['Use MCP tool BoostValidate', 'composer require --dev phpstan/phpstan'],
            ],
        ];

        return $recommendations[$category] ?? null;
    }

    private function hasPackage($type)
    {
        $packages = $this->projectInfo['packages'] ?? [];

        $typeMap = [
            'security' => ['sanctum', 'passport', 'fortify'],
            'cache' => ['redis', 'memcached', 'cache'],
            'queue' => ['horizon', 'queue'],
        ];

        if (!isset($typeMap[$type])) {
            return false;
        }

        foreach ($packages as $package) {
            foreach ($typeMap[$type] as $keyword) {
                if (strpos(strtolower($package), $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getPhpFiles()
    {
        $files = [];
        $excludeDirs = ['vendor', 'node_modules', 'storage', 'bootstrap/cache'];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->projectRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
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

                if (count($files) > 100) {
                    break;
                }
            }
        } catch (\Exception $e) {
        }

        return $files;
    }

    private function loadHistory()
    {
        if (!file_exists($this->historyFile)) {
            return [];
        }

        $content = file_get_contents($this->historyFile);
        return json_decode($content, true) ?: [];
    }
}
