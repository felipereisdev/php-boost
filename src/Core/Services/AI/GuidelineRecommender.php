<?php

namespace FelipeReisDev\PhpBoost\Core\Services\AI;

class GuidelineRecommender
{
    private $detector;
    private $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->detector = new CodePatternDetector($basePath);
    }

    public function analyze()
    {
        $scanResults = $this->detector->scan();
        $suggestions = $this->detector->generateSuggestions($scanResults);

        return [
            'summary' => $this->generateSummary($scanResults, $suggestions),
            'scan_results' => $scanResults,
            'suggestions' => $suggestions,
            'guidelines' => $this->generateGuidelines($suggestions),
        ];
    }

    private function generateSummary($scanResults, $suggestions)
    {
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        $lowCount = 0;

        foreach ($suggestions as $suggestion) {
            switch ($suggestion['severity']) {
                case 'critical':
                    $criticalCount++;
                    break;
                case 'high':
                    $highCount++;
                    break;
                case 'medium':
                    $mediumCount++;
                    break;
                case 'low':
                    $lowCount++;
                    break;
            }
        }

        return [
            'files_analyzed' => $scanResults['files_analyzed'],
            'total_issues' => $scanResults['total_issues'],
            'issues_by_severity' => [
                'critical' => $criticalCount,
                'high' => $highCount,
                'medium' => $mediumCount,
                'low' => $lowCount,
            ],
            'suggestions_count' => count($suggestions),
        ];
    }

    private function generateGuidelines($suggestions)
    {
        $guidelines = [];

        usort($suggestions, function ($a, $b) {
            $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return $severityOrder[$a['severity']] - $severityOrder[$b['severity']];
        });

        foreach ($suggestions as $suggestion) {
            if ($suggestion['occurrences'] > 3) {
                $guidelines[] = [
                    'title' => $suggestion['guideline'],
                    'description' => $suggestion['example'],
                    'priority' => $suggestion['priority'],
                    'reason' => 'Found ' . $suggestion['occurrences'] . ' occurrences in your codebase',
                    'pattern' => $suggestion['pattern'],
                ];
            }
        }

        return $guidelines;
    }

    public function exportGuidelines($guidelines, $outputPath)
    {
        $content = "# Recommended Guidelines\n\n";
        $content .= "Auto-generated based on codebase analysis.\n\n";
        $content .= "---\n\n";

        foreach ($guidelines as $guideline) {
            $content .= "## {$guideline['title']}\n\n";
            $content .= "**Priority:** " . ucfirst($guideline['priority']) . "\n\n";
            $content .= "**Reason:** {$guideline['reason']}\n\n";
            $content .= "**Example:**\n\n";
            $content .= "```php\n{$guideline['description']}\n```\n\n";
            $content .= "---\n\n";
        }

        file_put_contents($outputPath, $content);
    }
}
