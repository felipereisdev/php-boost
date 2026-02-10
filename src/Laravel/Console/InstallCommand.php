<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineGenerator;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineWriter;

class InstallCommand extends Command
{
    protected $signature = 'boost:install 
                            {--force : Overwrite existing files without backup}
                            {--claude-only : Generate only CLAUDE.md}
                            {--agents-only : Generate only AGENTS.md}';

    protected $description = 'Install PHP Boost and generate AI guidelines';

    public function handle()
    {
        $this->info('PHP Boost Installation');
        $this->info('=====================');
        $this->newLine();

        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';

        $this->info('Step 1: Detecting project information...');
        
        $inspector = new ProjectInspector($rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $this->displayProjectInfo($projectInfo);

        $this->info('Step 2: Generating guidelines...');
        
        $generator = new GuidelineGenerator($projectInfo);
        $writer = new GuidelineWriter($rootPath);

        $generateClaude = !$this->option('agents-only');
        $generateAgents = !$this->option('claude-only');

        if ($generateClaude) {
            $this->generateClaudeMd($generator, $writer);
        }

        if ($generateAgents) {
            $this->generateAgentsMd($generator, $writer);
        }

        $this->newLine();
        $this->info('Installation complete!');
        $this->newLine();
        
        $this->displayNextSteps();

        return 0;
    }

    private function displayProjectInfo($projectInfo)
    {
        $this->newLine();
        $this->info('Project: ' . $projectInfo['name']);
        $this->info('Framework: ' . $projectInfo['framework']['name'] . ' ' . $projectInfo['framework']['version']);
        $this->info('PHP: ' . $projectInfo['php']['runtime'] . ' (Constraint: ' . $projectInfo['php']['constraint'] . ')');
        
        if (!empty($projectInfo['database'])) {
            $this->info('Database: ' . $projectInfo['database']);
        }
        
        if (!empty($projectInfo['environment'])) {
            $this->info('Environment: ' . $projectInfo['environment']);
        }
        
        if (!empty($projectInfo['tests']['framework'])) {
            $this->info('Tests: ' . $projectInfo['tests']['framework'] . ' (' . $projectInfo['tests']['count'] . ' tests)');
        }
        
        $this->newLine();
    }

    private function generateClaudeMd($generator, $writer)
    {
        $this->info('Generating CLAUDE.md...');
        
        $content = $generator->generateClaudeMd();
        
        try {
            $writer->writeClaudeMd($content);
            $this->info('✓ CLAUDE.md generated successfully');
            
            if ($writer->hasBackups('CLAUDE.md')) {
                $this->comment('  → Backup created at ' . $writer->getBackupPath());
            }
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate CLAUDE.md: ' . $e->getMessage());
        }
    }

    private function generateAgentsMd($generator, $writer)
    {
        $this->info('Generating AGENTS.md...');
        
        $content = $generator->generateAgentsMd();
        
        try {
            $writer->writeAgentsMd($content);
            $this->info('✓ AGENTS.md generated successfully');
            
            if ($writer->hasBackups('AGENTS.md')) {
                $this->comment('  → Backup created at ' . $writer->getBackupPath());
            }
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate AGENTS.md: ' . $e->getMessage());
        }
    }

    private function displayNextSteps()
    {
        $this->info('Next Steps:');
        $this->info('1. Review generated CLAUDE.md and AGENTS.md files');
        $this->info('2. Customize any sections below the AUTO-GENERATED markers');
        $this->info('3. Start the MCP server: php artisan boost:start');
        $this->info('4. Re-run this command anytime to update guidelines');
        $this->newLine();
        $this->comment('Tip: Use --claude-only or --agents-only to regenerate specific files');
    }
}
