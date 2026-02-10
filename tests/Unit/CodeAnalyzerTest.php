<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\CodeAnalyzer;

class CodeAnalyzerTest extends TestCase
{
    private $testProjectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testProjectRoot = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->testProjectRoot);
        mkdir($this->testProjectRoot . '/app');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testProjectRoot);
    }

    public function testAnalyzeReturnsStructuredData()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
        ];

        $analyzer = new CodeAnalyzer($this->testProjectRoot, $projectInfo);
        $result = $analyzer->analyze();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tools', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    public function testDetectsDebugFunctions()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
        ];

        $testFile = $this->testProjectRoot . '/app/Test.php';
        file_put_contents($testFile, '<?php var_dump($data);');

        $analyzer = new CodeAnalyzer($this->testProjectRoot, $projectInfo);
        $result = $analyzer->analyze();

        $debugIssues = array_filter($result['issues'], function ($issue) {
            return strpos($issue['message'], 'Debug function') !== false;
        });

        $this->assertNotEmpty($debugIssues);
    }

    public function testDetectsTodoComments()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
        ];

        $testFile = $this->testProjectRoot . '/app/Test.php';
        file_put_contents($testFile, '<?php // TODO: fix this');

        $analyzer = new CodeAnalyzer($this->testProjectRoot, $projectInfo);
        $result = $analyzer->analyze();

        $todoIssues = array_filter($result['issues'], function ($issue) {
            return strpos($issue['message'], 'TODO') !== false;
        });

        $this->assertNotEmpty($todoIssues);
    }

    public function testCustomAnalysisCountsAllCustomIssues()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
        ];

        $testFile = $this->testProjectRoot . '/app/Test.php';
        file_put_contents($testFile, "<?php\nvar_dump(\$data); // TODO: remove");

        $analyzer = new CodeAnalyzer($this->testProjectRoot, $projectInfo);
        $result = $analyzer->analyze();

        $this->assertEquals('fail', $result['tools']['custom']['status']);
        $this->assertGreaterThanOrEqual(2, $result['tools']['custom']['issues']);
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
