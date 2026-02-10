<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\DocumentationGenerator;

class DocumentationGeneratorTest extends TestCase
{
    private $tempDir;
    private $generator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/app');
        mkdir($this->tempDir . '/app/Http');
        mkdir($this->tempDir . '/app/Http/Controllers');
        mkdir($this->tempDir . '/app/Models');
        mkdir($this->tempDir . '/routes');
        mkdir($this->tempDir . '/database');
        mkdir($this->tempDir . '/database/migrations');
        
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'Laravel', 'version' => '10.0'],
            'database' => 'mysql',
            'php' => ['constraint' => '>=8.0'],
        ];
        
        $this->generator = new DocumentationGenerator($this->tempDir, $projectInfo);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateOpenApi()
    {
        $spec = $this->generator->generateOpenApi();
        
        $this->assertIsArray($spec);
        $this->assertEquals('3.0.0', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
    }

    public function testGenerateDatabaseDocs()
    {
        $migration = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
    }
}
PHP;
        
        file_put_contents($this->tempDir . '/database/migrations/2024_01_01_000000_create_users_table.php', $migration);
        
        $docs = $this->generator->generateDatabaseDocs();
        
        $this->assertIsArray($docs);
        $this->assertArrayHasKey('database', $docs);
        $this->assertArrayHasKey('tables', $docs);
        $this->assertEquals('mysql', $docs['database']);
    }

    public function testGenerateArchitectureDocs()
    {
        $docs = $this->generator->generateArchitectureDocs();
        
        $this->assertIsArray($docs);
        $this->assertArrayHasKey('framework', $docs);
        $this->assertArrayHasKey('structure', $docs);
        $this->assertArrayHasKey('patterns', $docs);
        $this->assertArrayHasKey('layers', $docs);
        $this->assertEquals('Laravel', $docs['framework']);
    }

    public function testGenerateDeploymentGuide()
    {
        $guide = $this->generator->generateDeploymentGuide();
        
        $this->assertIsArray($guide);
        $this->assertArrayHasKey('requirements', $guide);
        $this->assertArrayHasKey('steps', $guide);
        $this->assertArrayHasKey('configuration', $guide);
        $this->assertArrayHasKey('troubleshooting', $guide);
        $this->assertIsArray($guide['steps']);
    }

    public function testGenerateOnboardingGuide()
    {
        $guide = $this->generator->generateOnboardingGuide();
        
        $this->assertIsArray($guide);
        $this->assertArrayHasKey('setup', $guide);
        $this->assertArrayHasKey('structure', $guide);
        $this->assertArrayHasKey('conventions', $guide);
        $this->assertArrayHasKey('common_tasks', $guide);
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
