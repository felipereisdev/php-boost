<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\ProjectHealthScorer;

class ProjectHealthScorerTest extends TestCase
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

    public function testCalculateScoreReturnsStructuredData()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
            'tests' => ['count' => 50, 'framework' => 'pest'],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('weaknesses', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('timestamp', $result);
    }

    public function testScoreIsBetween0And100()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
            'tests' => ['count' => 0],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    public function testCategoriesHaveScoresAndWeights()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
            'tests' => ['count' => 10],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $this->assertArrayHasKey('categories', $result);

        foreach ($result['categories'] as $category => $data) {
            $this->assertArrayHasKey('score', $data);
            $this->assertArrayHasKey('weight', $data);
            $this->assertArrayHasKey('weighted_score', $data);

            $this->assertGreaterThanOrEqual(0, $data['score']);
            $this->assertLessThanOrEqual(100, $data['score']);
            $this->assertGreaterThan(0, $data['weight']);
        }
    }

    public function testIdentifiesStrengths()
    {
        file_put_contents($this->testProjectRoot . '/README.md', '# Test Project');
        file_put_contents($this->testProjectRoot . '/CLAUDE.md', '# Guidelines');

        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
            'tests' => ['count' => 100, 'framework' => 'pest'],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $this->assertIsArray($result['strengths']);
    }

    public function testIdentifiesWeaknesses()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'standalone', 'version' => 'none'],
            'tests' => ['count' => 0],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $this->assertNotEmpty($result['weaknesses']);
        $this->assertIsArray($result['weaknesses']);
    }

    public function testGeneratesRecommendations()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
            'tests' => ['count' => 5],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $this->assertIsArray($result['recommendations']);

        if (!empty($result['recommendations'])) {
            $recommendation = $result['recommendations'][0];
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('action', $recommendation);
        }
    }

    public function testSaveAndLoadHistory()
    {
        $projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'laravel', 'version' => '10'],
            'tests' => ['count' => 10],
            'packages' => [],
        ];

        $scorer = new ProjectHealthScorer($this->testProjectRoot, $projectInfo);
        $result = $scorer->calculateScore();

        $scorer->saveScore($result);

        $historyFile = $this->testProjectRoot . '/.php-boost/health-history.json';
        $this->assertFileExists($historyFile);

        $history = json_decode(file_get_contents($historyFile), true);
        $this->assertIsArray($history);
        $this->assertNotEmpty($history);
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
