<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Services\StaticAnalysisService;
use PHPUnit\Framework\TestCase;

class StaticAnalysisServicePhase3Test extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/php_boost_static_p3_' . uniqid('', true);
        @mkdir($this->tmpDir . '/app/Http/Controllers', 0777, true);
        @mkdir($this->tmpDir . '/app/Services', 0777, true);
        @mkdir($this->tmpDir . '/routes', 0777, true);

        file_put_contents($this->tmpDir . '/app/Http/Controllers/PostController.php', <<<'PHPF'
<?php

class PostController
{
    public function index($posts)
    {
        foreach ($posts as $post) {
            $name = $post->user->name;
            $x = User::find($post->user_id);
        }
    }
}
PHPF
);

        file_put_contents($this->tmpDir . '/app/Services/UnusedService.php', <<<'PHPF'
<?php

class UnusedService
{
    public function run() {}
}
PHPF
);

        file_put_contents($this->tmpDir . '/routes/web.php', <<<'PHPF'
<?php

use Illuminate\Support\Facades\Route;
Route::get('/posts', [PostController::class, 'index']);
PHPF
);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    public function testFindNPlusOneRisksHasSeverityAndEvidence()
    {
        $service = new StaticAnalysisService();
        $files = $service->listPhpFiles([$this->tmpDir . '/app/Http/Controllers']);
        $risks = $service->findNPlusOneRisks($files);

        $this->assertNotEmpty($risks);
        $this->assertArrayHasKey('severity', $risks[0]);
        $this->assertArrayHasKey('evidence', $risks[0]);
        $this->assertGreaterThanOrEqual(0.2, $risks[0]['confidence']);
    }

    public function testDeadCodeHintsIncludeReferenceSignals()
    {
        $service = new StaticAnalysisService();
        $files = $service->listPhpFiles([$this->tmpDir . '/app', $this->tmpDir . '/routes']);
        $hints = $service->deadCodeHints($files);

        $this->assertNotEmpty($hints);

        $unused = null;
        foreach ($hints as $hint) {
            if ($hint['symbol'] === 'UnusedService') {
                $unused = $hint;
                break;
            }
        }

        $this->assertNotNull($unused);
        $this->assertArrayHasKey('references_count', $unused);
        $this->assertArrayHasKey('signals', $unused);
    }

    private function removeDir($dir)
    {
        if (!is_dir($dir)) {
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
