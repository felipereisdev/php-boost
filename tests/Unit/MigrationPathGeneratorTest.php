<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\MigrationPathGenerator;

class MigrationPathGeneratorTest extends TestCase
{
    private $testProjectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testProjectRoot = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->testProjectRoot);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        rmdir($this->testProjectRoot);
    }

    public function testGeneratePathLaravel8To11()
    {
        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $path = $generator->generatePath('laravel-8', 'laravel-11');

        $this->assertIsArray($path);
        $this->assertArrayHasKey('from', $path);
        $this->assertArrayHasKey('to', $path);
        $this->assertArrayHasKey('steps', $path);
        $this->assertArrayHasKey('breaking_changes', $path);
        $this->assertArrayHasKey('estimated_effort', $path);

        $this->assertEquals('laravel-8', $path['from']);
        $this->assertEquals('laravel-11', $path['to']);
    }

    public function testGeneratePathReturnsSteps()
    {
        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $path = $generator->generatePath('laravel-10', 'laravel-11');

        $this->assertNotEmpty($path['steps']);
        $this->assertIsArray($path['steps']);

        foreach ($path['steps'] as $step) {
            $this->assertArrayHasKey('step', $step);
            $this->assertArrayHasKey('title', $step);
            $this->assertArrayHasKey('description', $step);
        }
    }

    public function testGeneratePathIdentifiesBreakingChanges()
    {
        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $path = $generator->generatePath('laravel-10', 'laravel-11');

        $this->assertNotEmpty($path['breaking_changes']);
        $this->assertIsArray($path['breaking_changes']);

        foreach ($path['breaking_changes'] as $change) {
            $this->assertArrayHasKey('type', $change);
            $this->assertArrayHasKey('description', $change);
            $this->assertArrayHasKey('impact', $change);
        }
    }

    public function testEstimatesEffort()
    {
        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $path = $generator->generatePath('laravel-8', 'laravel-11');

        $this->assertArrayHasKey('estimated_effort', $path);
        $effort = $path['estimated_effort'];

        $this->assertArrayHasKey('minimum_hours', $effort);
        $this->assertArrayHasKey('maximum_hours', $effort);
        $this->assertArrayHasKey('complexity', $effort);

        $this->assertGreaterThan(0, $effort['minimum_hours']);
        $this->assertGreaterThan($effort['minimum_hours'], $effort['maximum_hours']);
    }

    public function testThrowsExceptionForInvalidVersion()
    {
        $this->expectException(\InvalidArgumentException::class);

        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $generator->generatePath('invalid-version', 'laravel-11');
    }

    public function testRecommendedApproachForLargeJump()
    {
        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $path = $generator->generatePath('laravel-8', 'laravel-11');

        $this->assertArrayHasKey('recommended_approach', $path);
        $this->assertContains($path['recommended_approach'], ['incremental', 'incremental_recommended', 'direct']);
    }

    public function testIncludesResources()
    {
        $generator = new MigrationPathGenerator($this->testProjectRoot);
        $path = $generator->generatePath('laravel-10', 'laravel-11');

        $this->assertArrayHasKey('resources', $path);
        $this->assertIsArray($path['resources']);

        if (!empty($path['resources'])) {
            $resource = $path['resources'][0];
            $this->assertArrayHasKey('type', $resource);
            $this->assertArrayHasKey('title', $resource);
            $this->assertArrayHasKey('url', $resource);
        }
    }
}
