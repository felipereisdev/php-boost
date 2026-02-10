<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Services\PerformanceProfiler;

class ProfileCommand extends Command
{
    protected $signature = 'boost:profile 
                            {--format=table : Output format (table, json)}
                            {--category= : Filter by category (n_plus_one, cache_opportunity, etc.)}
                            {--min-severity= : Minimum severity (low, medium, high)}
                            {--export= : Export report to file}';

    protected $description = 'Analyze application performance and detect issues';

    public function handle()
    {
        $this->info('PHP Boost - Performance Profiler');
        $this->info('================================');
        $this->newLine();

        $rootPath = base_path();
        
        $this->info('Analyzing application...');
        $this->newLine();

        $profiler = new PerformanceProfiler($rootPath);
        
        $startTime = microtime(true);
        $report = $profiler->analyze();
        $duration = round((microtime(true) - $startTime) * 1000);

        $this->displaySummary($report, $duration);
        
        if ($this->option('format') === 'json') {
            $this->displayJsonReport($report);
        } else {
            $this->displayTableReport($report);
        }
        
        $this->displayRecommendations($report);
        $this->displayScore($report);

        if ($this->option('export')) {
            $this->exportReport($report);
        }

        return 0;
    }

    private function displaySummary($report, $duration)
    {
        $summary = $report['summary'];
        
        $this->info("Analysis completed in {$duration}ms");
        $this->newLine();
        
        $this->line("Total Issues: {$summary['total_issues']}");
        $this->line("  High Severity: {$summary['high_severity']}");
        $this->line("  Medium Severity: {$summary['medium_severity']}");
        $this->line("  Low Severity: {$summary['low_severity']}");
        $this->newLine();
    }

    private function displayTableReport($report)
    {
        $category = $this->option('category');
        $minSeverity = $this->option('min-severity');
        
        foreach ($report['categories'] as $cat => $data) {
            if ($category && $cat !== $category) {
                continue;
            }
            
            if ($data['count'] === 0) {
                continue;
            }
            
            $this->info(ucwords(str_replace('_', ' ', $cat)) . " ({$data['count']})");
            $this->line(str_repeat('-', 80));
            
            $filteredIssues = $this->filterIssues($data['issues'], $minSeverity);
            
            if (empty($filteredIssues)) {
                $this->comment('No issues matching criteria');
                $this->newLine();
                continue;
            }
            
            $tableData = [];
            foreach ($filteredIssues as $issue) {
                $tableData[] = [
                    'File' => $issue['file'],
                    'Line' => $issue['line'],
                    'Type' => $issue['type'],
                    'Severity' => $this->formatSeverity($issue['severity']),
                    'Description' => $issue['description'],
                ];
            }
            
            $this->table(
                ['File', 'Line', 'Type', 'Severity', 'Description'],
                $tableData
            );
            
            $this->newLine();
        }
    }

    private function displayJsonReport($report)
    {
        $category = $this->option('category');
        $minSeverity = $this->option('min-severity');
        
        $filtered = $report;
        
        if ($category) {
            $filtered['categories'] = array_filter(
                $filtered['categories'],
                function($key) use ($category) {
                    return $key === $category;
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        
        if ($minSeverity) {
            foreach ($filtered['categories'] as $cat => $data) {
                $filtered['categories'][$cat]['issues'] = $this->filterIssues(
                    $data['issues'],
                    $minSeverity
                );
            }
        }
        
        $this->line(json_encode($filtered, JSON_PRETTY_PRINT));
    }

    private function displayRecommendations($report)
    {
        if (empty($report['recommendations'])) {
            return;
        }
        
        $this->info('Recommendations');
        $this->line(str_repeat('=', 80));
        $this->newLine();
        
        foreach ($report['recommendations'] as $idx => $rec) {
            $priority = strtoupper($rec['priority']);
            $this->line(($idx + 1) . ". [{$priority}] {$rec['action']}");
            $this->comment("   Impact: {$rec['impact']}");
            $this->newLine();
        }
    }

    private function displayScore($report)
    {
        $score = $report['score'];
        
        $this->info('Performance Score');
        $this->line(str_repeat('=', 80));
        $this->newLine();
        
        $color = $score >= 80 ? 'info' : ($score >= 50 ? 'comment' : 'error');
        $this->{$color}("Score: {$score}/100");
        
        if ($score >= 80) {
            $this->info('✓ Great performance!');
        } elseif ($score >= 50) {
            $this->comment('⚠ Room for improvement');
        } else {
            $this->error('✗ Critical issues detected');
        }
        
        $this->newLine();
    }

    private function exportReport($report)
    {
        $path = $this->option('export');
        
        $content = json_encode($report, JSON_PRETTY_PRINT);
        file_put_contents($path, $content);
        
        $this->info("✓ Report exported to: {$path}");
        $this->newLine();
    }

    private function filterIssues($issues, $minSeverity)
    {
        if (!$minSeverity) {
            return $issues;
        }
        
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3];
        $minLevel = $severityLevels[$minSeverity] ?? 1;
        
        return array_filter($issues, function($issue) use ($severityLevels, $minLevel) {
            $level = $severityLevels[$issue['severity']] ?? 1;
            return $level >= $minLevel;
        });
    }

    private function formatSeverity($severity)
    {
        $colors = [
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
        ];
        
        return ($colors[$severity] ?? '') . ' ' . strtoupper($severity);
    }
}
