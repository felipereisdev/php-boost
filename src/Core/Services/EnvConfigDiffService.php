<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class EnvConfigDiffService
{
    public function analyze($basePath, $keysPattern = null, array $environments = [])
    {
        $envFiles = $this->discoverEnvFiles($basePath, $environments);
        $configDir = rtrim($basePath, '/') . '/config';
        $configReferences = $this->collectConfigEnvReferences($configDir);

        $flags = [];
        $divergences = [];
        $sensitiveExposure = [];
        $byEnvironment = [];

        foreach ($envFiles as $envName => $envFile) {
            $envValues = $this->parseEnv($envFile);
            $byEnvironment[$envName] = [
                'file' => $envFile,
                'total_keys' => count($envValues),
            ];

            foreach ($envValues as $key => $value) {
                if ($keysPattern && stripos($key, $keysPattern) === false) {
                    continue;
                }

                if ($this->looksLikeFlag($key)) {
                    $flags[] = ['environment' => $envName, 'key' => $key, 'value' => $value];
                }

                if ($this->looksSensitive($key) && !$this->isMasked($value)) {
                    $sensitiveExposure[] = [
                        'environment' => $envName,
                        'key' => $key,
                        'risk' => 'potential_plaintext_secret',
                    ];
                }
            }
        }

        $divergences = $this->findCrossEnvironmentDivergences($envFiles, $keysPattern);

        return [
            'flags' => $flags,
            'divergences' => $divergences,
            'sensitive_exposure' => $sensitiveExposure,
            'config_references' => $configReferences,
            'environments' => $byEnvironment,
        ];
    }

    private function discoverEnvFiles($basePath, array $environments)
    {
        $basePath = rtrim($basePath, '/');

        $files = [];
        if (!empty($environments)) {
            foreach ($environments as $environment) {
                $envName = trim((string) $environment);
                if ($envName === 'default') {
                    $candidate = $basePath . '/.env';
                } else {
                    $candidate = $basePath . '/.env.' . $envName;
                }

                if (file_exists($candidate)) {
                    $files[$envName] = $candidate;
                }
            }
        }

        if (empty($files)) {
            $default = $basePath . '/.env';
            if (file_exists($default)) {
                $files['default'] = $default;
            }

            foreach (glob($basePath . '/.env.*') as $candidate) {
                $name = basename($candidate);
                if ($name === '.env.example') {
                    continue;
                }
                $files[str_replace('.env.', '', $name)] = $candidate;
            }
        }

        $example = $basePath . '/.env.example';
        if (file_exists($example)) {
            $files['example'] = $example;
        }

        return $files;
    }

    private function findCrossEnvironmentDivergences(array $envFiles, $keysPattern)
    {
        $valuesByKey = [];

        foreach ($envFiles as $envName => $file) {
            $env = $this->parseEnv($file);
            foreach ($env as $key => $value) {
                if ($keysPattern && stripos($key, $keysPattern) === false) {
                    continue;
                }
                if (!isset($valuesByKey[$key])) {
                    $valuesByKey[$key] = [];
                }
                $valuesByKey[$key][$envName] = $value;
            }
        }

        $divergences = [];
        foreach ($valuesByKey as $key => $values) {
            if (count(array_unique($values)) > 1) {
                $divergences[] = [
                    'key' => $key,
                    'values' => $values,
                ];
            }
        }

        return $divergences;
    }

    private function parseEnv($file)
    {
        if (!file_exists($file)) {
            return [];
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $data = [];
        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $data[trim($key)] = trim(trim($value), "\"'");
        }

        return $data;
    }

    private function collectConfigEnvReferences($configDir)
    {
        if (!is_dir($configDir)) {
            return [];
        }

        $references = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configDir));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            if (preg_match_all('/env\(\s*[\"\']([A-Z0-9_]+)[\"\']/', $content, $matches)) {
                foreach ($matches[1] as $key) {
                    if (!isset($references[$key])) {
                        $references[$key] = [];
                    }
                    $references[$key][] = $file->getPathname();
                }
            }
        }

        return $references;
    }

    private function looksLikeFlag($key)
    {
        return strpos($key, 'FLAG') !== false || strpos($key, 'FEATURE') !== false;
    }

    private function looksSensitive($key)
    {
        $tokens = ['SECRET', 'TOKEN', 'PASSWORD', 'API_KEY', 'PRIVATE_KEY'];
        foreach ($tokens as $token) {
            if (strpos($key, $token) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isMasked($value)
    {
        return $value === '' || strpos($value, '***') !== false || $value === 'null';
    }
}
