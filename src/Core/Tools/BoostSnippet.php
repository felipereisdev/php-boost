<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\SnippetGenerator;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class BoostSnippet extends AbstractTool
{
    public function getName()
    {
        return 'BoostSnippet';
    }

    public function getDescription()
    {
        return 'Generate code snippets following guidelines (MCP equivalent of boost:snippet)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string'],
                'list' => ['type' => 'boolean', 'default' => false],
                'name' => ['type' => 'string'],
                'namespace' => ['type' => 'string'],
                'resource' => ['type' => 'boolean'],
                'with_factory' => ['type' => 'boolean'],
                'model' => ['type' => 'string'],
                'table' => ['type' => 'string'],
                'action' => ['type' => 'string'],
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

        try {
            $rootPath = isset($arguments['base_path']) ? $arguments['base_path'] : getcwd();
            $composerPath = rtrim($rootPath, '/') . '/composer.json';
            $projectInfo = $this->inspectProject($rootPath, $composerPath);

            $customSnippetsPath = rtrim($rootPath, '/') . '/.php-boost/snippets';
            $generator = new SnippetGenerator($projectInfo, $customSnippetsPath);

            if (!empty($arguments['list'])) {
                $types = $generator->getAvailableTypes();
                return ToolResult::success(
                    $this->getName(),
                    'Snippet types listed',
                    ['types' => $types, 'count' => count($types)],
                    ['base_path' => $rootPath, 'writes_performed' => false, 'duration_ms' => (int) round((microtime(true) - $start) * 1000)]
                );
            }

            $this->validateArguments($arguments, ['type']);
            $options = $this->buildOptions($arguments);
            $content = $generator->generate($arguments['type'], $options);

            $written = [];
            if (!empty($arguments['output'])) {
                $outputPath = $this->resolveOutputPath($rootPath, $arguments['output']);
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($outputPath, $content);
                $written[] = $outputPath;
            }

            $data = [
                'type' => $arguments['type'],
                'content' => $content,
            ];

            if (!empty($written)) {
                $data['written_files'] = $written;
            }

            return ToolResult::success(
                $this->getName(),
                'Snippet generated successfully',
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
                'Snippet generation failed',
                [],
                ['base_path' => isset($arguments['base_path']) ? $arguments['base_path'] : getcwd()],
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

    private function buildOptions(array $arguments)
    {
        $options = [];
        $map = [
            'name' => 'name',
            'namespace' => 'namespace',
            'model' => 'model',
            'table' => 'table',
            'action' => 'action',
        ];

        foreach ($map as $argKey => $optionKey) {
            if (isset($arguments[$argKey])) {
                $options[$optionKey] = $arguments[$argKey];
            }
        }

        if (!empty($arguments['resource'])) {
            $options['resource'] = true;
        }

        if (!empty($arguments['with_factory'])) {
            $options['with-factory'] = true;
        }

        return $options;
    }

    private function resolveOutputPath($basePath, $path)
    {
        if (strpos($path, '/') === 0) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}
