<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\APIContractMap;
use FelipeReisDev\PhpBoost\Core\Tools\DeadCodeHints;
use FelipeReisDev\PhpBoost\Core\Tools\FindNPlusOneRisk;
use FelipeReisDev\PhpBoost\Core\Tools\PolicyAudit;
use PHPUnit\Framework\TestCase;

class GoldenFixturesTest extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/php_boost_golden_' . uniqid('', true);
        @mkdir($this->tmpDir . '/app/Http/Controllers', 0777, true);
        @mkdir($this->tmpDir . '/app/Services', 0777, true);
        @mkdir($this->tmpDir . '/routes', 0777, true);

        file_put_contents($this->tmpDir . '/app/Http/Controllers/PostController.php', <<<'PHPF'
<?php

namespace App\Http\Controllers;

class PostController
{
    public function index($posts)
    {
        foreach ($posts as $post) {
            $name = $post->user->name;
            $x = User::find($post->user_id);
        }
    }

    public function store() {}
}
PHPF
);

        file_put_contents($this->tmpDir . '/app/Services/UnusedService.php', <<<'PHPF'
<?php

namespace App\Services;

class UnusedService
{
}
PHPF
);

        file_put_contents($this->tmpDir . '/routes/api.php', <<<'PHPF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/posts', [App\Http\Controllers\PostController::class, 'index'])->middleware(['auth:sanctum', 'can:viewAny,App\\Models\\Post']);
Route::post('/posts', [App\Http\Controllers\PostController::class, 'store']);
PHPF
);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    public function testGoldenApiContractAndPolicyAudit()
    {
        $cwd = getcwd();
        chdir($this->tmpDir);

        try {
            $api = new APIContractMap([]);
            $apiResult = $api->execute(['route_prefix' => '/posts']);
            $endpoints = array_map(function ($e) {
                return [
                    'method' => $e['method'],
                    'path' => $e['path'],
                    'controller_action' => $e['controller_action'],
                    'auth' => $e['auth'],
                ];
            }, $apiResult['data']['endpoints']);

            $expectedApi = $this->loadFixture('api_contract_map.json');
            $this->assertSame($expectedApi, $endpoints);

            $policy = new PolicyAudit([]);
            $policyResult = $policy->execute(['route_prefix' => '/posts']);
            $matrix = array_map(function ($m) {
                return [
                    'endpoint' => $m['endpoint'],
                    'policy' => $m['policy'],
                    'model' => $m['model'],
                    'status' => $m['status'],
                ];
            }, $policyResult['data']['matrix']);

            $expectedPolicy = $this->loadFixture('policy_audit.json');
            $this->assertSame($expectedPolicy, $matrix);
        } finally {
            chdir($cwd);
        }
    }

    public function testGoldenNPlusOneAndDeadCode()
    {
        $cwd = getcwd();
        chdir($this->tmpDir);

        try {
            $nplus = new FindNPlusOneRisk([]);
            $nplusResult = $nplus->execute([
                'paths' => [$this->tmpDir . '/app/Http/Controllers'],
                'severity_threshold' => 0.7,
            ]);

            $nplusNormalized = array_map(function ($risk) {
                return [
                    'file' => basename($risk['file']),
                    'pattern' => $risk['pattern'],
                    'severity' => $risk['severity'],
                    'relation' => $risk['relation'],
                ];
            }, $nplusResult['data']['risks']);

            $expectedNplus = $this->loadFixture('nplusone.json');
            $this->assertSame($expectedNplus, $nplusNormalized);

            $dead = new DeadCodeHints([]);
            $deadResult = $dead->execute([
                'paths' => [$this->tmpDir . '/app/Services'],
                'min_confidence' => 0.6,
            ]);

            $deadNormalized = array_map(function ($hint) {
                return [
                    'symbol' => $hint['symbol'],
                    'type' => $hint['type'],
                ];
            }, $deadResult['data']['hints']);

            $expectedDead = $this->loadFixture('dead_code.json');
            $this->assertSame($expectedDead, $deadNormalized);
        } finally {
            chdir($cwd);
        }
    }

    private function loadFixture($name)
    {
        $path = __DIR__ . '/../Fixtures/golden/' . $name;
        $content = file_get_contents($path);
        if ($content === false) {
            $this->fail('Unable to load fixture: ' . $path);
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            $this->fail('Invalid fixture JSON: ' . $path);
        }

        return $decoded;
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
