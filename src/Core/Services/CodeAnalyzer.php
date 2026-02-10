<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class CodeAnalyzer
{
    private $projectRoot;
    private $projectInfo;
    private $issues;

    public function __construct($projectRoot, $projectInfo)
    {
        $this->projectRoot = $projectRoot;
        $this->projectInfo = $projectInfo;
        $this->issues = [];
    }

    public function analyze()
    {
        $results = [
            'phpcs' => $this->runPhpCodeSniffer(),
            'phpstan' => $this->runPhpStan(),
            'custom' => $this->runCustomAnalysis(),
        ];

        return [
            'tools' => $results,
            'issues' => $this->issues,
            'summary' => $this->generateSummary($results),
        ];
    }

    private function runPhpCodeSniffer()
    {
        $phpcsPath = $this->findExecutable('phpcs');

        if (!$phpcsPath) {
            return [
                'available' => false,
                'message' => 'PHP_CodeSniffer not found. Install: composer require --dev squizlabs/php_codesniffer',
            ];
        }

        $command = "{$phpcsPath} --standard=PSR12 --report=json " . escapeshellarg($this->projectRoot);

        exec($command . ' 2>&1', $output, $exitCode);

        $result = json_decode(implode("\n", $output), true);

        if (!$result) {
            return [
                'available' => true,
                'status' => 'error',
                'message' => 'Failed to parse PHP_CodeSniffer output',
            ];
        }

        $totalErrors = $result['totals']['errors'] ?? 0;
        $totalWarnings = $result['totals']['warnings'] ?? 0;

        if (isset($result['files'])) {
            foreach ($result['files'] as $file => $fileData) {
                if (isset($fileData['messages'])) {
                    foreach ($fileData['messages'] as $message) {
                        $this->issues[] = [
                            'tool' => 'phpcs',
                            'severity' => $message['type'] === 'ERROR' ? 'error' : 'warning',
                            'file' => $file,
                            'line' => $message['line'],
                            'message' => $message['message'],
                            'source' => $message['source'] ?? null,
                        ];
                    }
                }
            }
        }

        return [
            'available' => true,
            'status' => $exitCode === 0 ? 'pass' : 'fail',
            'errors' => $totalErrors,
            'warnings' => $totalWarnings,
            'fixable' => $result['totals']['fixable'] ?? 0,
        ];
    }

    private function runPhpStan()
    {
        $phpstanPath = $this->findExecutable('phpstan');

        if (!$phpstanPath) {
            return [
                'available' => false,
                'message' => 'PHPStan not found. Install: composer require --dev phpstan/phpstan',
            ];
        }

        $configFile = $this->projectRoot . '/phpstan.neon';
        if (!file_exists($configFile)) {
            $configFile = $this->projectRoot . '/phpstan.neon.dist';
        }

        $command = "{$phpstanPath} analyse --error-format=json --no-progress";

        if (file_exists($configFile)) {
            $command .= " -c " . escapeshellarg($configFile);
        } else {
            $command .= " --level=5 " . escapeshellarg($this->projectRoot . '/app');
        }

        exec($command . ' 2>&1', $output, $exitCode);

        $result = json_decode(implode("\n", $output), true);

        if (!$result) {
            return [
                'available' => true,
                'status' => 'error',
                'message' => 'Failed to parse PHPStan output',
            ];
        }

        $totalErrors = $result['totals']['file_errors'] ?? 0;

        if (isset($result['files'])) {
            foreach ($result['files'] as $file => $fileData) {
                if (isset($fileData['messages'])) {
                    foreach ($fileData['messages'] as $message) {
                        $this->issues[] = [
                            'tool' => 'phpstan',
                            'severity' => 'error',
                            'file' => $file,
                            'line' => $message['line'] ?? null,
                            'message' => $message['message'] ?? 'Unknown error',
                            'ignorable' => $message['ignorable'] ?? false,
                        ];
                    }
                }
            }
        }

        return [
            'available' => true,
            'status' => $exitCode === 0 ? 'pass' : 'fail',
            'errors' => $totalErrors,
        ];
    }

    private function runCustomAnalysis()
    {
        $issues = [];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            if (!preg_match('/^<\?php\s*$/m', $content) && !preg_match('/^<\?php\s+\S/', $content)) {
                $issues[] = [
                    'tool' => 'custom',
                    'file' => $file,
                    'severity' => 'warning',
                    'message' => 'PHP opening tag should be on its own line',
                ];
            }

            if (preg_match('/\bvar_dump\s*\(|\bdd\s*\(|\bdump\s*\(/', $content)) {
                $issues[] = [
                    'tool' => 'custom',
                    'severity' => 'warning',
                    'file' => $file,
                    'message' => 'Debug function detected (var_dump/dd/dump)',
                ];
            }

            if (preg_match('/\btodo\b|\bfixme\b/i', $content)) {
                $issues[] = [
                    'tool' => 'custom',
                    'severity' => 'info',
                    'file' => $file,
                    'message' => 'TODO/FIXME comment found',
                ];
            }

            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (strlen($line) > 120) {
                    $issues[] = [
                        'tool' => 'custom',
                        'severity' => 'info',
                        'file' => $file,
                        'line' => $lineNum + 1,
                        'message' => 'Line exceeds 120 characters',
                    ];
                    break;
                }
            }
        }

        $this->issues = array_merge($this->issues, $issues);

        return [
            'available' => true,
            'status' => empty($issues) ? 'pass' : 'fail',
            'issues' => count($issues),
        ];
    }

    private function findExecutable($name)
    {
        $vendorBin = $this->projectRoot . '/vendor/bin/' . $name;

        if (file_exists($vendorBin)) {
            return $vendorBin;
        }

        exec("which {$name} 2>/dev/null", $output, $exitCode);

        if ($exitCode === 0 && !empty($output[0])) {
            return $output[0];
        }

        return null;
    }

    private function getPhpFiles()
    {
        $files = [];
        $excludeDirs = ['vendor', 'node_modules', 'storage', 'bootstrap/cache'];

        $dirs = ['app', 'src', 'lib'];
        foreach ($dirs as $dir) {
            $path = $this->projectRoot . '/' . $dir;
            if (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path, $excludeDirs));
            }
        }

        return $files;
    }

    private function scanDirectory($directory, $excludeDirs)
    {
        $files = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $path = $file->getPathname();
                    $shouldExclude = false;

                    foreach ($excludeDirs as $dir) {
                        if (strpos($path, '/' . $dir . '/') !== false) {
                            $shouldExclude = true;
                            break;
                        }
                    }

                    if (!$shouldExclude) {
                        $files[] = $path;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $files;
    }

    private function generateSummary($results)
    {
        $summary = [
            'total_issues' => count($this->issues),
            'tools_available' => 0,
            'tools_passing' => 0,
        ];

        foreach ($results as $tool => $result) {
            if ($result['available']) {
                $summary['tools_available']++;

                if (isset($result['status']) && $result['status'] === 'pass') {
                    $summary['tools_passing']++;
                }
            }
        }

        return $summary;
    }
}
