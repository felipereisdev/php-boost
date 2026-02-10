<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineValidator;
use FelipeReisDev\PhpBoost\Core\Services\CodeAnalyzer;

class ValidateCommand extends Command
{
    protected $signature = 'boost:validate 
                            {--format=text : Output format (text, json)}
                            {--ci : CI mode - exit with error code on low score}
                            {--threshold=70 : Minimum score threshold for CI mode}';

    protected $description = 'Validate code against PHP Boost guidelines';

    public function handle()
    {
        $this->info('PHP Boost - Guideline Validation');
        $this->info('=================================');
        $this->newLine();

        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';

        $this->info('Analyzing project...');
        $this->newLine();

        $inspector = new ProjectInspector($rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $validator = new GuidelineValidator($rootPath, $projectInfo);
        $results = $validator->validate();

        $analyzer = new CodeAnalyzer($rootPath, $projectInfo);
        $codeQuality = $analyzer->analyze();

        $results['code_quality'] = $codeQuality;

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return $this->getExitCode($results);
        }

        $this->displayResults($results);

        return $this->getExitCode($results);
    }

    private function displayResults($results)
    {
        $score = $results['score'];
        $maxScore = $results['max_score'];

        $this->newLine();
        $this->info('Overall Health Score: ' . $score . '/' . $maxScore);
        $this->newLine();

        $this->displayScoreBar($score, $maxScore);
        $this->newLine();

        $this->displayCategoryScores($results['results']);
        $this->newLine();

        if (!empty($results['violations'])) {
            $this->displayViolations($results['violations']);
            $this->newLine();
        }

        if (!empty($results['code_quality']['issues'])) {
            $this->displayCodeQualityIssues($results['code_quality']['issues']);
            $this->newLine();
        }

        if (!empty($results['recommendations'])) {
            $this->displayRecommendations($results['recommendations']);
            $this->newLine();
        }

        $this->displaySummary($results);
    }

    private function displayScoreBar($score, $maxScore)
    {
        $percentage = ($score / $maxScore) * 100;
        $barLength = 50;
        $filledLength = (int) (($percentage / 100) * $barLength);

        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);

        if ($percentage >= 80) {
            $color = 'green';
        } elseif ($percentage >= 60) {
            $color = 'yellow';
        } else {
            $color = 'red';
        }

        $this->line("<fg={$color}>{$bar}</> {$percentage}%");
    }

    private function displayCategoryScores($results)
    {
        $this->info('Category Scores:');
        $this->newLine();

        $categories = [
            'php_best_practices' => 'PHP Best Practices',
            'framework_conventions' => 'Framework Conventions',
            'code_style' => 'Code Style',
            'security' => 'Security',
            'performance' => 'Performance',
        ];

        foreach ($categories as $key => $label) {
            if (isset($results[$key])) {
                $categoryScore = $results[$key]['score'];
                $categoryMax = $results[$key]['max_score'];
                $percentage = $categoryMax > 0 ? ($categoryScore / $categoryMax) * 100 : 0;

                $icon = $this->getScoreIcon($percentage);
                $color = $this->getScoreColor($percentage);

                $this->line(sprintf(
                    '  %s <fg=%s>%s: %d/%d (%.0f%%)</>',
                    $icon,
                    $color,
                    $label,
                    $categoryScore,
                    $categoryMax,
                    $percentage
                ));

                if (!empty($results[$key]['issues'])) {
                    $issueCount = count($results[$key]['issues']);
                    $this->line("     <fg=gray>└─ {$issueCount} issue(s) found</>");
                }
            }
        }
    }

    private function displayViolations($violations)
    {
        $this->error('Violations:');
        $this->newLine();

        $grouped = [];
        foreach ($violations as $violation) {
            $type = $violation['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $violation;
        }

        foreach ($grouped as $type => $items) {
            $this->line("<fg=yellow>  {$type} (" . count($items) . ")</>");

            foreach (array_slice($items, 0, 5) as $item) {
                $file = str_replace(base_path() . '/', '', $item['file']);
                $this->line("    ✗ {$file}");
                $this->line("      <fg=gray>{$item['message']}</>");
            }

            if (count($items) > 5) {
                $remaining = count($items) - 5;
                $this->line("    <fg=gray>... and {$remaining} more</>");
            }

            $this->newLine();
        }
    }

    private function displayCodeQualityIssues($issues)
    {
        $this->info('Code Quality Issues:');
        $this->newLine();

        foreach (array_slice($issues, 0, 10) as $issue) {
            $severity = $issue['severity'] ?? 'info';
            $icon = $this->getSeverityIcon($severity);
            $color = $this->getSeverityColor($severity);

            $this->line(sprintf(
                '  %s <fg=%s>%s</>',
                $icon,
                $color,
                $issue['message']
            ));

            if (isset($issue['file'])) {
                $file = str_replace(base_path() . '/', '', $issue['file']);
                $location = isset($issue['line']) ? ":{$issue['line']}" : '';
                $this->line("     <fg=gray>└─ {$file}{$location}</>");
            }
        }

        if (count($issues) > 10) {
            $remaining = count($issues) - 10;
            $this->newLine();
            $this->line("  <fg=gray>... and {$remaining} more issues</>");
        }
    }

    private function displayRecommendations($recommendations)
    {
        $this->info('Recommendations:');
        $this->newLine();

        foreach ($recommendations as $index => $recommendation) {
            $num = $index + 1;
            $this->line("  {$num}. {$recommendation}");
        }
    }

    private function displaySummary($results)
    {
        $score = $results['score'];

        $this->info('Summary:');
        $this->newLine();

        if ($score >= 80) {
            $this->line('  <fg=green>✓ Excellent!</> Your code follows best practices.');
        } elseif ($score >= 60) {
            $this->line('  <fg=yellow>⚠ Good, but can be improved.</> Review recommendations above.');
        } else {
            $this->line('  <fg=red>✗ Critical issues detected.</> Immediate action required.');
        }

        $violationCount = count($results['violations']);
        if ($violationCount > 0) {
            $this->newLine();
            $this->line("  Total violations: <fg=yellow>{$violationCount}</>");
        }

        $this->newLine();
        $this->comment('Tip: Run "php artisan boost:fix" to auto-fix code style issues');
    }

    private function getScoreIcon($percentage)
    {
        if ($percentage >= 80) {
            return '✓';
        } elseif ($percentage >= 60) {
            return '⚠';
        } else {
            return '✗';
        }
    }

    private function getScoreColor($percentage)
    {
        if ($percentage >= 80) {
            return 'green';
        } elseif ($percentage >= 60) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    private function getSeverityIcon($severity)
    {
        $icons = [
            'critical' => '🔴',
            'error' => '✗',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];

        return $icons[$severity] ?? 'ℹ';
    }

    private function getSeverityColor($severity)
    {
        $colors = [
            'critical' => 'red',
            'error' => 'red',
            'warning' => 'yellow',
            'info' => 'gray',
        ];

        return $colors[$severity] ?? 'gray';
    }

    private function getExitCode($results)
    {
        if (!$this->option('ci')) {
            return 0;
        }

        $threshold = (int) $this->option('threshold');
        $score = $results['score'];

        if ($score < $threshold) {
            $this->error("CI Mode: Score {$score} is below threshold {$threshold}");
            return 1;
        }

        return 0;
    }
}
