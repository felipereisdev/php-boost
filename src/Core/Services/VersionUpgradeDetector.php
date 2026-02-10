<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class VersionUpgradeDetector
{
    private $stateFile;
    private $composerFile;
    
    public function __construct($rootPath, $composerFile = null)
    {
        $this->stateFile = $rootPath . '/.php-boost/state.json';
        $this->composerFile = $composerFile ?: $rootPath . '/composer.json';
    }
    
    public function detectUpgrades()
    {
        $previousState = $this->loadState();
        $currentState = $this->getCurrentState();
        
        if (empty($previousState)) {
            return [];
        }
        
        $upgrades = [];
        
        $prevRequire = array_merge(
            $previousState['require'] ?? [],
            $previousState['require-dev'] ?? []
        );
        
        $currRequire = array_merge(
            $currentState['require'] ?? [],
            $currentState['require-dev'] ?? []
        );
        
        foreach ($currRequire as $package => $version) {
            if (!isset($prevRequire[$package])) {
                $upgrades[] = [
                    'package' => $package,
                    'type' => 'added',
                    'from' => null,
                    'to' => $version,
                    'severity' => $this->detectSeverity($package, null, $version),
                ];
                continue;
            }
            
            $prevVersion = $prevRequire[$package];
            
            if ($prevVersion !== $version) {
                $upgrades[] = [
                    'package' => $package,
                    'type' => 'upgraded',
                    'from' => $prevVersion,
                    'to' => $version,
                    'severity' => $this->detectSeverity($package, $prevVersion, $version),
                ];
            }
        }
        
        foreach ($prevRequire as $package => $version) {
            if (!isset($currRequire[$package])) {
                $upgrades[] = [
                    'package' => $package,
                    'type' => 'removed',
                    'from' => $version,
                    'to' => null,
                    'severity' => 'low',
                ];
            }
        }
        
        return $upgrades;
    }
    
    public function hasSignificantUpgrades()
    {
        $upgrades = $this->detectUpgrades();
        
        foreach ($upgrades as $upgrade) {
            if (in_array($upgrade['severity'], ['critical', 'high'])) {
                return true;
            }
        }
        
        return false;
    }
    
    public function saveCurrentState()
    {
        $state = $this->getCurrentState();
        $this->saveState($state);
    }
    
    public function formatUpgrades($upgrades)
    {
        if (empty($upgrades)) {
            return '';
        }
        
        $output = [];
        
        $critical = array_filter($upgrades, function ($u) {
            return $u['severity'] === 'critical';
        });
        
        $high = array_filter($upgrades, function ($u) {
            return $u['severity'] === 'high';
        });
        
        $medium = array_filter($upgrades, function ($u) {
            return $u['severity'] === 'medium';
        });
        
        $low = array_filter($upgrades, function ($u) {
            return $u['severity'] === 'low';
        });
        
        if (!empty($critical)) {
            $output[] = "\n⚠️  CRITICAL UPGRADES:";
            foreach ($critical as $upgrade) {
                $output[] = $this->formatUpgrade($upgrade);
            }
        }
        
        if (!empty($high)) {
            $output[] = "\n⚠️  HIGH PRIORITY UPGRADES:";
            foreach ($high as $upgrade) {
                $output[] = $this->formatUpgrade($upgrade);
            }
        }
        
        if (!empty($medium)) {
            $output[] = "\n📦 MEDIUM PRIORITY UPGRADES:";
            foreach ($medium as $upgrade) {
                $output[] = $this->formatUpgrade($upgrade);
            }
        }
        
        if (!empty($low)) {
            $output[] = "\n📝 LOW PRIORITY UPGRADES:";
            foreach ($low as $upgrade) {
                $output[] = $this->formatUpgrade($upgrade);
            }
        }
        
        return implode("\n", $output);
    }
    
    private function formatUpgrade($upgrade)
    {
        switch ($upgrade['type']) {
            case 'added':
                return "  + {$upgrade['package']}: {$upgrade['to']} (NEW)";
            
            case 'removed':
                return "  - {$upgrade['package']}: {$upgrade['from']} (REMOVED)";
            
            case 'upgraded':
                return "  ↑ {$upgrade['package']}: {$upgrade['from']} → {$upgrade['to']}";
            
            default:
                return "  ? {$upgrade['package']}";
        }
    }
    
    private function detectSeverity($package, $fromVersion, $toVersion)
    {
        $criticalPackages = [
            'php',
            'laravel/framework',
            'illuminate/support',
            'lumen/framework',
        ];
        
        $highPriorityPackages = [
            'laravel/sanctum',
            'laravel/passport',
            'livewire/livewire',
            'inertiajs/inertia-laravel',
        ];
        
        if (in_array($package, $criticalPackages)) {
            if ($this->isMajorVersionChange($fromVersion, $toVersion)) {
                return 'critical';
            }
            
            if ($this->isMinorVersionChange($fromVersion, $toVersion)) {
                return 'high';
            }
            
            return 'medium';
        }
        
        if (in_array($package, $highPriorityPackages)) {
            if ($this->isMajorVersionChange($fromVersion, $toVersion)) {
                return 'high';
            }
            
            return 'medium';
        }
        
        if ($this->isMajorVersionChange($fromVersion, $toVersion)) {
            return 'medium';
        }
        
        return 'low';
    }
    
    private function isMajorVersionChange($from, $to)
    {
        if (!$from || !$to) {
            return false;
        }
        
        $fromMajor = $this->extractMajorVersion($from);
        $toMajor = $this->extractMajorVersion($to);
        
        return $fromMajor !== null && $toMajor !== null && $fromMajor !== $toMajor;
    }
    
    private function isMinorVersionChange($from, $to)
    {
        if (!$from || !$to) {
            return false;
        }
        
        $fromMinor = $this->extractMinorVersion($from);
        $toMinor = $this->extractMinorVersion($to);
        
        return $fromMinor !== null && $toMinor !== null && $fromMinor !== $toMinor;
    }
    
    private function extractMajorVersion($version)
    {
        $version = preg_replace('/^[^0-9]*/', '', $version);
        
        if (preg_match('/^(\d+)\./', $version, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
    
    private function extractMinorVersion($version)
    {
        $version = preg_replace('/^[^0-9]*/', '', $version);
        
        if (preg_match('/^\d+\.(\d+)/', $version, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
    
    private function loadState()
    {
        if (!file_exists($this->stateFile)) {
            return [];
        }
        
        $content = file_get_contents($this->stateFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveState($state)
    {
        $dir = dirname($this->stateFile);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->stateFile, json_encode($state, JSON_PRETTY_PRINT));
    }
    
    private function getCurrentState()
    {
        if (!file_exists($this->composerFile)) {
            return [];
        }
        
        $content = file_get_contents($this->composerFile);
        $composer = json_decode($content, true);
        
        return [
            'require' => $composer['require'] ?? [],
            'require-dev' => $composer['require-dev'] ?? [],
            'timestamp' => time(),
        ];
    }
}
