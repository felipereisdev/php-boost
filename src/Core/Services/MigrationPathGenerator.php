<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class MigrationPathGenerator
{
    private $projectRoot;
    private $composerPath;
    private $migrations;

    public function __construct($projectRoot, $composerPath = null)
    {
        $this->projectRoot = $projectRoot;
        $this->composerPath = $composerPath ?: $projectRoot . '/composer.json';
        $this->loadMigrationData();
    }

    public function generatePath($from, $to)
    {
        $fromParsed = $this->parseVersion($from);
        $toParsed = $this->parseVersion($to);

        if (!$fromParsed || !$toParsed) {
            throw new \InvalidArgumentException('Invalid version format');
        }

        $migrationKey = "{$fromParsed['framework']}-{$fromParsed['version']}-to-{$toParsed['version']}";

        if (!isset($this->migrations[$fromParsed['framework']])) {
            throw new \RuntimeException("No migration data for framework: {$fromParsed['framework']}");
        }

        $steps = $this->getMigrationSteps($fromParsed, $toParsed);

        $breakingChanges = $this->getBreakingChanges($fromParsed, $toParsed);

        $effort = $this->estimateEffort($fromParsed, $toParsed, $breakingChanges);

        return [
            'from' => $from,
            'to' => $to,
            'steps' => $steps,
            'breaking_changes' => $breakingChanges,
            'estimated_effort' => $effort,
            'recommended_approach' => $this->getRecommendedApproach($fromParsed, $toParsed),
            'resources' => $this->getResources($fromParsed, $toParsed),
        ];
    }

    private function parseVersion($version)
    {
        if (preg_match('/^(laravel|lumen|symfony|php)-?(\d+)(?:\.(\d+))?$/i', $version, $matches)) {
            return [
                'framework' => strtolower($matches[1]),
                'version' => $matches[2],
                'minor' => $matches[3] ?? 0,
            ];
        }

        return null;
    }

    private function getMigrationSteps($from, $to)
    {
        $framework = $from['framework'];
        $fromVer = (int) $from['version'];
        $toVer = (int) $to['version'];

        $steps = [];

        if ($framework === 'laravel') {
            $steps = $this->getLaravelMigrationSteps($fromVer, $toVer);
        } elseif ($framework === 'lumen') {
            $steps = $this->getLumenMigrationSteps($fromVer, $toVer);
        } elseif ($framework === 'php') {
            $steps = $this->getPhpMigrationSteps($fromVer, $toVer);
        }

        return $steps;
    }

    private function getLaravelMigrationSteps($from, $to)
    {
        $allSteps = [
            8 => [
                9 => [
                    ['step' => 1, 'title' => 'Update composer.json', 'description' => 'Change Laravel framework version to ^9.0', 'commands' => ['composer update laravel/framework --with-all-dependencies']],
                    ['step' => 2, 'title' => 'Update PHP version', 'description' => 'Laravel 9 requires PHP 8.0+', 'commands' => ['php -v']],
                    ['step' => 3, 'title' => 'Update Flysystem', 'description' => 'Flysystem upgraded to v3', 'commands' => []],
                    ['step' => 4, 'title' => 'Update return types', 'description' => 'Add return types to render() methods', 'commands' => []],
                    ['step' => 5, 'title' => 'Test application', 'description' => 'Run tests to ensure everything works', 'commands' => ['php artisan test']],
                ],
                10 => [
                    ['step' => 1, 'title' => 'Upgrade to Laravel 9 first', 'description' => 'Follow Laravel 8→9 migration path first', 'commands' => []],
                    ['step' => 2, 'title' => 'Update composer.json', 'description' => 'Change Laravel framework version to ^10.0', 'commands' => ['composer update laravel/framework --with-all-dependencies']],
                    ['step' => 3, 'title' => 'Update PHP version', 'description' => 'Laravel 10 requires PHP 8.1+', 'commands' => ['php -v']],
                    ['step' => 4, 'title' => 'Update dependencies', 'description' => 'Review breaking changes in third-party packages', 'commands' => ['composer outdated']],
                    ['step' => 5, 'title' => 'Test application', 'description' => 'Run comprehensive tests', 'commands' => ['php artisan test']],
                ],
                11 => [
                    ['step' => 1, 'title' => 'Upgrade to Laravel 10 first', 'description' => 'Follow Laravel 8→10 migration path first', 'commands' => []],
                    ['step' => 2, 'title' => 'Update composer.json', 'description' => 'Change Laravel framework version to ^11.0', 'commands' => ['composer update laravel/framework --with-all-dependencies']],
                    ['step' => 3, 'title' => 'Update PHP version', 'description' => 'Laravel 11 requires PHP 8.2+', 'commands' => ['php -v']],
                    ['step' => 4, 'title' => 'Remove HTTP Kernel', 'description' => 'Laravel 11 uses middleware in bootstrap/app.php', 'commands' => []],
                    ['step' => 5, 'title' => 'Remove Console Kernel', 'description' => 'Commands are now auto-discovered', 'commands' => []],
                    ['step' => 6, 'title' => 'Update middleware', 'description' => 'Migrate middleware configuration to bootstrap/app.php', 'commands' => []],
                    ['step' => 7, 'title' => 'Update route service provider', 'description' => 'Routes are now loaded in bootstrap/app.php', 'commands' => []],
                    ['step' => 8, 'title' => 'Test application', 'description' => 'Run comprehensive tests', 'commands' => ['php artisan test']],
                ],
            ],
            9 => [
                10 => [
                    ['step' => 1, 'title' => 'Update composer.json', 'description' => 'Change Laravel framework version to ^10.0', 'commands' => ['composer update laravel/framework --with-all-dependencies']],
                    ['step' => 2, 'title' => 'Update PHP version', 'description' => 'Laravel 10 requires PHP 8.1+', 'commands' => ['php -v']],
                    ['step' => 3, 'title' => 'Update return types', 'description' => 'Add missing return types', 'commands' => []],
                    ['step' => 4, 'title' => 'Test application', 'description' => 'Run tests', 'commands' => ['php artisan test']],
                ],
                11 => [
                    ['step' => 1, 'title' => 'Upgrade to Laravel 10 first', 'description' => 'Follow Laravel 9→10 migration path first', 'commands' => []],
                    ['step' => 2, 'title' => 'Update composer.json', 'description' => 'Change Laravel framework version to ^11.0', 'commands' => ['composer update laravel/framework --with-all-dependencies']],
                    ['step' => 3, 'title' => 'Update PHP version', 'description' => 'Laravel 11 requires PHP 8.2+', 'commands' => ['php -v']],
                    ['step' => 4, 'title' => 'Remove kernels', 'description' => 'Remove HTTP and Console Kernels', 'commands' => []],
                    ['step' => 5, 'title' => 'Update configuration', 'description' => 'Migrate to new bootstrap structure', 'commands' => []],
                    ['step' => 6, 'title' => 'Test application', 'description' => 'Run comprehensive tests', 'commands' => ['php artisan test']],
                ],
            ],
            10 => [
                11 => [
                    ['step' => 1, 'title' => 'Update composer.json', 'description' => 'Change Laravel framework version to ^11.0', 'commands' => ['composer update laravel/framework --with-all-dependencies']],
                    ['step' => 2, 'title' => 'Update PHP version', 'description' => 'Laravel 11 requires PHP 8.2+', 'commands' => ['php -v']],
                    ['step' => 3, 'title' => 'Remove HTTP Kernel', 'description' => 'Remove app/Http/Kernel.php', 'commands' => []],
                    ['step' => 4, 'title' => 'Remove Console Kernel', 'description' => 'Remove app/Console/Kernel.php', 'commands' => []],
                    ['step' => 5, 'title' => 'Update bootstrap/app.php', 'description' => 'Add middleware and route configuration', 'commands' => []],
                    ['step' => 6, 'title' => 'Test application', 'description' => 'Run tests', 'commands' => ['php artisan test']],
                ],
            ],
        ];

        if (isset($allSteps[$from][$to])) {
            return $allSteps[$from][$to];
        }

        return [
            ['step' => 1, 'title' => 'Manual migration required', 'description' => "No automated path from Laravel {$from} to {$to}", 'commands' => []],
        ];
    }

    private function getLumenMigrationSteps($from, $to)
    {
        return [
            ['step' => 1, 'title' => 'Update composer.json', 'description' => "Change lumen/framework version to ^{$to}.0", 'commands' => ['composer update']],
            ['step' => 2, 'title' => 'Review breaking changes', 'description' => 'Check Lumen upgrade guide', 'commands' => []],
            ['step' => 3, 'title' => 'Test application', 'description' => 'Run tests', 'commands' => ['php artisan test']],
        ];
    }

    private function getPhpMigrationSteps($from, $to)
    {
        $steps = [
            ['step' => 1, 'title' => 'Update PHP version', 'description' => "Install PHP {$to}", 'commands' => ['php -v']],
            ['step' => 2, 'title' => 'Update composer.json', 'description' => "Change PHP constraint to ^{$to}.0", 'commands' => ['composer update']],
        ];

        if ($to >= 8) {
            $steps[] = ['step' => 3, 'title' => 'Update deprecated features', 'description' => 'Review and update deprecated PHP features', 'commands' => []];
            $steps[] = ['step' => 4, 'title' => 'Add type hints', 'description' => 'Add parameter and return type hints', 'commands' => []];
        }

        $steps[] = ['step' => 5, 'title' => 'Test application', 'description' => 'Run comprehensive tests', 'commands' => ['vendor/bin/phpunit']];

        return $steps;
    }

    private function getBreakingChanges($from, $to)
    {
        $framework = $from['framework'];
        $fromVer = (int) $from['version'];
        $toVer = (int) $to['version'];

        $changes = [];

        if ($framework === 'laravel') {
            if ($fromVer === 8 && $toVer >= 9) {
                $changes[] = ['type' => 'breaking', 'description' => 'Flysystem 3.x upgrade', 'impact' => 'high'];
                $changes[] = ['type' => 'breaking', 'description' => 'PHP 8.0+ required', 'impact' => 'critical'];
            }

            if ($fromVer <= 9 && $toVer >= 10) {
                $changes[] = ['type' => 'breaking', 'description' => 'PHP 8.1+ required', 'impact' => 'critical'];
            }

            if ($fromVer <= 10 && $toVer >= 11) {
                $changes[] = ['type' => 'breaking', 'description' => 'PHP 8.2+ required', 'impact' => 'critical'];
                $changes[] = ['type' => 'breaking', 'description' => 'HTTP Kernel removed', 'impact' => 'high'];
                $changes[] = ['type' => 'breaking', 'description' => 'Console Kernel removed', 'impact' => 'high'];
                $changes[] = ['type' => 'breaking', 'description' => 'Route service provider removed', 'impact' => 'medium'];
                $changes[] = ['type' => 'breaking', 'description' => 'Config structure changed', 'impact' => 'medium'];
            }
        }

        return $changes;
    }

    private function estimateEffort($from, $to, $breakingChanges)
    {
        $fromVer = (int) $from['version'];
        $toVer = (int) $to['version'];
        $versionJump = $toVer - $fromVer;

        $baseHours = $versionJump * 2;

        $criticalChanges = count(array_filter($breakingChanges, function ($c) {
            return $c['impact'] === 'critical';
        }));

        $highChanges = count(array_filter($breakingChanges, function ($c) {
            return $c['impact'] === 'high';
        }));

        $additionalHours = ($criticalChanges * 2) + ($highChanges * 1);

        $totalHours = $baseHours + $additionalHours;

        return [
            'minimum_hours' => $totalHours,
            'maximum_hours' => $totalHours * 2,
            'complexity' => $this->getComplexity($totalHours),
        ];
    }

    private function getComplexity($hours)
    {
        if ($hours <= 4) {
            return 'low';
        } elseif ($hours <= 8) {
            return 'medium';
        } elseif ($hours <= 16) {
            return 'high';
        } else {
            return 'very_high';
        }
    }

    private function getRecommendedApproach($from, $to)
    {
        $fromVer = (int) $from['version'];
        $toVer = (int) $to['version'];
        $versionJump = $toVer - $fromVer;

        if ($versionJump > 2) {
            return 'incremental';
        } elseif ($versionJump === 2) {
            return 'incremental_recommended';
        } else {
            return 'direct';
        }
    }

    private function getResources($from, $to)
    {
        $framework = $from['framework'];
        $toVer = (int) $to['version'];

        $resources = [];

        if ($framework === 'laravel') {
            $resources[] = [
                'type' => 'documentation',
                'title' => "Laravel {$toVer}.x Upgrade Guide",
                'url' => "https://laravel.com/docs/{$toVer}.x/upgrade",
            ];

            $resources[] = [
                'type' => 'tool',
                'title' => 'Laravel Shift',
                'url' => 'https://laravelshift.com/',
                'description' => 'Automated upgrade service',
            ];
        }

        return $resources;
    }

    private function loadMigrationData()
    {
        $this->migrations = [
            'laravel' => true,
            'lumen' => true,
            'symfony' => true,
            'php' => true,
        ];
    }
}
