<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Services\McpClientConfigurator;
use PHPUnit\Framework\TestCase;

class McpClientConfiguratorTest extends TestCase
{
    private $tmpHome;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpHome = sys_get_temp_dir() . '/php-boost-mcp-' . uniqid('', true);
        mkdir($this->tmpHome, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpHome);
        parent::tearDown();
    }

    public function testConfigureCreatesCodexAndClaudeConfigs()
    {
        $service = new McpClientConfigurator($this->tmpHome);
        $result = $service->configure('/project/path', 'php artisan boost:start');

        $this->assertSame('created', $result['codex']['status']);
        $this->assertSame('created', $result['claude']['status']);

        $codexConfig = file_get_contents($this->tmpHome . '/.codex/config.toml');
        $this->assertStringContainsString('[mcp_servers.php-boost]', $codexConfig);
        $this->assertStringContainsString('php artisan boost:start', $codexConfig);

        $claudeConfigPath = $this->tmpHome . '/Library/Application Support/Claude/claude_desktop_config.json';
        $this->assertFileExists($claudeConfigPath);

        $claude = json_decode((string) file_get_contents($claudeConfigPath), true);
        $this->assertIsArray($claude);
        $this->assertArrayHasKey('mcpServers', $claude);
        $this->assertArrayHasKey('php-boost', $claude['mcpServers']);
        $this->assertSame('/bin/zsh', $claude['mcpServers']['php-boost']['command']);
    }

    public function testConfigureCodexUpdatesExistingPhpBoostBlock()
    {
        $codexDir = $this->tmpHome . '/.codex';
        mkdir($codexDir, 0755, true);

        $initial = "personality = \"pragmatic\"\n\n"
            . "[mcp_servers.php-boost]\n"
            . "command = \"/bin/zsh\"\n"
            . "args = [\"-lc\", \"cd /old/path && php artisan boost:start\"]\n\n"
            . "[projects.\"/tmp\"]\n"
            . "trust_level = \"trusted\"\n";

        file_put_contents($codexDir . '/config.toml', $initial);

        $service = new McpClientConfigurator($this->tmpHome);
        $result = $service->configureCodex('/new/path', 'php artisan boost:start');

        $this->assertSame('updated', $result['status']);

        $updated = (string) file_get_contents($codexDir . '/config.toml');
        $this->assertSame(1, substr_count($updated, '[mcp_servers.php-boost]'));
        $this->assertStringContainsString('cd /new/path && php artisan boost:start', $updated);
        $this->assertStringContainsString('[projects."/tmp"]', $updated);
    }

    public function testConfigureClaudeReturnsErrorWhenJsonIsInvalid()
    {
        file_put_contents($this->tmpHome . '/.claude.json', '{invalid json');

        $service = new McpClientConfigurator($this->tmpHome);
        $result = $service->configureClaude('/project/path', 'php artisan boost:start');

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('not valid JSON', $result['message']);
    }

    private function removeDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
