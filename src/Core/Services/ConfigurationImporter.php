<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class ConfigurationImporter
{
    private $projectRoot;
    private $configPath;

    public function __construct($projectRoot = null)
    {
        $this->projectRoot = $projectRoot ?: getcwd();
        $this->configPath = $this->projectRoot . '/.php-boost';
    }

    public function import($config)
    {
        $this->validateConfig($config);

        $results = [
            'preferences' => false,
            'custom_templates' => false,
        ];

        if (isset($config['preferences'])) {
            $results['preferences'] = $this->importPreferences($config['preferences']);
        }

        if (isset($config['custom_templates'])) {
            $results['custom_templates'] = $this->importCustomTemplates($config['custom_templates']);
        }

        return $results;
    }

    public function importFromFile($filename)
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException("Configuration file not found: {$filename}");
        }

        $json = file_get_contents($filename);
        $config = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in configuration file: " . json_last_error_msg());
        }

        return $this->import($config);
    }

    public function importFromUrl($url, $options = [])
    {
        $timeout = $options['timeout'] ?? 30;
        $headers = $options['headers'] ?? [];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'User-Agent: PHP-Boost/1.0',
        ], $headers));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Failed to fetch configuration from {$url}. HTTP {$httpCode}: {$error}");
        }

        $config = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON response from {$url}: " . json_last_error_msg());
        }

        return $this->import($config);
    }

    public function merge($config, $overwrite = false)
    {
        $this->validateConfig($config);

        $currentConfig = $this->loadCurrentConfig();

        if ($overwrite) {
            $merged = array_replace_recursive($currentConfig, $config);
        } else {
            $merged = array_replace_recursive($config, $currentConfig);
        }

        return $this->import($merged);
    }

    private function validateConfig($config)
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException('Configuration must be an array');
        }

        if (!isset($config['version'])) {
            throw new \InvalidArgumentException('Configuration must include a version field');
        }

        $supportedVersions = ['1.0'];
        if (!in_array($config['version'], $supportedVersions)) {
            throw new \InvalidArgumentException("Unsupported configuration version: {$config['version']}");
        }
    }

    private function importPreferences($preferences)
    {
        $this->ensureDirectoryExists($this->configPath);

        $stateFile = $this->configPath . '/state.json';
        $currentState = [];

        if (file_exists($stateFile)) {
            $currentState = json_decode(file_get_contents($stateFile), true) ?: [];
        }

        $newState = array_replace_recursive($currentState, $preferences);

        $json = json_encode($newState, JSON_PRETTY_PRINT);

        if (file_put_contents($stateFile, $json) === false) {
            throw new \RuntimeException("Failed to write preferences to {$stateFile}");
        }

        return true;
    }

    private function importCustomTemplates($templates)
    {
        $templatesPath = $this->configPath . '/templates';
        $this->ensureDirectoryExists($templatesPath);

        $imported = 0;

        foreach ($templates as $relativePath => $content) {
            $fullPath = $templatesPath . '/' . $relativePath;
            $dir = dirname($fullPath);

            $this->ensureDirectoryExists($dir);

            if (file_put_contents($fullPath, $content) !== false) {
                $imported++;
            }
        }

        return $imported > 0;
    }

    private function loadCurrentConfig()
    {
        $stateFile = $this->configPath . '/state.json';

        if (!file_exists($stateFile)) {
            return [
                'version' => '1.0',
                'preferences' => [],
                'custom_templates' => [],
            ];
        }

        $state = json_decode(file_get_contents($stateFile), true);

        return [
            'version' => '1.0',
            'preferences' => $state,
            'custom_templates' => [],
        ];
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
