<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class GitIntegration
{
    private $rootPath;
    private $enabled;
    
    public function __construct($rootPath, $enabled = false)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->enabled = $enabled && $this->isGitRepository();
    }
    
    public function isGitRepository()
    {
        return is_dir($this->rootPath . '/.git');
    }
    
    public function autoCommit($files, $message)
    {
        if (!$this->enabled) {
            return false;
        }
        
        foreach ($files as $file) {
            $this->addFile($file);
        }
        
        return $this->commit($message);
    }
    
    public function getDiff($files)
    {
        if (!$this->enabled) {
            return '';
        }
        
        $output = [];
        
        foreach ($files as $file) {
            $diff = $this->getFileDiff($file);
            if ($diff) {
                $output[] = $diff;
            }
        }
        
        return implode("\n\n", $output);
    }
    
    public function hasChanges($files)
    {
        if (!$this->enabled) {
            return false;
        }
        
        foreach ($files as $file) {
            if ($this->fileHasChanges($file)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function generateGitAttributes()
    {
        $content = <<<'GITATTRIBUTES'
# PHP Boost Guidelines
/.claude/CLAUDE.md merge=ours
/.claude/AGENTS.md merge=ours
/.php-boost/state.json merge=ours
/.php-boost/preferences.json merge=ours

GITATTRIBUTES;
        
        $gitAttributesPath = $this->rootPath . '/.gitattributes';
        
        if (file_exists($gitAttributesPath)) {
            $existing = file_get_contents($gitAttributesPath);
            
            if (strpos($existing, '# PHP Boost Guidelines') === false) {
                $content = $existing . "\n" . $content;
            } else {
                return false;
            }
        }
        
        file_put_contents($gitAttributesPath, $content);
        
        return true;
    }
    
    public function generatePreCommitHook()
    {
        $hookPath = $this->rootPath . '/.git/hooks/pre-commit';
        
        if (file_exists($hookPath)) {
            return false;
        }
        
        $content = <<<'HOOK'
#!/bin/sh

COMPOSER_CHANGED=$(git diff --cached --name-only | grep "composer.json")

if [ ! -z "$COMPOSER_CHANGED" ]; then
    echo ""
    echo "⚠️  composer.json modified - PHP Boost guidelines may need updating"
    echo "Run: ./vendor/bin/boost-install"
    echo ""
fi

exit 0
HOOK;
        
        file_put_contents($hookPath, $content);
        chmod($hookPath, 0755);
        
        return true;
    }
    
    private function addFile($file)
    {
        $relativePath = str_replace($this->rootPath . '/', '', $file);
        
        $command = sprintf(
            'cd %s && git add %s 2>&1',
            escapeshellarg($this->rootPath),
            escapeshellarg($relativePath)
        );
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0;
    }
    
    private function commit($message)
    {
        $command = sprintf(
            'cd %s && git commit -m %s 2>&1',
            escapeshellarg($this->rootPath),
            escapeshellarg($message)
        );
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0;
    }
    
    private function getFileDiff($file)
    {
        $relativePath = str_replace($this->rootPath . '/', '', $file);
        
        $command = sprintf(
            'cd %s && git diff %s 2>&1',
            escapeshellarg($this->rootPath),
            escapeshellarg($relativePath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output)) {
            return implode("\n", $output);
        }
        
        return '';
    }
    
    private function fileHasChanges($file)
    {
        $relativePath = str_replace($this->rootPath . '/', '', $file);
        
        $command = sprintf(
            'cd %s && git diff --name-only %s 2>&1',
            escapeshellarg($this->rootPath),
            escapeshellarg($relativePath)
        );
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0 && !empty($output);
    }
}
