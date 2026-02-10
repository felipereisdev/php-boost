<?php

namespace FelipeReisDev\PhpBoost\Standalone;

class TemplateScaffold
{
    private $rootPath;
    private $templatesPath;

    public function __construct($rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->templatesPath = $this->rootPath . '/.php-boost/templates';
    }

    public function scaffold($type, $name, $options = [])
    {
        $validTypes = ['php', 'framework', 'database', 'environment', 'package'];
        
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid type: {$type}. Valid types: " . implode(', ', $validTypes));
        }

        $templatePath = $this->getTemplatePath($type, $name);
        
        if (file_exists($templatePath) && empty($options['force'])) {
            throw new \RuntimeException("Template already exists: {$templatePath}");
        }

        $this->ensureDirectoryExists(dirname($templatePath));
        
        $content = $this->generateTemplateContent($type, $name, $options);
        
        file_put_contents($templatePath, $content);
        
        return $templatePath;
    }

    public function list($type = null)
    {
        if (!is_dir($this->templatesPath)) {
            return [];
        }

        $templates = [];
        
        if ($type) {
            $typePath = $this->templatesPath . '/' . ucfirst($type);
            
            if (is_dir($typePath)) {
                $templates[$type] = $this->scanDirectory($typePath);
            }
        } else {
            $types = ['Php', 'Framework', 'Database', 'Environment', 'Packages'];
            
            foreach ($types as $typeDir) {
                $typePath = $this->templatesPath . '/' . $typeDir;
                
                if (is_dir($typePath)) {
                    $templates[strtolower($typeDir)] = $this->scanDirectory($typePath);
                }
            }
        }
        
        return $templates;
    }

    private function getTemplatePath($type, $name)
    {
        $typeMap = [
            'php' => 'Php',
            'framework' => 'Framework',
            'database' => 'Database',
            'environment' => 'Environment',
            'package' => 'Packages',
        ];

        $typeDir = $typeMap[$type];
        
        if ($type === 'framework') {
            list($framework, $version) = $this->parseFrameworkName($name);
            return $this->templatesPath . '/' . $typeDir . '/' . ucfirst($framework) . '/' . strtolower($framework) . $version . '.php';
        }
        
        if ($type === 'php') {
            return $this->templatesPath . '/' . $typeDir . '/php' . $name . '.php';
        }
        
        return $this->templatesPath . '/' . $typeDir . '/' . strtolower($name) . '.php';
    }

    private function parseFrameworkName($name)
    {
        if (preg_match('/^([a-z]+)(\d+)$/', strtolower($name), $matches)) {
            return [$matches[1], $matches[2]];
        }
        
        throw new \InvalidArgumentException("Invalid framework name format: {$name}. Use format like 'laravel11' or 'lumen8'");
    }

    private function generateTemplateContent($type, $name, $options)
    {
        $mergeStrategy = $options['merge'] ?? 'replace';
        
        $content = "<?php\n\n";
        $content .= "return <<<'MARKDOWN'\n";
        
        if ($mergeStrategy === 'append') {
            $content .= "<!-- MERGE:APPEND -->\n\n";
        } elseif ($mergeStrategy === 'prepend') {
            $content .= "<!-- MERGE:PREPEND -->\n\n";
        }
        
        $content .= $this->getTemplateBoilerplate($type, $name);
        
        $content .= "\nMARKDOWN;\n";
        
        return $content;
    }

    private function getTemplateBoilerplate($type, $name)
    {
        switch ($type) {
            case 'php':
                return "## PHP {$name} Best Practices\n\n"
                    . "### Features\n"
                    . "- Feature 1\n"
                    . "- Feature 2\n\n"
                    . "### Restrictions\n"
                    . "- Restriction 1\n"
                    . "- Restriction 2\n";
            
            case 'framework':
                list($framework, $version) = $this->parseFrameworkName($name);
                return "## " . ucfirst($framework) . " {$version} Best Practices\n\n"
                    . "### Architecture\n"
                    . "- Pattern 1\n"
                    . "- Pattern 2\n\n"
                    . "### Conventions\n"
                    . "- Convention 1\n"
                    . "- Convention 2\n";
            
            case 'database':
                return "## " . ucfirst($name) . " Best Practices\n\n"
                    . "### Query Optimization\n"
                    . "- Optimization 1\n"
                    . "- Optimization 2\n\n"
                    . "### Schema Design\n"
                    . "- Pattern 1\n"
                    . "- Pattern 2\n";
            
            case 'environment':
                return "## " . ucfirst($name) . " Environment\n\n"
                    . "### Configuration\n"
                    . "- Config 1\n"
                    . "- Config 2\n\n"
                    . "### Best Practices\n"
                    . "- Practice 1\n"
                    . "- Practice 2\n";
            
            case 'package':
                return "### " . ucfirst($name) . "\n\n"
                    . "**Usage Guidelines:**\n\n"
                    . "- Guideline 1\n"
                    . "- Guideline 2\n\n"
                    . "**Common Patterns:**\n\n"
                    . "```php\n"
                    . "// Example code\n"
                    . "```\n";
            
            default:
                return "## Custom Template: {$name}\n\n- Item 1\n- Item 2\n";
        }
    }

    private function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function scanDirectory($directory)
    {
        $files = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}
