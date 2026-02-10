<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\AI\GuidelineRecommender;
use FelipeReisDev\PhpBoost\Core\Services\AI\PatternLearningSystem;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostAnalyze extends AbstractTool
{
    public function getName()
    {
        return 'BoostAnalyze';
    }

    public function getDescription()
    {
        return 'AI-powered codebase analysis (MCP equivalent of boost:analyze)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'learn' => ['type' => 'boolean', 'default' => false],
                'suggest' => ['type' => 'boolean', 'default' => true],
                'export' => ['type' => 'string'],
                'format' => ['type' => 'string', 'enum' => ['json', 'text'], 'default' => 'json'],
                'base_path' => ['type' => 'string'],
            ],
        ];
    }

    public function isReadOnly()
    {
        return false;
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);

        try {
            $basePath = isset($arguments['base_path']) ? $arguments['base_path'] : getcwd();
            $learn = !empty($arguments['learn']);
            $suggest = array_key_exists('suggest', $arguments) ? (bool) $arguments['suggest'] : true;

            $data = [];

            if ($learn) {
                $learningSystem = new PatternLearningSystem($basePath);
                $data['learn'] = $learningSystem->learnFromCodebase();
                $data['adaptations'] = $learningSystem->adaptGuidelines([]);
            }

            if ($suggest || (!$learn && empty($arguments['export']))) {
                $recommender = new GuidelineRecommender($basePath);
                $data['analysis'] = $recommender->analyze();
            }

            $written = [];
            if (!empty($arguments['export'])) {
                if (!isset($data['analysis'])) {
                    $recommender = new GuidelineRecommender($basePath);
                    $data['analysis'] = $recommender->analyze();
                } else {
                    $recommender = new GuidelineRecommender($basePath);
                }

                $outputPath = $this->resolveOutputPath($basePath, $arguments['export']);
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $recommender->exportGuidelines($data['analysis']['guidelines'], $outputPath);
                $written[] = $outputPath;
            }

            if (!empty($written)) {
                $data['written_files'] = $written;
            }

            return ToolResult::success(
                $this->getName(),
                'Analysis completed successfully',
                $data,
                [
                    'base_path' => $basePath,
                    'writes_performed' => !empty($written),
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                ]
            );
        } catch (\Exception $e) {
            return ToolResult::error(
                $this->getName(),
                'Analysis failed',
                [],
                ['base_path' => isset($arguments['base_path']) ? $arguments['base_path'] : getcwd()],
                [],
                [['message' => $e->getMessage()]]
            );
        }
    }

    private function resolveOutputPath($basePath, $path)
    {
        if (strpos($path, '/') === 0) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}
