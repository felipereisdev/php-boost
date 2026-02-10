<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\ConfigurationExporter;
use FelipeReisDev\PhpBoost\Core\Services\ConfigurationImporter;

class TeamSyncTest extends TestCase
{
    private $testRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRoot = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->testRoot, 0755, true);
        mkdir($this->testRoot . '/.php-boost', 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testRoot);
    }

    public function testExportCreatesValidConfig()
    {
        $this->createComposerJson();

        $exporter = new ConfigurationExporter($this->testRoot);
        $config = $exporter->export();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('exported_at', $config);
        $this->assertArrayHasKey('project', $config);
        $this->assertEquals('1.0', $config['version']);
    }

    public function testExportToFileCreatesJsonFile()
    {
        $this->createComposerJson();

        $exporter = new ConfigurationExporter($this->testRoot);
        $filename = $exporter->exportToFile();

        $this->assertFileExists($filename);
        $this->assertJson(file_get_contents($filename));
    }

    public function testImportFromFileLoadsConfig()
    {
        $configFile = $this->testRoot . '/.php-boost/config.json';
        $config = [
            'version' => '1.0',
            'exported_at' => date('c'),
            'project' => ['name' => 'test-project'],
            'preferences' => [
                'locale' => 'pt-BR',
                'auto_update' => true,
            ],
        ];

        file_put_contents($configFile, json_encode($config));

        $importer = new ConfigurationImporter($this->testRoot);
        $result = $importer->importFromFile($configFile);

        $this->assertIsArray($result);
        $this->assertTrue($result['preferences']);
    }

    public function testImportThrowsExceptionForInvalidFile()
    {
        $this->expectException(\RuntimeException::class);

        $importer = new ConfigurationImporter($this->testRoot);
        $importer->importFromFile('/nonexistent/file.json');
    }

    public function testImportThrowsExceptionForInvalidJson()
    {
        $configFile = $this->testRoot . '/.php-boost/config.json';
        file_put_contents($configFile, 'invalid json {');

        $this->expectException(\RuntimeException::class);

        $importer = new ConfigurationImporter($this->testRoot);
        $importer->importFromFile($configFile);
    }

    public function testImportValidatesConfigVersion()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('version');

        $importer = new ConfigurationImporter($this->testRoot);
        $importer->import(['invalid' => 'config']);
    }

    public function testImportCustomTemplates()
    {
        $config = [
            'version' => '1.0',
            'custom_templates' => [
                'custom/template.php' => '<?php return "test";',
            ],
        ];

        $importer = new ConfigurationImporter($this->testRoot);
        $result = $importer->import($config);

        $templatePath = $this->testRoot . '/.php-boost/templates/custom/template.php';
        $this->assertFileExists($templatePath);
        $this->assertTrue($result['custom_templates']);
    }

    public function testMergeConfigurations()
    {
        $stateFile = $this->testRoot . '/.php-boost/state.json';
        file_put_contents($stateFile, json_encode([
            'locale' => 'en',
            'auto_update' => false,
        ]));

        $newConfig = [
            'version' => '1.0',
            'preferences' => [
                'locale' => 'pt-BR',
            ],
        ];

        $importer = new ConfigurationImporter($this->testRoot);
        $importer->merge($newConfig, false);

        $state = json_decode(file_get_contents($stateFile), true);
        $this->assertEquals('en', $state['locale']);
        $this->assertFalse($state['auto_update']);
    }

    public function testMergeConfigurationsWithOverwrite()
    {
        $stateFile = $this->testRoot . '/.php-boost/state.json';
        file_put_contents($stateFile, json_encode([
            'locale' => 'en',
            'auto_update' => false,
        ]));

        $newConfig = [
            'version' => '1.0',
            'preferences' => [
                'locale' => 'pt-BR',
            ],
        ];

        $importer = new ConfigurationImporter($this->testRoot);
        $importer->merge($newConfig, true);

        $state = json_decode(file_get_contents($stateFile), true);
        $this->assertEquals('pt-BR', $state['locale']);
    }

    private function createComposerJson()
    {
        $composer = [
            'name' => 'test/project',
            'description' => 'Test project',
            'require' => [
                'php' => '^7.4',
            ],
        ];

        file_put_contents(
            $this->testRoot . '/composer.json',
            json_encode($composer)
        );
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
