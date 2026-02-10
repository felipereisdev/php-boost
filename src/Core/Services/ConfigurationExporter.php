<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class ConfigurationExporter
{
    private $projectRoot;
    private $configPath;

    public function __construct($projectRoot = null)
    {
        $this->projectRoot = $projectRoot ?: getcwd();
        $this->configPath = $this->projectRoot . '/.php-boost';
    }

    public function export()
    {
        $config = [
            'version' => '1.0',
            'exported_at' => date('c'),
            'php_boost_version' => $this->getPhpBoostVersion(),
            'project' => $this->getProjectConfig(),
            'preferences' => $this->getPreferences(),
            'custom_templates' => $this->getCustomTemplates(),
        ];

        return $config;
    }

    public function exportToFile($filename = null)
    {
        $filename = $filename ?: $this->configPath . '/team-config.json';
        
        $this->ensureDirectoryExists(dirname($filename));
        
        $config = $this->export();
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($filename, $json) === false) {
            throw new \RuntimeException("Failed to write configuration to {$filename}");
        }

        return $filename;
    }

    public function exportToUrl($url, $webhook = null)
    {
        $config = $this->export();
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: PHP-Boost/1.0',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Failed to export configuration to {$url}. HTTP {$httpCode}");
        }

        if ($webhook) {
            $this->notifyWebhook($webhook, 'export', $config);
        }

        return $response;
    }

    private function getPhpBoostVersion()
    {
        $composerFile = $this->projectRoot . '/vendor/felipereisdev/php-boost/composer.json';
        
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? 'dev';
        }

        return 'unknown';
    }

    private function getProjectConfig()
    {
        $composerFile = $this->projectRoot . '/composer.json';
        
        if (!file_exists($composerFile)) {
            return [];
        }

        $composer = json_decode(file_get_contents($composerFile), true);

        return [
            'name' => $composer['name'] ?? 'unknown',
            'description' => $composer['description'] ?? '',
            'php_version' => $composer['require']['php'] ?? '^7.4',
        ];
    }

    private function getPreferences()
    {
        $stateFile = $this->configPath . '/state.json';
        
        if (!file_exists($stateFile)) {
            return [];
        }

        $state = json_decode(file_get_contents($stateFile), true);

        return [
            'locale' => $state['locale'] ?? 'en',
            'templates' => $state['templates'] ?? [],
            'auto_update' => $state['auto_update'] ?? true,
        ];
    }

    private function getCustomTemplates()
    {
        $templatesPath = $this->configPath . '/templates';
        
        if (!is_dir($templatesPath)) {
            return [];
        }

        $templates = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($templatesPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($templatesPath . '/', '', $file->getPathname());
                $templates[$relativePath] = file_get_contents($file->getPathname());
            }
        }

        return $templates;
    }

    private function notifyWebhook($webhook, $event, $data)
    {
        $payload = [
            'event' => $event,
            'timestamp' => date('c'),
            'project' => $data['project']['name'] ?? 'unknown',
            'summary' => $this->generateSummary($data),
        ];

        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }

    private function generateSummary($data)
    {
        $customTemplates = count($data['custom_templates'] ?? []);
        
        return sprintf(
            'Configuration exported from %s. Custom templates: %d',
            $data['project']['name'] ?? 'unknown',
            $customTemplates
        );
    }

    private function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \RuntimeException("Failed to create directory {$directory}");
            }
        }
    }
}
