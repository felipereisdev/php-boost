<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\DocumentationGenerator;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostDocs extends AbstractTool
{
    public function getName()
    {
        return 'BoostDocs';
    }

    public function getDescription()
    {
        return 'Generate project documentation (MCP equivalent of boost:docs)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['openapi', 'database', 'architecture', 'deployment', 'onboarding', 'all'], 'default' => 'all'],
                'format' => ['type' => 'string', 'enum' => ['json', 'markdown', 'html'], 'default' => 'json'],
                'output' => ['type' => 'string'],
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
        $rootPath = $this->resolveBasePath($arguments);

        try {
            $composerPath = rtrim($rootPath, '/') . '/composer.json';
            $projectInfo = $this->inspectProject($rootPath, $composerPath);

            $generator = new DocumentationGenerator($rootPath, $projectInfo);
            $type = isset($arguments['type']) ? $arguments['type'] : 'all';
            $docs = $this->generateDocumentation($generator, $type);

            $written = [];
            if (!empty($arguments['output'])) {
                $format = isset($arguments['format']) ? $arguments['format'] : 'json';
                $outputPath = $this->resolveOutputPath($rootPath, $arguments['output']);
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($outputPath, $this->renderOutput($docs, $format));
                $written[] = $outputPath;
            }

            $data = ['type' => $type, 'docs' => $docs];
            if (!empty($written)) {
                $data['written_files'] = $written;
            }

            return ToolResult::success(
                $this->getName(),
                'Documentation generated successfully',
                $data,
                [
                    'base_path' => $rootPath,
                    'writes_performed' => !empty($written),
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                ]
            );
        } catch (\Exception $e) {
            return ToolResult::error(
                $this->getName(),
                'Documentation generation failed',
                [],
                ['base_path' => $rootPath],
                [],
                [['message' => $e->getMessage()]]
            );
        }
    }

    private function inspectProject($rootPath, $composerPath)
    {
        $inspector = new ProjectInspector($rootPath, $composerPath);
        return $inspector->inspect();
    }

    private function generateDocumentation(DocumentationGenerator $generator, $type)
    {
        $docs = [];

        if ($type === 'all' || $type === 'openapi') {
            $docs['openapi'] = $generator->generateOpenApi();
        }
        if ($type === 'all' || $type === 'database') {
            $docs['database'] = $generator->generateDatabaseDocs();
        }
        if ($type === 'all' || $type === 'architecture') {
            $docs['architecture'] = $generator->generateArchitectureDocs();
        }
        if ($type === 'all' || $type === 'deployment') {
            $docs['deployment'] = $generator->generateDeploymentGuide();
        }
        if ($type === 'all' || $type === 'onboarding') {
            $docs['onboarding'] = $generator->generateOnboardingGuide();
        }

        return $docs;
    }

    private function renderOutput(array $docs, $format)
    {
        if ($format === 'markdown') {
            $content = "# Project Documentation\n\n";
            $content .= 'Generated: ' . date('Y-m-d H:i:s') . "\n\n";
            foreach ($docs as $section => $value) {
                $content .= '## ' . ucfirst($section) . "\n\n";
                $content .= "```json\n" . json_encode($value, JSON_PRETTY_PRINT) . "\n```\n\n";
            }
            return $content;
        }

        if ($format === 'html') {
            $json = htmlspecialchars(json_encode($docs, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8');
            return "<html><body><pre>{$json}</pre></body></html>";
        }

        return json_encode($docs, JSON_PRETTY_PRINT);
    }

    private function resolveOutputPath($basePath, $path)
    {
        if (strpos($path, '/') === 0) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}
