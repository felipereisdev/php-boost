<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Services\EnvConfigDiffService;
use PHPUnit\Framework\TestCase;

class EnvConfigDiffServiceTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/php_boost_env_' . uniqid('', true);
        @mkdir($this->tmpDir . '/config', 0777, true);

        file_put_contents($this->tmpDir . '/.env', "APP_FEATURE_X=true\nAPP_SECRET=secret-value\n");
        file_put_contents($this->tmpDir . '/.env.staging', "APP_FEATURE_X=false\nAPP_SECRET=***\n");
        file_put_contents($this->tmpDir . '/.env.example', "APP_FEATURE_X=false\nAPP_SECRET=\n");

        file_put_contents($this->tmpDir . '/config/app.php', <<<'PHPF'
<?php
return [
    'feature' => env('APP_FEATURE_X', false),
    'secret' => env('APP_SECRET'),
];
PHPF
);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    public function testAnalyzeDetectsDivergencesAndSensitiveExposure()
    {
        $service = new EnvConfigDiffService();
        $result = $service->analyze($this->tmpDir, null, ['default', 'staging']);

        $this->assertNotEmpty($result['flags']);
        $this->assertNotEmpty($result['divergences']);
        $this->assertNotEmpty($result['sensitive_exposure']);
        $this->assertArrayHasKey('APP_FEATURE_X', $result['config_references']);
    }

    private function removeDir($dir)
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
