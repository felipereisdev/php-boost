<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;
use FelipeReisDev\PhpBoost\Core\Services\DocumentationGenerator;

class DocsCommand extends Command
{
    protected $signature = 'boost:docs 
                            {--type=all : Documentation type (openapi, database, architecture, deployment, onboarding, all)}
                            {--format=json : Output format (json, markdown, html)}
                            {--output= : Output file path}';

    protected $description = 'Generate project documentation automatically';

    public function handle()
    {
        $this->info('PHP Boost - Documentation Generator');
        $this->info('==================================');
        $this->newLine();

        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';
        
        $this->info('Analyzing project...');
        
        $inspector = new ProjectInspector($rootPath, $composerPath);
        $projectInfo = $inspector->inspect();
        
        $generator = new DocumentationGenerator($rootPath, $projectInfo);
        
        $type = $this->option('type');
        $format = $this->option('format');
        
        $this->newLine();
        
        $docs = $this->generateDocumentation($generator, $type);
        
        if ($this->option('output')) {
            $this->exportDocumentation($docs, $format);
        } else {
            $this->displayDocumentation($docs, $format);
        }

        return 0;
    }

    private function generateDocumentation($generator, $type)
    {
        $docs = [];
        
        if ($type === 'all' || $type === 'openapi') {
            $this->info('Generating OpenAPI documentation...');
            $docs['openapi'] = $generator->generateOpenApi();
        }
        
        if ($type === 'all' || $type === 'database') {
            $this->info('Generating database documentation...');
            $docs['database'] = $generator->generateDatabaseDocs();
        }
        
        if ($type === 'all' || $type === 'architecture') {
            $this->info('Generating architecture documentation...');
            $docs['architecture'] = $generator->generateArchitectureDocs();
        }
        
        if ($type === 'all' || $type === 'deployment') {
            $this->info('Generating deployment guide...');
            $docs['deployment'] = $generator->generateDeploymentGuide();
        }
        
        if ($type === 'all' || $type === 'onboarding') {
            $this->info('Generating onboarding guide...');
            $docs['onboarding'] = $generator->generateOnboardingGuide();
        }
        
        $this->newLine();
        $this->info('✓ Documentation generated successfully');
        $this->newLine();
        
        return $docs;
    }

    private function displayDocumentation($docs, $format)
    {
        if ($format === 'json') {
            $this->line(json_encode($docs, JSON_PRETTY_PRINT));
        } elseif ($format === 'markdown') {
            $this->displayMarkdown($docs);
        } else {
            $this->displayTable($docs);
        }
    }

    private function displayMarkdown($docs)
    {
        foreach ($docs as $type => $content) {
            $this->line("# " . ucfirst($type) . " Documentation");
            $this->newLine();
            
            $this->renderMarkdownSection($content);
            
            $this->newLine();
            $this->line(str_repeat('-', 80));
            $this->newLine();
        }
    }

    private function renderMarkdownSection($content, $level = 2)
    {
        foreach ($content as $key => $value) {
            $heading = str_repeat('#', $level) . ' ' . ucfirst(str_replace('_', ' ', $key));
            $this->line($heading);
            $this->newLine();
            
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    $this->renderMarkdownSection($value, $level + 1);
                } else {
                    foreach ($value as $item) {
                        if (is_string($item)) {
                            $this->line("- " . $item);
                        } elseif (is_array($item)) {
                            $this->renderMarkdownSection($item, $level + 1);
                        }
                    }
                }
            } else {
                $this->line($value);
            }
            
            $this->newLine();
        }
    }

    private function displayTable($docs)
    {
        foreach ($docs as $type => $content) {
            $this->info(ucfirst($type) . ' Documentation');
            $this->line(str_repeat('=', 80));
            $this->newLine();
            
            $this->renderTableSection($content);
            
            $this->newLine();
        }
    }

    private function renderTableSection($content, $prefix = '')
    {
        foreach ($content as $key => $value) {
            $label = $prefix . ucfirst(str_replace('_', ' ', $key));
            
            if (is_array($value)) {
                $this->comment($label . ':');
                
                if ($this->isAssociativeArray($value)) {
                    $this->renderTableSection($value, '  ');
                } else {
                    foreach ($value as $item) {
                        if (is_string($item)) {
                            $this->line("  • " . $item);
                        } elseif (is_array($item)) {
                            $this->line("  • " . json_encode($item));
                        }
                    }
                }
            } else {
                $this->line($label . ': ' . $value);
            }
        }
    }

    private function exportDocumentation($docs, $format)
    {
        $path = $this->option('output');
        
        if ($format === 'json') {
            $content = json_encode($docs, JSON_PRETTY_PRINT);
        } elseif ($format === 'markdown') {
            $content = $this->generateMarkdownContent($docs);
        } else {
            $content = json_encode($docs, JSON_PRETTY_PRINT);
        }
        
        file_put_contents($path, $content);
        
        $this->info("✓ Documentation exported to: {$path}");
        $this->newLine();
    }

    private function generateMarkdownContent($docs)
    {
        $markdown = "# Project Documentation\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $markdown .= "---\n\n";
        
        foreach ($docs as $type => $content) {
            $markdown .= "## " . ucfirst($type) . "\n\n";
            $markdown .= $this->arrayToMarkdown($content);
            $markdown .= "\n---\n\n";
        }
        
        return $markdown;
    }

    private function arrayToMarkdown($array, $level = 3)
    {
        $markdown = '';
        
        foreach ($array as $key => $value) {
            $heading = str_repeat('#', $level) . ' ' . ucfirst(str_replace('_', ' ', $key));
            $markdown .= $heading . "\n\n";
            
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    $markdown .= $this->arrayToMarkdown($value, $level + 1);
                } else {
                    foreach ($value as $item) {
                        if (is_string($item)) {
                            $markdown .= "- " . $item . "\n";
                        } elseif (is_array($item)) {
                            $markdown .= "```json\n";
                            $markdown .= json_encode($item, JSON_PRETTY_PRINT) . "\n";
                            $markdown .= "```\n\n";
                        }
                    }
                }
            } else {
                $markdown .= $value . "\n";
            }
            
            $markdown .= "\n";
        }
        
        return $markdown;
    }

    private function isAssociativeArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
