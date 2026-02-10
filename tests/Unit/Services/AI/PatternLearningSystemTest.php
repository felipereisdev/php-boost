<?php

namespace Tests\Unit\Services\AI;

use FelipeReisDev\PhpBoost\Core\Services\AI\PatternLearningSystem;
use PHPUnit\Framework\TestCase;

class PatternLearningSystemTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/php-boost-learn-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    public function testLearnsNamingConventions()
    {
        $code = '<?php
        class UserController {
            function getUserData() {}
            function createUser() {}
        }
        ';

        file_put_contents($this->tempDir . '/Controller.php', $code);

        $learner = new PatternLearningSystem($this->tempDir);
        $learnings = $learner->learnFromCodebase();

        $this->assertArrayHasKey('naming_conventions', $learnings);
        $this->assertArrayHasKey('method_naming', $learnings['naming_conventions']);
    }

    public function testLearnsCodeStyle()
    {
        $code = '<?php
        class Service {
            public function process(int $id): string {
                return "result";
            }
            
            /**
             * @param array $data
             * @return bool
             */
            public function validate(array $data): bool {
                return true;
            }
        }
        ';

        file_put_contents($this->tempDir . '/Service.php', $code);

        $learner = new PatternLearningSystem($this->tempDir);
        $learnings = $learner->learnFromCodebase();

        $this->assertArrayHasKey('code_style', $learnings);
        $this->assertArrayHasKey('type_hints', $learnings['code_style']);
        $this->assertArrayHasKey('doc_blocks', $learnings['code_style']);
    }

    public function testDetectsServiceLayer()
    {
        mkdir($this->tempDir . '/app/Services', 0755, true);
        file_put_contents($this->tempDir . '/app/Services/UserService.php', '<?php class UserService {}');

        $learner = new PatternLearningSystem($this->tempDir);
        $learnings = $learner->learnFromCodebase();

        $this->assertArrayHasKey('architecture_patterns', $learnings);
        $this->assertTrue($learnings['architecture_patterns']['service_layer']['found']);
    }

    public function testDetectsRepositoryPattern()
    {
        mkdir($this->tempDir . '/app/Repositories', 0755, true);
        file_put_contents($this->tempDir . '/app/Repositories/UserRepository.php', '<?php class UserRepository {}');

        $learner = new PatternLearningSystem($this->tempDir);
        $learnings = $learner->learnFromCodebase();

        $this->assertArrayHasKey('architecture_patterns', $learnings);
        $this->assertTrue($learnings['architecture_patterns']['repository_pattern']['found']);
    }

    public function testAdaptsGuidelines()
    {
        mkdir($this->tempDir . '/app/Services', 0755, true);
        file_put_contents($this->tempDir . '/app/Services/UserService.php', '<?php class UserService {}');
        file_put_contents($this->tempDir . '/app/Services/PostService.php', '<?php class PostService {}');
        file_put_contents($this->tempDir . '/app/Services/CommentService.php', '<?php class CommentService {}');
        file_put_contents($this->tempDir . '/app/Services/CategoryService.php', '<?php class CategoryService {}');
        file_put_contents($this->tempDir . '/app/Services/TagService.php', '<?php class TagService {}');
        file_put_contents($this->tempDir . '/app/Services/MediaService.php', '<?php class MediaService {}');
        file_put_contents($this->tempDir . '/app/Services/NotificationService.php', '<?php class NotificationService {}');
        file_put_contents($this->tempDir . '/app/Services/EmailService.php', '<?php class EmailService {}');

        $learner = new PatternLearningSystem($this->tempDir);
        $learner->learnFromCodebase();
        
        $adaptations = $learner->adaptGuidelines([]);

        $this->assertIsArray($adaptations);
        
        $architectureAdaptations = array_filter($adaptations, function ($a) {
            return $a['type'] === 'architecture';
        });
        
        $this->assertGreaterThan(0, count($architectureAdaptations));
    }

    public function testSavesAndLoadsLearnedPatterns()
    {
        $code = '<?php class Test { function testMethod() {} }';
        file_put_contents($this->tempDir . '/Test.php', $code);

        $learner = new PatternLearningSystem($this->tempDir);
        $learner->learnFromCodebase();

        $storagePath = $this->tempDir . '/.php-boost/learned-patterns.json';
        $this->assertFileExists($storagePath);

        $content = file_get_contents($storagePath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('naming_conventions', $data);
    }

    public function testLearnsCommonPractices()
    {
        $code = '<?php
        use Illuminate\Support\Facades\Log;
        
        class Service {
            public function process() {
                Log::info("Processing");
            }
        }
        ';

        file_put_contents($this->tempDir . '/Service.php', $code);

        $learner = new PatternLearningSystem($this->tempDir);
        $learnings = $learner->learnFromCodebase();

        $this->assertArrayHasKey('common_practices', $learnings);
        $this->assertArrayHasKey('logging', $learnings['common_practices']);
        $this->assertTrue($learnings['common_practices']['logging']['uses_logging']);
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
