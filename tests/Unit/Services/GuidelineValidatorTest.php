<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\GuidelineValidator;

class GuidelineValidatorTest extends TestCase
{
    private $testRoot;
    private $projectInfo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRoot = sys_get_temp_dir() . '/php-boost-test-' . uniqid();
        mkdir($this->testRoot, 0755, true);
        mkdir($this->testRoot . '/app', 0755, true);
        
        $this->projectInfo = [
            'name' => 'test-project',
            'framework' => ['name' => 'standalone'],
            'php' => ['constraint' => '^7.4'],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->testRoot);
    }

    public function testValidateReturnsStructure()
    {
        $this->createTestFile('test.php', '<?php declare(strict_types=1);');

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('max_score', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('violations', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function testDetectsMissingStrictTypes()
    {
        $this->createTestFile('test.php', '<?php echo "test";');

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $phpBestPractices = $result['results']['php_best_practices'];
        $this->assertLessThan(100, $phpBestPractices['score']);
        $this->assertNotEmpty($phpBestPractices['issues']);
    }

    public function testDetectsSelectAsterisk()
    {
        $code = '<?php 
        declare(strict_types=1);
        $users = DB::query("SELECT * FROM users");
        ';

        $this->createTestFile('test.php', $code);

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $this->assertGreaterThan(0, count($result['violations']));
        
        $hasSelectViolation = false;
        foreach ($result['violations'] as $violation) {
            if ($violation['type'] === 'performance') {
                $hasSelectViolation = true;
                break;
            }
        }
        
        $this->assertTrue($hasSelectViolation);
    }

    public function testDetectsUnsafeInputHandling()
    {
        $code = '<?php 
        declare(strict_types=1);
        $name = $_GET["name"];
        echo $name;
        ';

        $this->createTestFile('test.php', $code);

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $hasSecurityViolation = false;
        foreach ($result['violations'] as $violation) {
            if ($violation['type'] === 'security') {
                $hasSecurityViolation = true;
                break;
            }
        }
        
        $this->assertTrue($hasSecurityViolation);
    }

    public function testDetectsEvalUsage()
    {
        $code = '<?php 
        declare(strict_types=1);
        eval("echo \'test\';");
        ';

        $this->createTestFile('test.php', $code);

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $security = $result['results']['security'];
        $this->assertLessThan(100, $security['score']);
    }

    public function testScoreCalculation()
    {
        $this->createTestFile('good.php', '<?php declare(strict_types=1); class Good {}');

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $this->assertGreaterThan(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function testGeneratesRecommendations()
    {
        $badCode = '<?php 
        eval("dangerous");
        $data = $_GET["data"];
        ';

        $this->createTestFile('bad.php', $badCode);

        $validator = new GuidelineValidator($this->testRoot, $this->projectInfo);
        $result = $validator->validate();

        $this->assertNotEmpty($result['recommendations']);
    }

    private function createTestFile($filename, $content)
    {
        file_put_contents($this->testRoot . '/app/' . $filename, $content);
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
