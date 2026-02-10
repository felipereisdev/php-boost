<?php

namespace FelipeReisDev\PhpBoost\Standalone;

use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineGenerator;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineWriter;
use FelipeReisDev\PhpBoost\Core\Services\VersionUpgradeDetector;
use FelipeReisDev\PhpBoost\Core\Services\GitIntegration;

class Install
{
    private $rootPath;
    private $options;

    public function __construct($rootPath, $options = [])
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->options = array_merge([
            'force' => false,
            'claude-only' => false,
            'agents-only' => false,
            'silent' => false,
            'interactive' => false,
            'git-commit' => false,
            'git-setup' => false,
            'lang' => null,
        ], $options);
    }

    public function execute()
    {
        if ($this->options['interactive']) {
            return $this->executeInteractive();
        }

        $this->output('PHP Boost Installation');
        $this->output('=====================');
        $this->output('');

        $composerPath = $this->rootPath . '/composer.json';

        if (!file_exists($composerPath)) {
            $this->error('composer.json not found at ' . $composerPath);
            return false;
        }

        $detector = new VersionUpgradeDetector($this->rootPath, $composerPath);
        $upgrades = $detector->detectUpgrades();
        
        if (!empty($upgrades)) {
            $this->output('Dependency Changes Detected:');
            $this->output($detector->formatUpgrades($upgrades));
            $this->output('');
        }
        
        $this->output('Step 1: Detecting project information...');
        
        $inspector = new ProjectInspector($this->rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $this->displayProjectInfo($projectInfo);

        $this->output('Step 2: Generating guidelines...');
        
        $generator = new GuidelineGenerator($projectInfo, null, $this->rootPath, $this->options['lang']);
        $writer = new GuidelineWriter($this->rootPath);

        $generateClaude = !$this->options['agents-only'];
        $generateAgents = !$this->options['claude-only'];

        $success = true;

        if ($generateClaude) {
            $success = $this->generateClaudeMd($generator, $writer) && $success;
        }

        if ($generateAgents) {
            $success = $this->generateAgentsMd($generator, $writer) && $success;
        }
        
        $detector = new VersionUpgradeDetector($this->rootPath, $composerPath);
        $detector->saveCurrentState();
        
        if ($this->options['git-setup']) {
            $this->setupGitIntegration();
        }
        
        if ($this->options['git-commit'] && $success) {
            $this->handleGitCommit();
        }

        $this->output('');
        $this->output('Installation ' . ($success ? 'complete!' : 'completed with errors'));
        $this->output('');
        
        $this->displayNextSteps();

        return $success;
    }

    private function displayProjectInfo($projectInfo)
    {
        $this->output('');
        $this->output('Project: ' . $projectInfo['name']);
        $this->output('Framework: ' . $projectInfo['framework']['name'] . ' ' . $projectInfo['framework']['version']);
        $this->output('PHP: ' . $projectInfo['php']['runtime'] . ' (Constraint: ' . $projectInfo['php']['constraint'] . ')');
        
        if (!empty($projectInfo['database'])) {
            $this->output('Database: ' . $projectInfo['database']);
        }
        
        if (!empty($projectInfo['environment'])) {
            $this->output('Environment: ' . $projectInfo['environment']);
        }
        
        if (!empty($projectInfo['tests']['framework'])) {
            $this->output('Tests: ' . $projectInfo['tests']['framework'] . ' (' . $projectInfo['tests']['count'] . ' tests)');
        }
        
        $this->output('');
    }

    private function generateClaudeMd($generator, $writer)
    {
        $this->output('Generating CLAUDE.md...');
        
        $content = $generator->generateClaudeMd();
        
        try {
            $writer->writeClaudeMd($content);
            $this->success('✓ CLAUDE.md generated successfully');
            
            if ($writer->hasBackups('CLAUDE.md')) {
                $this->comment('  → Backup created at ' . $writer->getBackupPath());
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate CLAUDE.md: ' . $e->getMessage());
            return false;
        }
    }

    private function generateAgentsMd($generator, $writer)
    {
        $this->output('Generating AGENTS.md...');
        
        $content = $generator->generateAgentsMd();
        
        try {
            $writer->writeAgentsMd($content);
            $this->success('✓ AGENTS.md generated successfully');
            
            if ($writer->hasBackups('AGENTS.md')) {
                $this->comment('  → Backup created at ' . $writer->getBackupPath());
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate AGENTS.md: ' . $e->getMessage());
            return false;
        }
    }

    private function displayNextSteps()
    {
        $this->output('Next Steps:');
        $this->output('1. Review generated CLAUDE.md and AGENTS.md files');
        $this->output('2. Customize any sections below the AUTO-GENERATED markers');
        $this->output('3. Configure your MCP client to use ./vendor/bin/boost-server (auto-start)');
        $this->output('4. Re-run this command anytime to update guidelines');
        $this->output('');
        $this->comment('Tip: Use --claude-only or --agents-only to regenerate specific files');
    }

    private function output($message)
    {
        if (!$this->options['silent']) {
            echo $message . PHP_EOL;
        }
    }

    private function success($message)
    {
        if (!$this->options['silent']) {
            echo "\033[32m" . $message . "\033[0m" . PHP_EOL;
        }
    }

    private function error($message)
    {
        if (!$this->options['silent']) {
            echo "\033[31m" . $message . "\033[0m" . PHP_EOL;
        }
    }

    private function comment($message)
    {
        if (!$this->options['silent']) {
            echo "\033[33m" . $message . "\033[0m" . PHP_EOL;
        }
    }
    
    private function setupGitIntegration()
    {
        $git = new GitIntegration($this->rootPath, true);
        
        if (!$git->isGitRepository()) {
            $this->comment('Not a git repository. Skipping git integration setup.');
            return;
        }
        
        $this->output('');
        $this->output('Setting up Git integration...');
        
        if ($git->generateGitAttributes()) {
            $this->success('✓ .gitattributes configured');
        } else {
            $this->comment('  → .gitattributes already configured');
        }
        
        if ($git->generatePreCommitHook()) {
            $this->success('✓ Pre-commit hook installed');
        } else {
            $this->comment('  → Pre-commit hook already exists');
        }
    }
    
    private function handleGitCommit()
    {
        $git = new GitIntegration($this->rootPath, true);
        
        if (!$git->isGitRepository()) {
            return;
        }
        
        $files = [
            $this->rootPath . '/.claude/CLAUDE.md',
            $this->rootPath . '/.claude/AGENTS.md',
            $this->rootPath . '/.php-boost/state.json',
        ];
        
        if (!$git->hasChanges($files)) {
            return;
        }
        
        $this->output('');
        $this->output('Git changes detected. Committing...');
        
        $diff = $git->getDiff($files);
        if ($diff) {
            $this->output('');
            $this->comment('Changes preview:');
            $this->output(substr($diff, 0, 500));
            if (strlen($diff) > 500) {
                $this->output('...');
            }
        }
        
        $message = 'Update PHP Boost guidelines';
        
        if ($git->autoCommit($files, $message)) {
            $this->success('✓ Guidelines committed to git');
        } else {
            $this->error('✗ Failed to commit guidelines');
        }
    }

    private function executeInteractive()
    {
        $this->output('');
        $this->output('=================================');
        $this->output('PHP Boost - Interactive Mode');
        $this->output('=================================');
        $this->output('');

        $composerPath = $this->rootPath . '/composer.json';

        if (!file_exists($composerPath)) {
            $this->error('composer.json not found at ' . $composerPath);
            return false;
        }

        $inspector = new ProjectInspector($this->rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $this->displayProjectInfo($projectInfo);

        $generateClaude = InteractiveInput::confirm('Generate CLAUDE.md?', true);
        $generateAgents = InteractiveInput::confirm('Generate AGENTS.md?', true);

        $this->output('');
        $this->output('Generating guidelines...');

        $generator = new GuidelineGenerator($projectInfo, null, $this->rootPath);
        $writer = new GuidelineWriter($this->rootPath);

        $success = true;

        if ($generateClaude) {
            $success = $this->generateClaudeMd($generator, $writer) && $success;
        }

        if ($generateAgents) {
            $success = $this->generateAgentsMd($generator, $writer) && $success;
        }

        if ($success) {
            $preferences = [
                'generate_claude' => $generateClaude,
                'generate_agents' => $generateAgents,
            ];
            
            $this->savePreferences($preferences);
        }

        $this->output('');
        $this->output('Installation ' . ($success ? 'complete!' : 'completed with errors'));
        $this->output('');

        $this->displayNextSteps();

        return $success;
    }

    private function savePreferences($preferences)
    {
        $configDir = $this->rootPath . '/.php-boost';
        $configFile = $configDir . '/preferences.json';

        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configFile, json_encode($preferences, JSON_PRETTY_PRINT));
        
        $this->comment('  → Preferences saved to .php-boost/preferences.json');
    }

    private function loadPreferences()
    {
        $configFile = $this->rootPath . '/.php-boost/preferences.json';

        if (!file_exists($configFile)) {
            return [];
        }

        $content = file_get_contents($configFile);
        return json_decode($content, true) ?: [];
    }

    public static function postInstall($event = null)
    {
        $config = self::loadConfig();
        
        if ($config['auto_update'] === false) {
            return;
        }

        if (isset($_SERVER['argv']) && in_array('--no-boost', $_SERVER['argv'])) {
            return;
        }

        $rootPath = getcwd();
        $composerPath = $rootPath . '/composer.json';

        if (!file_exists($composerPath)) {
            return;
        }

        self::outputHook('');
        self::outputHook('PHP Boost: Checking guidelines...');

        $installer = new self($rootPath, ['silent' => false]);
        
        if (self::shouldUpdate($rootPath)) {
            self::outputHook('Guidelines are outdated. Regenerating...');
            $installer->execute();
        } else {
            self::outputHook('Guidelines are up to date.');
        }
    }

    public static function postUpdate($event = null)
    {
        $config = self::loadConfig();
        
        if ($config['auto_update'] === false) {
            return;
        }

        if (isset($_SERVER['argv']) && in_array('--no-boost', $_SERVER['argv'])) {
            return;
        }

        $rootPath = getcwd();
        $composerPath = $rootPath . '/composer.json';

        if (!file_exists($composerPath)) {
            return;
        }

        self::outputHook('');
        self::outputHook('PHP Boost: Checking for changes...');

        $detector = new VersionUpgradeDetector($rootPath, $composerPath);
        $upgrades = $detector->detectUpgrades();
        
        if (!empty($upgrades)) {
            self::outputHook('');
            self::outputHook('Dependencies changed:');
            self::outputHook($detector->formatUpgrades($upgrades));
            self::outputHook('');
            self::outputHook('Regenerating guidelines...');
            
            $installer = new self($rootPath, ['silent' => false]);
            $installer->execute();
            
            $detector->saveCurrentState();
        } else {
            self::outputHook('No relevant changes detected.');
        }
    }

    private static function shouldUpdate($rootPath)
    {
        $claudePath = $rootPath . '/.claude/CLAUDE.md';
        $agentsPath = $rootPath . '/.claude/AGENTS.md';
        $composerPath = $rootPath . '/composer.json';

        if (!file_exists($claudePath) || !file_exists($agentsPath)) {
            return true;
        }

        $detector = new VersionUpgradeDetector($rootPath, $composerPath);
        
        if ($detector->hasSignificantUpgrades()) {
            return true;
        }

        $composerMtime = filemtime($composerPath);
        $claudeMtime = filemtime($claudePath);
        $agentsMtime = filemtime($agentsPath);

        return $composerMtime > $claudeMtime || $composerMtime > $agentsMtime;
    }

    private static function loadConfig()
    {
        $rootPath = getcwd();
        $configFile = $rootPath . '/.php-boost/config.json';

        if (!file_exists($configFile)) {
            return ['auto_update' => true];
        }

        $content = file_get_contents($configFile);
        $config = json_decode($content, true);

        return array_merge(['auto_update' => true], $config ?: []);
    }

    private static function outputHook($message)
    {
        echo $message . PHP_EOL;
    }
}
