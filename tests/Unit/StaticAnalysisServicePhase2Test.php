<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Services\StaticAnalysisService;
use PHPUnit\Framework\TestCase;

class StaticAnalysisServicePhase2Test extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/php_boost_phase2_' . uniqid('', true);
        @mkdir($this->tmpDir . '/app/Models', 0777, true);
        @mkdir($this->tmpDir . '/app/Http/Requests', 0777, true);
        @mkdir($this->tmpDir . '/app/Http/Resources', 0777, true);
        @mkdir($this->tmpDir . '/routes', 0777, true);

        file_put_contents($this->tmpDir . '/app/Models/Post.php', <<<'PHPF'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';
    protected $fillable = ['title', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
PHPF
);

        file_put_contents($this->tmpDir . '/app/Http/Requests/StorePostRequest.php', <<<'PHPF'
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string',
            'body' => 'nullable|string',
        ];
    }
}
PHPF
);

        file_put_contents($this->tmpDir . '/app/Http/Resources/PostResource.php', <<<'PHPF'
<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}
PHPF
);

        file_put_contents($this->tmpDir . '/routes/api.php', <<<'PHPF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/posts', [PostController::class, 'index'])->middleware(['auth:sanctum', 'can:viewAny,App\\Models\\Post']);
Route::post('/posts', [PostController::class, 'store']);
PHPF
);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    public function testExtractModelsAndRelations()
    {
        $service = new StaticAnalysisService();
        $files = $service->listPhpFiles([$this->tmpDir . '/app']);

        $models = $service->extractModels($files);
        $this->assertCount(1, $models);
        $this->assertSame('App\\Models\\Post', $models[0]['class']);

        $relations = $service->extractModelRelations($models);
        $this->assertCount(1, $relations);
        $this->assertSame('belongsTo', $relations[0]['type']);
    }

    public function testRouteContractsAndPolicyMatrix()
    {
        $service = new StaticAnalysisService();
        $contracts = $service->routeContracts($this->tmpDir, '/posts');

        $this->assertCount(2, $contracts);
        $this->assertTrue($contracts[0]['auth']);

        $matrix = $service->policyMatrix($this->tmpDir, '/posts');
        $this->assertCount(2, $matrix);
        $this->assertSame('protected', $matrix[0]['status']);
        $this->assertSame('missing_explicit_policy', $matrix[1]['status']);
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
