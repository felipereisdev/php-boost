<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class McpClientConfigurator
{
    private $homePath;

    public function __construct($homePath = null)
    {
        $this->homePath = rtrim((string) ($homePath ?: $this->detectHomePath()), '/');
    }

    public function configure($projectPath, $startCommand)
    {
        return [
            'codex' => $this->configureCodex($projectPath, $startCommand),
            'claude' => $this->configureClaude($projectPath, $startCommand),
        ];
    }

    public function configureCodex($projectPath, $startCommand)
    {
        $configPath = $this->homePath . '/.codex/config.toml';
        $directory = dirname($configPath);
        $fileExists = file_exists($configPath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return [
                'status' => 'error',
                'path' => $configPath,
                'message' => 'Unable to create Codex configuration directory.',
            ];
        }

        $existing = file_exists($configPath) ? (string) file_get_contents($configPath) : '';
        $command = 'cd ' . $projectPath . ' && ' . $startCommand;

        $block = $this->buildCodexBlock($command);
        $updated = $this->upsertCodexBlock($existing, $block);

        if (file_put_contents($configPath, $updated) === false) {
            return [
                'status' => 'error',
                'path' => $configPath,
                'message' => 'Unable to write Codex configuration file.',
            ];
        }

        return [
            'status' => $fileExists ? 'updated' : 'created',
            'path' => $configPath,
        ];
    }

    public function configureClaude($projectPath, $startCommand)
    {
        $configPath = $this->resolveClaudeConfigPath();
        $directory = dirname($configPath);
        $fileExists = file_exists($configPath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return [
                'status' => 'error',
                'path' => $configPath,
                'message' => 'Unable to create Claude configuration directory.',
            ];
        }

        $config = [];

        if (file_exists($configPath)) {
            $content = (string) file_get_contents($configPath);
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [
                    'status' => 'error',
                    'path' => $configPath,
                    'message' => 'Claude config is not valid JSON.',
                ];
            }

            $config = $decoded;
        }

        if (!isset($config['mcpServers']) || !is_array($config['mcpServers'])) {
            $config['mcpServers'] = [];
        }

        $config['mcpServers']['php-boost'] = [
            'command' => '/bin/zsh',
            'args' => [
                '-lc',
                'cd ' . $projectPath . ' && ' . $startCommand,
            ],
        ];

        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($encoded === false || file_put_contents($configPath, $encoded . PHP_EOL) === false) {
            return [
                'status' => 'error',
                'path' => $configPath,
                'message' => 'Unable to write Claude configuration file.',
            ];
        }

        return [
            'status' => $fileExists ? 'updated' : 'created',
            'path' => $configPath,
        ];
    }

    private function detectHomePath()
    {
        $home = getenv('HOME');
        if (is_string($home) && $home !== '') {
            return $home;
        }

        return getcwd();
    }

    private function resolveClaudeConfigPath()
    {
        $preferred = [
            $this->homePath . '/.claude.json',
            $this->homePath . '/Library/Application Support/Claude/claude_desktop_config.json',
            $this->homePath . '/.config/Claude/claude_desktop_config.json',
        ];

        foreach ($preferred as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return $preferred[1];
    }

    private function buildCodexBlock($command)
    {
        return "[mcp_servers.php-boost]\n"
            . "command = \"/bin/zsh\"\n"
            . "args = [\"-lc\", \"" . $this->escapeToml($command) . "\"]\n";
    }

    private function upsertCodexBlock($content, $block)
    {
        $pattern = '/(^|\n)\[mcp_servers\.php-boost\]\n(?:.*\n)*?(?=\n\[|\z)/m';

        if (preg_match($pattern, $content) === 1) {
            return preg_replace($pattern, "\n" . $block, $content, 1);
        }

        $trimmed = rtrim($content);
        if ($trimmed === '') {
            return $block;
        }

        return $trimmed . "\n\n" . $block;
    }

    private function escapeToml($value)
    {
        return str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value
        );
    }
}
