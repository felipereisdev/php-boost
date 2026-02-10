<?php

namespace FelipeReisDev\PhpBoost\Laravel\Console;

use Illuminate\Console\Command;
use FelipeReisDev\PhpBoost\Core\Tools\ProjectInspector;
use FelipeReisDev\PhpBoost\Core\Services\SnippetGenerator;

class SnippetCommand extends Command
{
    protected $signature = 'boost:snippet 
                            {type : Type of snippet (controller, model, service, etc.)}
                            {--name= : Name of the generated class}
                            {--namespace= : Custom namespace}
                            {--resource : Generate resource controller (for controllers)}
                            {--with-factory : Generate model with factory trait (for models)}
                            {--model= : Model name (for repositories)}
                            {--table= : Table name (for models and migrations)}
                            {--action= : Migration action (for migrations)}
                            {--output= : Output file path (optional)}
                            {--list : List available snippet types}';

    protected $description = 'Generate code snippets following project guidelines';

    public function handle()
    {
        if ($this->option('list')) {
            return $this->listAvailableTypes();
        }

        $type = $this->argument('type');
        
        $this->info('PHP Boost - Code Snippet Generator');
        $this->info('===================================');
        $this->newLine();

        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';
        
        $inspector = new ProjectInspector($rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $customSnippetsPath = $rootPath . '/.php-boost/snippets';
        $generator = new SnippetGenerator($projectInfo, $customSnippetsPath);

        try {
            $options = $this->gatherOptions($type);
            $content = $generator->generate($type, $options);
            
            if ($this->option('output')) {
                $outputPath = $this->option('output');
                file_put_contents($outputPath, $content);
                $this->info("✓ Snippet generated: {$outputPath}");
            } else {
                $this->newLine();
                $this->line($content);
                $this->newLine();
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to generate snippet: ' . $e->getMessage());
            return 1;
        }
    }

    private function listAvailableTypes()
    {
        $rootPath = base_path();
        $composerPath = $rootPath . '/composer.json';
        
        $inspector = new ProjectInspector($rootPath, $composerPath);
        $projectInfo = $inspector->inspect();

        $customSnippetsPath = $rootPath . '/.php-boost/snippets';
        $generator = new SnippetGenerator($projectInfo, $customSnippetsPath);

        $types = $generator->getAvailableTypes();

        $this->info('Available Snippet Types:');
        $this->newLine();

        foreach ($types as $type) {
            $this->line("  • {$type}");
        }

        $this->newLine();
        $this->comment('Usage: php artisan boost:snippet {type} --name=ClassName');
        
        return 0;
    }

    private function gatherOptions($type)
    {
        $options = [];
        
        if ($this->option('name')) {
            $options['name'] = $this->option('name');
        }
        
        if ($this->option('namespace')) {
            $options['namespace'] = $this->option('namespace');
        }
        
        if ($this->option('resource')) {
            $options['resource'] = true;
        }
        
        if ($this->option('with-factory')) {
            $options['with-factory'] = true;
        }
        
        if ($this->option('model')) {
            $options['model'] = $this->option('model');
        }
        
        if ($this->option('table')) {
            $options['table'] = $this->option('table');
        }
        
        if ($this->option('action')) {
            $options['action'] = $this->option('action');
        }

        return $options;
    }
}
