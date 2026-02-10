<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use FelipeReisDev\PhpBoost\Core\Services\AI\GuidelineRecommender;
use FelipeReisDev\PhpBoost\Core\Services\AI\PatternLearningSystem;
use Illuminate\Console\Command;

class AnalyzeCommand extends Command
{
    protected $signature = 'boost:analyze 
                            {--learn : Learn patterns from codebase}
                            {--suggest : Generate guideline suggestions}
                            {--export= : Export guidelines to file}
                            {--format=text : Output format (text, json)}';

    protected $description = 'AI-powered codebase analysis and guideline suggestions';

    public function handle()
    {
        $this->info('🤖 PHP Boost AI Analyzer');
        $this->info('Analyzing your codebase...');
        $this->newLine();

        $basePath = base_path();
        $learn = $this->option('learn');
        $suggest = $this->option('suggest');
        $export = $this->option('export');
        $format = $this->option('format');

        if ($learn) {
            $this->learnPatterns($basePath);
        }

        if ($suggest || (!$learn && !$export)) {
            $this->generateSuggestions($basePath, $format, $export);
        }

        $this->newLine();
        $this->info('✓ Analysis complete!');

        return 0;
    }

    private function learnPatterns($basePath)
    {
        $this->info('📚 Learning patterns from codebase...');
        $this->newLine();

        $learningSystem = new PatternLearningSystem($basePath);
        
        $learnings = $learningSystem->learnFromCodebase();
        
        $this->info('✓ Naming Conventions:');
        foreach ($learnings['naming_conventions'] as $key => $value) {
            $this->line("  - {$key}: {$value}");
        }
        $this->newLine();

        $this->info('✓ Code Style:');
        foreach ($learnings['code_style'] as $key => $data) {
            if (is_array($data) && isset($data['prevalence'])) {
                $this->line("  - {$key}: {$data['prevalence']}%");
            }
        }
        $this->newLine();

        $this->info('✓ Architecture Patterns:');
        foreach ($learnings['architecture_patterns'] as $key => $data) {
            $status = $data['found'] ? '✓' : '✗';
            $this->line("  {$status} {$key}: " . ($data['found'] ? "{$data['usage']}% usage" : 'not found'));
        }
        $this->newLine();

        if (is_dir($basePath . '/.git')) {
            $this->info('📊 Analyzing commit history...');
            $commitAnalysis = $learningSystem->analyzeCommitHistory();
            
            if (isset($commitAnalysis['commit_frequency']['total_commits'])) {
                $this->line("  - Total commits (3 months): {$commitAnalysis['commit_frequency']['total_commits']}");
                $this->line("  - Average per week: {$commitAnalysis['commit_frequency']['average_per_week']}");
            }
            $this->newLine();
        }

        $adaptations = $learningSystem->adaptGuidelines([]);
        if (count($adaptations) > 0) {
            $this->info('💡 Recommended guideline adaptations:');
            foreach ($adaptations as $adaptation) {
                $confidence = round($adaptation['confidence'] * 100);
                $this->line("  - [{$adaptation['type']}] {$adaptation['guideline']} (confidence: {$confidence}%)");
            }
            $this->newLine();
        }
    }

    private function generateSuggestions($basePath, $format, $export)
    {
        $this->info('🔍 Scanning codebase for patterns...');
        $this->newLine();

        $recommender = new GuidelineRecommender($basePath);
        $analysis = $recommender->analyze();

        $summary = $analysis['summary'];
        
        $this->info('📊 Analysis Summary:');
        $this->line("  - Files analyzed: {$summary['files_analyzed']}");
        $this->line("  - Total issues: {$summary['total_issues']}");
        $this->newLine();

        if ($summary['total_issues'] > 0) {
            $this->info('Issues by severity:');
            $this->line("  - Critical: {$summary['issues_by_severity']['critical']}");
            $this->line("  - High: {$summary['issues_by_severity']['high']}");
            $this->line("  - Medium: {$summary['issues_by_severity']['medium']}");
            $this->line("  - Low: {$summary['issues_by_severity']['low']}");
            $this->newLine();
        }

        if (count($analysis['suggestions']) > 0) {
            $this->info('💡 Suggestions:');
            $this->newLine();

            foreach ($analysis['suggestions'] as $suggestion) {
                $icon = $this->getSeverityIcon($suggestion['severity']);
                $this->line("{$icon} {$suggestion['guideline']}");
                $this->line("   Found {$suggestion['occurrences']} occurrences");
                $this->line("   Example: {$suggestion['example']}");
                $this->newLine();
            }
        }

        if (count($analysis['guidelines']) > 0) {
            $this->info('📝 Recommended Guidelines:');
            $this->newLine();

            foreach ($analysis['guidelines'] as $guideline) {
                $this->line("✓ {$guideline['title']}");
                $this->line("  Priority: " . ucfirst($guideline['priority']));
                $this->line("  Reason: {$guideline['reason']}");
                $this->newLine();
            }
        }

        if ($format === 'json') {
            $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
        }

        if ($export) {
            $outputPath = $basePath . '/' . $export;
            $recommender->exportGuidelines($analysis['guidelines'], $outputPath);
            $this->info("✓ Guidelines exported to: {$export}");
        }
    }

    private function getSeverityIcon($severity)
    {
        $icons = [
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
        ];

        return $icons[$severity] ?? '⚪';
    }
}
