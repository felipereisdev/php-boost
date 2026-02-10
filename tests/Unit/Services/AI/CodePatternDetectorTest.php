<?php

namespace Tests\Unit\Services\AI;

use FelipeReisDev\PhpBoost\Core\Services\AI\CodePatternDetector;
use PHPUnit\Framework\TestCase;

class CodePatternDetectorTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    public function testDetectsRawSqlQueries()
    {
        $code = '<?php
        $results = DB::raw("SELECT * FROM users");
        $data = $query->raw("DELETE FROM posts");
        ';

        file_put_contents($this->tempDir . '/test.php', $code);

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();

        $this->assertArrayHasKey('raw_sql', $results['patterns_found']);
        $this->assertEquals(2, $results['patterns_found']['raw_sql']['count']);
    }

    public function testDetectsSelectAll()
    {
        $code = '<?php
        $users = DB::table("users")->select(*)->get();
        $sql = "SELECT * FROM posts WHERE id = 1";
        ';

        file_put_contents($this->tempDir . '/test.php', $code);

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();

        $this->assertArrayHasKey('select_all', $results['patterns_found']);
        $this->assertGreaterThanOrEqual(1, $results['patterns_found']['select_all']['count']);
    }

    public function testDetectsNPlusOneQueries()
    {
        $code = '<?php
        foreach ($users->get() as $user) {
            $posts = $user->posts;
        }
        ';

        file_put_contents($this->tempDir . '/test.php', $code);

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();

        $this->assertArrayHasKey('n_plus_one', $results['patterns_found']);
    }

    public function testDetectsHardCodedCredentials()
    {
        $code = '<?php
        $password = "supersecret123";
        $api_key = "abc123def456";
        $secret = "my-secret-key";
        ';

        file_put_contents($this->tempDir . '/test.php', $code);

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();

        $this->assertArrayHasKey('hard_coded_credentials', $results['patterns_found']);
        $this->assertEquals(3, $results['patterns_found']['hard_coded_credentials']['count']);
        $this->assertEquals('critical', $results['patterns_found']['hard_coded_credentials']['severity']);
    }

    public function testGeneratesSuggestions()
    {
        $code = '<?php
        $results = DB::raw("SELECT * FROM users");
        ';

        file_put_contents($this->tempDir . '/test.php', $code);

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();
        $suggestions = $detector->generateSuggestions($results);

        $this->assertIsArray($suggestions);
        $this->assertGreaterThan(0, count($suggestions));
        
        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('guideline', $suggestion);
            $this->assertArrayHasKey('example', $suggestion);
            $this->assertArrayHasKey('priority', $suggestion);
            $this->assertArrayHasKey('severity', $suggestion);
        }
    }

    public function testCountsFilesAnalyzed()
    {
        file_put_contents($this->tempDir . '/file1.php', '<?php $a = 1;');
        file_put_contents($this->tempDir . '/file2.php', '<?php $b = 2;');

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();

        $this->assertEquals(2, $results['files_analyzed']);
    }

    public function testExcludesVendorDirectory()
    {
        mkdir($this->tempDir . '/vendor', 0755, true);
        file_put_contents($this->tempDir . '/vendor/test.php', '<?php $password = "secret";');
        file_put_contents($this->tempDir . '/app.php', '<?php $a = 1;');

        $detector = new CodePatternDetector($this->tempDir);
        $results = $detector->scan();

        $this->assertEquals(1, $results['files_analyzed']);
    }

    private function recursiveRemoveDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
        }
        rmdir($directory);
    }
}
