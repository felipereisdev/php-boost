<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineValidator;
use FelipeReisDev\PhpBoost\Core\Services\CodeAnalyzer;
use FelipeReisDev\PhpBoost\Core\Services\ProjectHealthScorer;

class HealthCommand extends Command
{
    protected $signature = 'boost:health 
                            {--format=text : Output format (text, json)}
                            {--save : Save health score history}';

    protected $description = 'Calculate project health score';

    public function handle()
    {
        $this->info('PHP Boost - Project Health Score');
        $this->info('=================================');
        $this->newLine();

        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';

        $this->info('Analyzing project health...');
        $this->newLine();

        $inspector = new ProjectInspector($rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $scorer = new ProjectHealthScorer($rootPath, $projectInfo);
        $healthScore = $scorer->calculateScore();

        if ($this->option('save')) {
            $scorer->saveScore($healthScore);
            $this->comment('Health score saved to history');
            $this->newLine();
        }

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($healthScore, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayHealthScore($healthScore);

        return 0;
    }

    private function displayHealthScore($healthScore)
    {
        $overall = $healthScore['overall_score'];

        $this->line('');
        $this->info("Overall Health Score: {$overall}/100");
        $this->newLine();

        $this->displayScoreBar($overall);
        $this->newLine();

        $this->displayGrade($overall);
        $this->newLine();

        $this->displayCategoryScores($healthScore['categories']);
        $this->newLine();

        if (!empty($healthScore['strengths'])) {
            $this->displayStrengths($healthScore['strengths']);
            $this->newLine();
        }

        if (!empty($healthScore['weaknesses'])) {
            $this->displayWeaknesses($healthScore['weaknesses']);
            $this->newLine();
        }

        if (!empty($healthScore['recommendations'])) {
            $this->displayRecommendations($healthScore['recommendations']);
            $this->newLine();
        }

        if (isset($healthScore['history'])) {
            $this->displayTrend($healthScore['history']);
            $this->newLine();
        }

        $this->displayFooter();
    }

    private function displayScoreBar($score)
    {
        $barLength = 50;
        $filledLength = (int) (($score / 100) * $barLength);

        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);

        $color = $this->getScoreColor($score);

        $this->line("<fg={$color}>{$bar}</> {$score}%");
    }

    private function displayGrade($score)
    {
        $grade = $this->getGrade($score);
        $color = $this->getScoreColor($score);
        $description = $this->getGradeDescription($grade);

        $this->line("<fg={$color}>Grade: {$grade}</>");
        $this->line("<fg=gray>{$description}</>");
    }

    private function displayCategoryScores($categories)
    {
        $this->info('Category Breakdown:');
        $this->newLine();

        foreach ($categories as $category => $data) {
            $score = $data['score'];
            $weight = $data['weight'] * 100;
            $icon = $this->getCategoryIcon($category);
            $color = $this->getScoreColor($score);
            $label = $this->formatCategoryLabel($category);

            $this->line(sprintf(
                '  %s <fg=%s>%s: %d/100</> <fg=gray>(weight: %.0f%%)</>',
                $icon,
                $color,
                $label,
                $score,
                $weight
            ));

            if (isset($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $this->line("     <fg=gray>• {$detail}</>");
                }
            }

            $this->newLine();
        }
    }

    private function displayStrengths($strengths)
    {
        $this->line('<fg=green>✓ Strengths:</>');
        $this->newLine();

        foreach ($strengths as $strength) {
            $this->line("  • {$strength}");
        }
    }

    private function displayWeaknesses($weaknesses)
    {
        $this->line('<fg=yellow>⚠ Areas for Improvement:</>');
        $this->newLine();

        foreach ($weaknesses as $weakness) {
            $this->line("  • {$weakness}");
        }
    }

    private function displayRecommendations($recommendations)
    {
        $this->info('Recommended Actions:');
        $this->newLine();

        foreach ($recommendations as $index => $recommendation) {
            $num = $index + 1;
            $priority = $recommendation['priority'];
            $icon = $this->getPriorityIcon($priority);

            $this->line("  {$num}. {$icon} {$recommendation['action']}");

            if (isset($recommendation['impact'])) {
                $this->line("     <fg=gray>Impact: {$recommendation['impact']}</>");
            }

            if (!empty($recommendation['commands'])) {
                foreach ($recommendation['commands'] as $command) {
                    $this->line("     <fg=gray>$ {$command}</>");
                }
            }

            $this->newLine();
        }
    }

    private function displayTrend($history)
    {
        if (count($history) < 2) {
            return;
        }

        $this->info('Health Trend:');
        $this->newLine();

        $latest = end($history);
        $previous = prev($history);

        $change = $latest['score'] - $previous['score'];

        if ($change > 0) {
            $this->line("  <fg=green>↑ +{$change} points</> (improved)");
        } elseif ($change < 0) {
            $this->line("  <fg=red>↓ {$change} points</> (declined)");
        } else {
            $this->line("  <fg=gray>→ No change</>");
        }

        $this->newLine();
        $this->line('  Recent history:');

        foreach (array_slice($history, -5) as $entry) {
            $date = date('Y-m-d H:i', $entry['timestamp']);
            $score = $entry['score'];
            $color = $this->getScoreColor($score);

            $this->line("    <fg=gray>{$date}:</> <fg={$color}>{$score}/100</>");
        }
    }

    private function displayFooter()
    {
        $this->comment('Tips:');
        $this->line('  • Run "php artisan boost:validate" for detailed code analysis');
        $this->line('  • Run "php artisan boost:health --save" to track progress');
        $this->line('  • Use "composer require --dev" to add quality tools');
    }

    private function getGrade($score)
    {
        if ($score >= 90) {
            return 'A';
        } elseif ($score >= 80) {
            return 'B';
        } elseif ($score >= 70) {
            return 'C';
        } elseif ($score >= 60) {
            return 'D';
        } else {
            return 'F';
        }
    }

    private function getGradeDescription($grade)
    {
        $descriptions = [
            'A' => 'Excellent - Production ready with best practices',
            'B' => 'Good - Solid codebase with minor improvements needed',
            'C' => 'Fair - Acceptable but needs attention',
            'D' => 'Poor - Significant issues to address',
            'F' => 'Critical - Major problems require immediate action',
        ];

        return $descriptions[$grade] ?? '';
    }

    private function getScoreColor($score)
    {
        if ($score >= 80) {
            return 'green';
        } elseif ($score >= 60) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    private function getCategoryIcon($category)
    {
        $icons = [
            'code_quality' => '📝',
            'security' => '🔒',
            'performance' => '⚡',
            'testing' => '🧪',
            'documentation' => '📚',
            'dependencies' => '📦',
            'architecture' => '🏗️',
        ];

        return $icons[$category] ?? '•';
    }

    private function getPriorityIcon($priority)
    {
        $icons = [
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
        ];

        return $icons[$priority] ?? '⚪';
    }

    private function formatCategoryLabel($category)
    {
        return ucwords(str_replace('_', ' ', $category));
    }
}
