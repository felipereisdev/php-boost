<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;

class FixCommand extends Command
{
    protected $signature = 'boost:fix
                            {--tool=auto : Formatter tool to use (auto, pint, php-cs-fixer)}
                            {--dry-run : Check fixes without modifying files}';

    protected $description = 'Auto-fix code style issues using Pint or PHP-CS-Fixer';

    public function handle()
    {
        $this->info('PHP Boost - Auto Fix');
        $this->info('===================');
        $this->newLine();

        $rootPath = base_path();
        $tool = strtolower((string) $this->option('tool'));
        $dryRun = (bool) $this->option('dry-run');

        [$toolLabel, $command] = $this->resolveFixerCommand($tool, $dryRun, $rootPath);

        if ($toolLabel === null || $command === null) {
            $this->error('No supported formatter found.');
            $this->line('Install one of the following in your project:');
            $this->line('  - composer require --dev laravel/pint');
            $this->line('  - composer require --dev friendsofphp/php-cs-fixer');
            return 1;
        }

        $this->info("Using formatter: {$toolLabel}");
        if ($dryRun) {
            $this->comment('Dry-run mode enabled (no files will be changed)');
        }
        $this->newLine();

        $currentDir = getcwd();
        chdir($rootPath);
        passthru($command, $exitCode);
        chdir($currentDir ?: $rootPath);

        $this->newLine();
        if ($exitCode === 0) {
            $this->info($dryRun ? 'No style violations found.' : 'Auto-fix completed successfully.');
            return 0;
        }

        $this->error($dryRun ? 'Style violations detected.' : 'Formatter finished with errors.');
        return (int) $exitCode;
    }

    private function resolveFixerCommand($tool, $dryRun, $rootPath)
    {
        if (!in_array($tool, ['auto', 'pint', 'php-cs-fixer'], true)) {
            $this->error("Invalid --tool option: {$tool}");
            return [null, null];
        }

        $pintBinary = $this->findBinary([
            $rootPath . '/vendor/bin/pint',
            $rootPath . '/vendor/bin/pint.bat',
        ]);

        $phpCsFixerBinary = $this->findBinary([
            $rootPath . '/vendor/bin/php-cs-fixer',
            $rootPath . '/vendor/bin/php-cs-fixer.bat',
        ]);

        if ($tool === 'pint' || ($tool === 'auto' && $pintBinary !== null)) {
            if ($pintBinary === null) {
                return [null, null];
            }

            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($pintBinary);
            if ($dryRun) {
                $command .= ' --test';
            }

            return ['Laravel Pint', $command];
        }

        if ($tool === 'php-cs-fixer' || ($tool === 'auto' && $phpCsFixerBinary !== null)) {
            if ($phpCsFixerBinary === null) {
                return [null, null];
            }

            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpCsFixerBinary) . ' fix';
            if ($dryRun) {
                $command .= ' --dry-run --diff';
            }

            return ['PHP-CS-Fixer', $command];
        }

        return [null, null];
    }

    private function findBinary(array $paths)
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
