<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Services\MigrationImpactAnalyzerService;
use PHPUnit\Framework\TestCase;

class MigrationImpactAnalyzerPhase3Test extends TestCase
{
    private $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/php_boost_migration_' . uniqid('', true);
        @mkdir($this->tmpDir . '/database/migrations', 0777, true);

        file_put_contents($this->tmpDir . '/database/migrations/2026_01_01_000000_alter_posts.php', <<<'PHPF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('legacy_col');
            $table->foreignId('user_id');
            $table->string('title')->change();
        });

        DB::statement('UPDATE posts SET title = TRIM(title)');
    }
};
PHPF
);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    public function testPreviewImpactsContainsHighRiskSignals()
    {
        $service = new MigrationImpactAnalyzerService();
        $pending = $service->pendingMigrations($this->tmpDir);

        $this->assertCount(1, $pending);

        $impacts = $service->previewImpacts($pending);
        $this->assertNotEmpty($impacts);

        $operations = array_column($impacts, 'operation');
        $this->assertContains('drop_column', $operations);
        $this->assertContains('change_column', $operations);
        $this->assertContains('raw_sql', $operations);

        $rawSqlImpact = null;
        foreach ($impacts as $impact) {
            if ($impact['operation'] === 'raw_sql') {
                $rawSqlImpact = $impact;
                break;
            }
        }

        $this->assertNotNull($rawSqlImpact);
        $this->assertSame('high', $rawSqlImpact['rollback_risk']);
        $this->assertNotEmpty($rawSqlImpact['notes']);
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
