<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\PerformanceProfiler;

class PerformanceProfilerTest extends TestCase
{
    private $tempDir;
    private $profiler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/app');
        
        $this->profiler = new PerformanceProfiler($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->removeDirectory($this->tempDir);
    }

    public function testAnalyzeReturnsReport()
    {
        $report = $this->profiler->analyze();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('categories', $report);
        $this->assertArrayHasKey('recommendations', $report);
        $this->assertArrayHasKey('score', $report);
    }

    public function testDetectsNPlusOneQueries()
    {
        $code = <<<'PHP'
<?php
namespace App\Http\Controllers;

class UserController
{
    public function index()
    {
        $users = User::all();
        foreach ($users as $user) {
            echo $user->posts;
        }
    }
}
PHP;
        
        file_put_contents($this->tempDir . '/app/UserController.php', $code);
        
        $report = $this->profiler->analyze();
        
        $this->assertArrayHasKey('n_plus_one', $report['categories']);
    }

    public function testDetectsMissingEagerLoading()
    {
        $code = <<<'PHP'
<?php
namespace App\Http\Controllers;

class UserController
{
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }
}
PHP;
        
        file_put_contents($this->tempDir . '/app/UserController.php', $code);
        
        $report = $this->profiler->analyze();
        
        $this->assertArrayHasKey('missing_eager_loading', $report['categories']);
    }

    public function testCalculatesPerformanceScore()
    {
        $report = $this->profiler->analyze();
        
        $this->assertIsInt($report['score']);
        $this->assertGreaterThanOrEqual(0, $report['score']);
        $this->assertLessThanOrEqual(100, $report['score']);
    }

    public function testGeneratesRecommendations()
    {
        $report = $this->profiler->analyze();
        
        $this->assertIsArray($report['recommendations']);
    }

    public function testSummaryHasSeverityCounts()
    {
        $report = $this->profiler->analyze();
        
        $this->assertArrayHasKey('total_issues', $report['summary']);
        $this->assertArrayHasKey('high_severity', $report['summary']);
        $this->assertArrayHasKey('medium_severity', $report['summary']);
        $this->assertArrayHasKey('low_severity', $report['summary']);
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
