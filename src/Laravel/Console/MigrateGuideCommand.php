<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Services\MigrationPathGenerator;

class MigrateGuideCommand extends Command
{
    protected $signature = 'boost:migrate-guide 
                            {--from= : Current framework version (e.g., laravel-8)}
                            {--to= : Target framework version (e.g., laravel-11)}
                            {--format=text : Output format (text, json)}';

    protected $description = 'Generate migration guide between framework versions';

    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (!$from || !$to) {
            $this->error('Both --from and --to options are required');
            $this->newLine();
            $this->info('Example: php artisan boost:migrate-guide --from=laravel-8 --to=laravel-11');
            return 1;
        }

        $this->info('PHP Boost - Migration Path Generator');
        $this->info('====================================');
        $this->newLine();

        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';

        $generator = new MigrationPathGenerator($rootPath, $composerPath);

        try {
            $migration = $generator->generatePath($from, $to);

            $format = $this->option('format');

            if ($format === 'json') {
                $this->line(json_encode($migration, JSON_PRETTY_PRINT));
                return 0;
            }

            $this->displayMigrationPath($migration);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function displayMigrationPath($migration)
    {
        $this->info("Migration Guide: {$migration['from']} → {$migration['to']}");
        $this->newLine();

        $approach = $migration['recommended_approach'];
        $this->displayApproach($approach);
        $this->newLine();

        $this->displayEffort($migration['estimated_effort']);
        $this->newLine();

        if (!empty($migration['breaking_changes'])) {
            $this->displayBreakingChanges($migration['breaking_changes']);
            $this->newLine();
        }

        $this->displaySteps($migration['steps']);
        $this->newLine();

        if (!empty($migration['resources'])) {
            $this->displayResources($migration['resources']);
            $this->newLine();
        }

        $this->displayTips();
    }

    private function displayApproach($approach)
    {
        $this->info('Recommended Approach:');

        if ($approach === 'direct') {
            $this->line('  <fg=green>✓ Direct upgrade</> - Can upgrade directly');
        } elseif ($approach === 'incremental_recommended') {
            $this->line('  <fg=yellow>⚠ Incremental upgrade recommended</> - Consider upgrading one version at a time');
        } elseif ($approach === 'incremental') {
            $this->line('  <fg=red>! Incremental upgrade required</> - Must upgrade one version at a time');
        }
    }

    private function displayEffort($effort)
    {
        $this->info('Estimated Effort:');

        $min = $effort['minimum_hours'];
        $max = $effort['maximum_hours'];
        $complexity = $effort['complexity'];

        $complexityLabels = [
            'low' => '<fg=green>Low</>',
            'medium' => '<fg=yellow>Medium</>',
            'high' => '<fg=red>High</>',
            'very_high' => '<fg=red>Very High</>',
        ];

        $complexityLabel = $complexityLabels[$complexity] ?? $complexity;

        $this->line("  Time: {$min}-{$max} hours");
        $this->line("  Complexity: {$complexityLabel}");
    }

    private function displayBreakingChanges($changes)
    {
        $this->error('Breaking Changes: ' . count($changes));
        $this->newLine();

        $grouped = [];
        foreach ($changes as $change) {
            $impact = $change['impact'];
            if (!isset($grouped[$impact])) {
                $grouped[$impact] = [];
            }
            $grouped[$impact][] = $change;
        }

        $order = ['critical', 'high', 'medium', 'low'];

        foreach ($order as $impact) {
            if (isset($grouped[$impact])) {
                $icon = $this->getImpactIcon($impact);
                $color = $this->getImpactColor($impact);

                $this->line("  <fg={$color}>{$icon} " . strtoupper($impact) . ":</>");

                foreach ($grouped[$impact] as $change) {
                    $this->line("    • {$change['description']}");
                }

                $this->newLine();
            }
        }
    }

    private function displaySteps($steps)
    {
        $this->info('Migration Steps:');
        $this->newLine();

        foreach ($steps as $step) {
            $stepNum = $step['step'];
            $title = $step['title'];
            $description = $step['description'];

            $this->line("<fg=cyan>Step {$stepNum}:</> {$title}");
            $this->line("  {$description}");

            if (!empty($step['commands'])) {
                foreach ($step['commands'] as $command) {
                    $this->line("  <fg=gray>$ {$command}</>");
                }
            }

            $this->newLine();
        }
    }

    private function displayResources($resources)
    {
        $this->info('Resources:');
        $this->newLine();

        foreach ($resources as $resource) {
            $type = $resource['type'];
            $title = $resource['title'];
            $url = $resource['url'];

            $icon = $type === 'documentation' ? '📚' : '🔧';

            $this->line("  {$icon} {$title}");
            $this->line("     <fg=gray>{$url}</>");

            if (isset($resource['description'])) {
                $this->line("     {$resource['description']}");
            }

            $this->newLine();
        }
    }

    private function displayTips()
    {
        $this->info('Tips:');
        $this->line('  1. Create a backup before starting the migration');
        $this->line('  2. Test thoroughly in a development environment first');
        $this->line('  3. Update dependencies one at a time');
        $this->line('  4. Run tests after each major step');
        $this->line('  5. Consider using Laravel Shift for automated migration');
    }

    private function getImpactIcon($impact)
    {
        $icons = [
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
        ];

        return $icons[$impact] ?? '⚪';
    }

    private function getImpactColor($impact)
    {
        $colors = [
            'critical' => 'red',
            'high' => 'yellow',
            'medium' => 'cyan',
            'low' => 'green',
        ];

        return $colors[$impact] ?? 'white';
    }
}
