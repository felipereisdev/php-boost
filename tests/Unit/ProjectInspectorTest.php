<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;

class ProjectInspectorTest extends TestCase
{
    private $tempDir;
    private $composerPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->tempDir);
        
        $this->composerPath = $this->tempDir . '/composer.json';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if (file_exists($this->composerPath)) {
            unlink($this->composerPath);
        }
        
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testInspectReturnsProjectInfo()
    {
        $composerContent = json_encode([
            'name' => 'test/project',
            'require' => [
                'php' => '^7.4',
                'laravel/framework' => '^8.0'
            ]
        ]);
        
        file_put_contents($this->composerPath, $composerContent);
        
        $inspector = new ProjectInspector($this->tempDir, $this->composerPath);
        $result = $inspector->inspect();
        
        $this->assertIsArray($result);
        $this->assertEquals('test/project', $result['name']);
        $this->assertArrayHasKey('php', $result);
        $this->assertArrayHasKey('framework', $result);
        $this->assertArrayHasKey('packages', $result);
    }

    public function testDetectFrameworkLaravel()
    {
        $composerContent = json_encode([
            'name' => 'test/project',
            'require' => [
                'php' => '^8.0',
                'laravel/framework' => '^9.0'
            ]
        ]);
        
        file_put_contents($this->composerPath, $composerContent);
        
        $inspector = new ProjectInspector($this->tempDir, $this->composerPath);
        $result = $inspector->inspect();
        
        $this->assertEquals('Laravel', $result['framework']['name']);
        $this->assertEquals('9.0', $result['framework']['version']);
    }

    public function testDetectFrameworkLumen()
    {
        $composerContent = json_encode([
            'name' => 'test/project',
            'require' => [
                'php' => '^7.4',
                'laravel/lumen-framework' => '^8.0'
            ]
        ]);
        
        file_put_contents($this->composerPath, $composerContent);
        
        $inspector = new ProjectInspector($this->tempDir, $this->composerPath);
        $result = $inspector->inspect();
        
        $this->assertEquals('Lumen', $result['framework']['name']);
        $this->assertEquals('8.0', $result['framework']['version']);
    }

    public function testDetectFrameworkStandalone()
    {
        $composerContent = json_encode([
            'name' => 'test/project',
            'require' => [
                'php' => '^7.4'
            ]
        ]);
        
        file_put_contents($this->composerPath, $composerContent);
        
        $inspector = new ProjectInspector($this->tempDir, $this->composerPath);
        $result = $inspector->inspect();
        
        $this->assertEquals('Standalone', $result['framework']['name']);
        $this->assertEquals('N/A', $result['framework']['version']);
    }

    public function testDetectPhpVersion()
    {
        $composerContent = json_encode([
            'name' => 'test/project',
            'require' => [
                'php' => '^8.1'
            ]
        ]);
        
        file_put_contents($this->composerPath, $composerContent);
        
        $inspector = new ProjectInspector($this->tempDir, $this->composerPath);
        $result = $inspector->inspect();
        
        $this->assertEquals('^8.1', $result['php']['constraint']);
        $this->assertNotEmpty($result['php']['runtime']);
    }

    public function testDetectPackages()
    {
        $composerContent = json_encode([
            'name' => 'test/project',
            'require' => [
                'php' => '^7.4',
                'laravel/framework' => '^8.0',
                'laravel/sanctum' => '^2.15',
                'livewire/livewire' => '^2.10'
            ]
        ]);
        
        file_put_contents($this->composerPath, $composerContent);
        
        $inspector = new ProjectInspector($this->tempDir, $this->composerPath);
        $result = $inspector->inspect();
        
        $this->assertArrayHasKey('laravel/sanctum', $result['packages']);
        $this->assertArrayHasKey('livewire/livewire', $result['packages']);
        $this->assertEquals('^2.15', $result['packages']['laravel/sanctum']);
        $this->assertEquals('^2.10', $result['packages']['livewire/livewire']);
    }
}
