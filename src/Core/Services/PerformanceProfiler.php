<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node;

class PerformanceProfiler
{
    private $rootPath;
    private $parser;
    private $findings;

    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
        $factory = new ParserFactory();
        $this->parser = $factory->createForHostVersion();
        $this->findings = [];
    }

    public function analyze()
    {
        $this->findings = [];
        
        $this->analyzeNPlusOneQueries();
        $this->analyzeMissingEagerLoading();
        $this->analyzeCacheOpportunities();
        $this->analyzeMemoryLeaks();
        $this->analyzeSlowQueries();
        
        return $this->generateReport();
    }

    private function analyzeNPlusOneQueries()
    {
        $files = $this->findPhpFiles($this->rootPath . '/app');
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            
            try {
                $ast = $this->parser->parse($code);
                $this->detectNPlusOne($ast, $file);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    private function detectNPlusOne($ast, $file)
    {
        $nodeFinder = new NodeFinder();
        
        $foreachNodes = $nodeFinder->findInstanceOf($ast, Node\Stmt\Foreach_::class);
        
        foreach ($foreachNodes as $foreach) {
            $methodCalls = $nodeFinder->findInstanceOf(
                [$foreach],
                Node\Expr\PropertyFetch::class
            );
            
            foreach ($methodCalls as $call) {
                if ($this->isRelationshipAccess($call)) {
                    $line = $foreach->getStartLine();
                    $this->addFinding('n_plus_one', $file, $line, [
                        'type' => 'N+1 Query Detected',
                        'severity' => 'high',
                        'description' => 'Relationship accessed inside loop without eager loading',
                        'suggestion' => 'Use ->with() to eager load relationships',
                    ]);
                }
            }
        }
    }

    private function isRelationshipAccess($node)
    {
        if (!$node instanceof Node\Expr\PropertyFetch) {
            return false;
        }
        
        $propertyName = $node->name;
        if (!is_string($propertyName) && property_exists($propertyName, 'name')) {
            $propertyName = $propertyName->name;
        }
        
        $relationshipPatterns = ['user', 'posts', 'comments', 'author', 'category', 'tags'];
        
        foreach ($relationshipPatterns as $pattern) {
            if (is_string($propertyName) && stripos($propertyName, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function analyzeMissingEagerLoading()
    {
        $files = $this->findPhpFiles($this->rootPath . '/app');
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            
            if (preg_match_all('/::all\(\)/', $code, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($code, 0, $match[1]), "\n") + 1;
                    
                    $this->addFinding('missing_eager_loading', $file, $line, [
                        'type' => 'Missing Eager Loading',
                        'severity' => 'medium',
                        'description' => 'Using Model::all() without eager loading',
                        'suggestion' => 'Consider using ->with() if relationships are accessed later',
                    ]);
                }
            }
        }
    }

    private function analyzeCacheOpportunities()
    {
        $files = $this->findPhpFiles($this->rootPath . '/app');
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            
            if (preg_match_all('/DB::select|DB::table/', $code, $matches, PREG_OFFSET_CAPTURE)) {
                $hasCache = preg_match('/Cache::remember|cache\(\)/', $code);
                
                if (!$hasCache && count($matches[0]) > 0) {
                    foreach ($matches[0] as $match) {
                        $line = substr_count(substr($code, 0, $match[1]), "\n") + 1;
                        
                        $this->addFinding('cache_opportunity', $file, $line, [
                            'type' => 'Cache Opportunity',
                            'severity' => 'low',
                            'description' => 'Database query without caching',
                            'suggestion' => 'Consider caching frequently accessed data',
                        ]);
                    }
                }
            }
        }
    }

    private function analyzeMemoryLeaks()
    {
        $files = $this->findPhpFiles($this->rootPath . '/app');
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            
            if (preg_match_all('/::chunk\((\d+)\)/', $code, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $idx => $match) {
                    $chunkSize = (int)$match[0];
                    
                    if ($chunkSize > 1000) {
                        $line = substr_count(substr($code, 0, $match[1]), "\n") + 1;
                        
                        $this->addFinding('memory_leak', $file, $line, [
                            'type' => 'Potential Memory Issue',
                            'severity' => 'medium',
                            'description' => "Large chunk size ({$chunkSize}) may cause memory issues",
                            'suggestion' => 'Consider using smaller chunk sizes (100-500)',
                        ]);
                    }
                }
            }
            
            if (preg_match('/->get\(\)|->all\(\)/', $code) && 
                preg_match('/foreach/', $code) && 
                !preg_match('/chunk|cursor/', $code)) {
                
                $this->addFinding('memory_leak', $file, 1, [
                    'type' => 'Potential Memory Issue',
                    'severity' => 'low',
                    'description' => 'Loading all records without chunking',
                    'suggestion' => 'Consider using ->chunk() or ->cursor() for large datasets',
                ]);
            }
        }
    }

    private function analyzeSlowQueries()
    {
        $files = $this->findPhpFiles($this->rootPath . '/app');
        
        foreach ($files as $file) {
            $code = file_get_contents($file);
            
            if (preg_match_all('/DB::raw\(|whereRaw\(|selectRaw\(/', $code, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($code, 0, $match[1]), "\n") + 1;
                    
                    $this->addFinding('slow_query', $file, $line, [
                        'type' => 'Raw Query Usage',
                        'severity' => 'medium',
                        'description' => 'Using raw SQL may bypass query optimization',
                        'suggestion' => 'Prefer Eloquent methods when possible',
                    ]);
                }
            }
            
            if (preg_match_all('/SELECT \*|select\(\'\*\'\)/', $code, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($code, 0, $match[1]), "\n") + 1;
                    
                    $this->addFinding('slow_query', $file, $line, [
                        'type' => 'SELECT * Usage',
                        'severity' => 'low',
                        'description' => 'Selecting all columns may impact performance',
                        'suggestion' => 'Select only needed columns',
                    ]);
                }
            }
        }
    }

    private function addFinding($category, $file, $line, $details)
    {
        if (!isset($this->findings[$category])) {
            $this->findings[$category] = [];
        }
        
        $this->findings[$category][] = array_merge([
            'file' => str_replace($this->rootPath . '/', '', $file),
            'line' => $line,
        ], $details);
    }

    private function generateReport()
    {
        $report = [
            'summary' => [
                'total_issues' => 0,
                'high_severity' => 0,
                'medium_severity' => 0,
                'low_severity' => 0,
            ],
            'categories' => [],
            'recommendations' => [],
        ];
        
        foreach ($this->findings as $category => $findings) {
            $report['categories'][$category] = [
                'count' => count($findings),
                'issues' => $findings,
            ];
            
            foreach ($findings as $finding) {
                $report['summary']['total_issues']++;
                
                switch ($finding['severity']) {
                    case 'high':
                        $report['summary']['high_severity']++;
                        break;
                    case 'medium':
                        $report['summary']['medium_severity']++;
                        break;
                    case 'low':
                        $report['summary']['low_severity']++;
                        break;
                }
            }
        }
        
        $report['recommendations'] = $this->generateRecommendations();
        $report['score'] = $this->calculatePerformanceScore();
        
        return $report;
    }

    private function generateRecommendations()
    {
        $recommendations = [];
        
        if (isset($this->findings['n_plus_one']) && count($this->findings['n_plus_one']) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Fix N+1 queries using eager loading',
                'impact' => 'High performance improvement',
            ];
        }
        
        if (isset($this->findings['cache_opportunity']) && count($this->findings['cache_opportunity']) > 5) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Implement caching for frequently accessed data',
                'impact' => 'Reduced database load',
            ];
        }
        
        if (isset($this->findings['memory_leak']) && count($this->findings['memory_leak']) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Optimize memory usage with chunking/cursor',
                'impact' => 'Prevent memory exhaustion',
            ];
        }
        
        return $recommendations;
    }

    private function calculatePerformanceScore()
    {
        $score = 100;
        
        if (isset($this->findings['n_plus_one'])) {
            $score -= count($this->findings['n_plus_one']) * 5;
        }
        
        if (isset($this->findings['missing_eager_loading'])) {
            $score -= count($this->findings['missing_eager_loading']) * 2;
        }
        
        if (isset($this->findings['cache_opportunity'])) {
            $score -= count($this->findings['cache_opportunity']) * 1;
        }
        
        if (isset($this->findings['memory_leak'])) {
            $score -= count($this->findings['memory_leak']) * 3;
        }
        
        if (isset($this->findings['slow_query'])) {
            $score -= count($this->findings['slow_query']) * 2;
        }
        
        return max(0, $score);
    }

    private function findPhpFiles($directory)
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}
