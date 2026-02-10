<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class StaticAnalysisService
{
    public function listPhpFiles(array $paths)
    {
        $files = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    public function extractModels(array $files)
    {
        $models = [];

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false || strpos($content, 'extends Model') === false) {
                continue;
            }

            $class = $this->extractClassName($content);
            if (!$class) {
                continue;
            }

            $namespace = $this->extractNamespace($content);
            $fqcn = $namespace ? $namespace . '\\' . $class : $class;

            $models[] = [
                'file' => $file,
                'class' => $fqcn,
                'table' => $this->extractPropertyString($content, 'table'),
                'connection' => $this->extractPropertyString($content, 'connection'),
                'fillable' => $this->extractArrayProperty($content, 'fillable'),
                'casts' => $this->extractAssocArrayProperty($content, 'casts'),
            ];
        }

        return $models;
    }

    public function extractModelRelations(array $models)
    {
        $relations = [];
        $types = ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany', 'morphTo', 'morphMany', 'morphOne'];

        foreach ($models as $model) {
            $content = @file_get_contents($model['file']);
            if ($content === false) {
                continue;
            }

            foreach ($types as $type) {
                if (preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*\{[^\{\}]*?\$this->' . $type . '\(([^\)]*)\)/s', $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $target = $this->extractRelationTarget($match[2]);
                        $relations[] = [
                            'from' => $model['class'],
                            'type' => $type,
                            'to' => $target,
                            'method' => $match[1],
                            'fk_guess' => $this->guessForeignKey($type, $target),
                            'morph' => strpos($type, 'morph') === 0,
                        ];
                    }
                }
            }
        }

        return $relations;
    }

    public function findNPlusOneRisks(array $files)
    {
        $risks = [];

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $lines = preg_split('/\R/', $content);
            $loopIndexes = [];

            foreach ($lines as $index => $line) {
                if (preg_match('/\b(foreach|for|while)\b/', $line)) {
                    $loopIndexes[] = $index;
                }
            }

            foreach ($loopIndexes as $start) {
                $windowLines = array_slice($lines, $start, 28);
                $window = implode("\n", $windowLines);
                $line = $lines[$start];

                $patterns = [];
                $confidence = 0.0;

                if (preg_match('/->\w+->\w+/', $window)) {
                    $patterns[] = 'relation-property-chain-in-loop';
                    $confidence += 0.42;
                }

                if (preg_match('/::(find|first|where|query)\s*\(/', $window)) {
                    $patterns[] = 'query-call-in-loop';
                    $confidence += 0.35;
                }

                if (preg_match('/->load(Missing)?\s*\(/', $window)) {
                    $patterns[] = 'lazy-load-explicit-in-loop';
                    $confidence += 0.31;
                }

                if (empty($patterns)) {
                    continue;
                }

                if (strpos($window, 'with(') !== false || strpos($window, 'load(') !== false) {
                    $confidence -= 0.18;
                }

                if ($confidence < 0.2) {
                    $confidence = 0.2;
                }
                if ($confidence > 0.95) {
                    $confidence = 0.95;
                }

                $risks[] = [
                    'file' => $file,
                    'line' => $start + 1,
                    'pattern' => implode('|', $patterns),
                    'relation' => $this->extractRelationAccess($window),
                    'confidence' => round($confidence, 2),
                    'severity' => $confidence >= 0.7 ? 'high' : 'medium',
                    'fix_hint' => 'Consider eager loading with with() before iterating',
                    'evidence' => trim($line),
                ];
            }
        }

        return $risks;
    }

    public function deadCodeHints(array $files)
    {
        $classDefs = [];
        $allContents = '';
        $routeContent = '';
        $consoleContent = '';

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $allContents .= "\n" . $content;
            if (strpos($file, '/routes/') !== false) {
                $routeContent .= "\n" . $content;
            }

            if (strpos($file, '/Console/') !== false || strpos($file, '/Commands/') !== false) {
                $consoleContent .= "\n" . $content;
            }

            if (preg_match('/class\s+(\w+)/', $content, $m)) {
                $classDefs[] = [
                    'name' => $m[1],
                    'file' => $file,
                    'namespace' => $this->extractNamespace($content),
                ];
            }
        }

        $hints = [];
        foreach ($classDefs as $classDef) {
            $shortName = $classDef['name'];
            $fqcn = $classDef['namespace'] ? $classDef['namespace'] . '\\' . $shortName : $shortName;

            $shortRefs = substr_count($allContents, $shortName);
            $fqcnRefs = substr_count($allContents, $fqcn);
            $totalRefs = $shortRefs + $fqcnRefs;

            if ($totalRefs > 2) {
                continue;
            }

            $signals = [];
            if (strpos($routeContent, $shortName) !== false || strpos($routeContent, $fqcn) !== false) {
                $signals[] = 'route_reference';
            }
            if (strpos($consoleContent, $shortName) !== false || strpos($consoleContent, $fqcn) !== false) {
                $signals[] = 'console_reference';
            }

            $confidence = 0.82;
            if (!empty($signals)) {
                $confidence -= 0.32;
            }
            if (strpos($classDef['file'], '/Models/') !== false) {
                $confidence -= 0.12;
            }
            if ($totalRefs <= 1) {
                $confidence += 0.08;
            }

            if ($confidence < 0.35) {
                $confidence = 0.35;
            }
            if ($confidence > 0.95) {
                $confidence = 0.95;
            }

            $hints[] = [
                'symbol' => $shortName,
                'type' => 'class',
                'evidence_missing_refs' => 'Low reference count detected',
                'confidence' => round($confidence, 2),
                'file' => $classDef['file'],
                'references_count' => $totalRefs,
                'signals' => $signals,
            ];
        }

        return $hints;
    }

    public function routeContracts($basePath, $routePrefix = null)
    {
        $routeFiles = $this->listPhpFiles([rtrim($basePath, '/') . '/routes']);
        $requestRules = $this->discoverFormRequests($basePath);
        $resourceFields = $this->discoverJsonResources($basePath);

        $contracts = [];
        foreach ($routeFiles as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $routeBlocks = $this->extractRouteStatements($content);
            foreach ($routeBlocks as $routeBlock) {
                $path = $routeBlock['path'];
                if ($routePrefix && strpos($path, $routePrefix) !== 0) {
                    continue;
                }

                $controllerAction = $routeBlock['action'];
                $endpointRequestRules = $this->matchRequestRulesForController($controllerAction, $requestRules);
                $endpointResourceFields = $this->matchResourceFieldsForController($controllerAction, $resourceFields);

                $contracts[] = [
                    'method' => $routeBlock['method'],
                    'path' => $path,
                    'controller_action' => $controllerAction,
                    'request_rules' => $endpointRequestRules,
                    'resource_fields' => $endpointResourceFields,
                    'middleware' => $routeBlock['middleware'],
                    'auth' => $this->hasAuthSignals($routeBlock['middleware'], $routeBlock['source']),
                    'source_file' => $file,
                ];
            }
        }

        return $contracts;
    }

    public function policyMatrix($basePath, $routePrefix = null)
    {
        $contracts = $this->routeContracts($basePath, $routePrefix);
        $matrix = [];

        foreach ($contracts as $endpoint) {
            $policy = $this->extractPolicySignal($endpoint['controller_action'], $endpoint['middleware']);
            $status = $policy ? 'protected' : 'missing_explicit_policy';

            $matrix[] = [
                'endpoint' => $endpoint['method'] . ' ' . $endpoint['path'],
                'policy' => $policy,
                'model' => $this->inferModelFromAction($endpoint['controller_action']),
                'status' => $status,
                'source_file' => $endpoint['source_file'],
            ];
        }

        return $matrix;
    }

    private function extractRouteStatements($content)
    {
        $routes = [];

        if (!preg_match_all('/Route::(get|post|put|patch|delete|apiResource|resource)\s*\((.*?)\)\s*;/is', $content, $matches, PREG_SET_ORDER)) {
            return $routes;
        }

        foreach ($matches as $match) {
            $verb = strtolower($match[1]);
            $args = $match[2];
            $source = $match[0];

            if (($verb === 'apiresource' || $verb === 'resource') && preg_match('/[\'\"]([^\'\"]+)[\'\"]\s*,\s*([A-Za-z0-9_\\\\:]+)/', $args, $resourceMatch)) {
                $path = $resourceMatch[1];
                $controller = $resourceMatch[2];
                $resourceActions = [
                    ['GET', '/' . trim($path, '/'), 'index'],
                    ['POST', '/' . trim($path, '/'), 'store'],
                    ['GET', '/' . trim($path, '/') . '/{id}', 'show'],
                    ['PUT', '/' . trim($path, '/') . '/{id}', 'update'],
                    ['PATCH', '/' . trim($path, '/') . '/{id}', 'update'],
                    ['DELETE', '/' . trim($path, '/') . '/{id}', 'destroy'],
                ];

                foreach ($resourceActions as $entry) {
                    $routes[] = [
                        'method' => $entry[0],
                        'path' => $entry[1],
                        'action' => $controller . '@' . $entry[2],
                        'middleware' => $this->extractMiddlewareFromStatement($source),
                        'source' => $source,
                    ];
                }

                continue;
            }

            if (!preg_match('/[\'\"]([^\'\"]+)[\'\"]/', $args, $pathMatch)) {
                continue;
            }

            $path = $pathMatch[1];
            $action = $this->extractControllerActionFromArgs($args);
            $routes[] = [
                'method' => strtoupper($verb),
                'path' => $path,
                'action' => $action,
                'middleware' => $this->extractMiddlewareFromStatement($source),
                'source' => $source,
            ];
        }

        return $routes;
    }

    private function discoverFormRequests($basePath)
    {
        $files = $this->listPhpFiles([
            rtrim($basePath, '/') . '/app/Http/Requests',
            rtrim($basePath, '/') . '/app/Requests',
        ]);

        $requests = [];
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false || strpos($content, 'extends FormRequest') === false) {
                continue;
            }

            $class = $this->extractClassName($content);
            if (!$class) {
                continue;
            }

            $rules = [];
            if (preg_match('/function\s+rules\s*\([^)]*\)\s*\{(.*?)\}/is', $content, $rulesBlock)) {
                if (preg_match('/return\s*\[(.*?)\];/is', $rulesBlock[1], $arrayBlock)) {
                    if (preg_match_all('/[\'\"]([^\'\"]+)[\'\"]\s*=>\s*([^,\n]+)/', $arrayBlock[1], $rulePairs, PREG_SET_ORDER)) {
                        foreach ($rulePairs as $pair) {
                            $rules[trim($pair[1])] = trim($pair[2]);
                        }
                    }
                }
            }

            $requests[$class] = $rules;
        }

        return $requests;
    }

    private function discoverJsonResources($basePath)
    {
        $files = $this->listPhpFiles([
            rtrim($basePath, '/') . '/app/Http/Resources',
            rtrim($basePath, '/') . '/app/Resources',
        ]);

        $resources = [];
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false || strpos($content, 'extends JsonResource') === false) {
                continue;
            }

            $class = $this->extractClassName($content);
            if (!$class) {
                continue;
            }

            $fields = [];
            if (preg_match('/function\s+toArray\s*\([^)]*\)\s*\{(.*?)\}/is', $content, $block)) {
                if (preg_match('/return\s*\[(.*?)\];/is', $block[1], $arrayBlock)) {
                    if (preg_match_all('/[\'\"]([^\'\"]+)[\'\"]\s*=>/i', $arrayBlock[1], $fieldMatches)) {
                        $fields = array_values(array_unique($fieldMatches[1]));
                    }
                }
            }

            $resources[$class] = $fields;
        }

        return $resources;
    }

    private function matchRequestRulesForController($controllerAction, array $requestRules)
    {
        if (!$controllerAction) {
            return [];
        }

        foreach ($requestRules as $class => $rules) {
            if (stripos($controllerAction, $class) !== false) {
                return $rules;
            }
        }

        return [];
    }

    private function matchResourceFieldsForController($controllerAction, array $resourceFields)
    {
        if (!$controllerAction) {
            return [];
        }

        foreach ($resourceFields as $class => $fields) {
            if (stripos($controllerAction, $class) !== false) {
                return $fields;
            }
        }

        return [];
    }

    private function extractPolicySignal($controllerAction, array $middleware)
    {
        foreach ($middleware as $item) {
            if (strpos($item, 'can:') === 0) {
                return $item;
            }
            if (strpos($item, 'policy') !== false || strpos($item, 'authorize') !== false) {
                return $item;
            }
        }

        if ($controllerAction && (strpos($controllerAction, 'authorize(') !== false || strpos($controllerAction, 'Gate::') !== false)) {
            return 'controller-authorize-heuristic';
        }

        return null;
    }

    private function inferModelFromAction($controllerAction)
    {
        if (!$controllerAction) {
            return null;
        }

        if (preg_match('/([A-Za-z0-9_]+)Controller/', $controllerAction, $match)) {
            return $match[1];
        }

        return null;
    }

    private function hasAuthSignals(array $middleware, $source)
    {
        foreach ($middleware as $item) {
            if (strpos($item, 'auth') !== false || strpos($item, 'can:') === 0) {
                return true;
            }
        }

        return strpos($source, 'auth') !== false || strpos($source, 'can:') !== false;
    }

    private function extractControllerActionFromArgs($args)
    {
        if (preg_match('/\[\s*([A-Za-z0-9_\\\\]+)::class\s*,\s*[\'\"]([A-Za-z0-9_]+)[\'\"]\s*\]/', $args, $match)) {
            return $match[1] . '@' . $match[2];
        }

        if (preg_match('/[\'\"]([A-Za-z0-9_\\\\@]+)[\'\"]/', $args, $match)) {
            $value = $match[1];
            if (strpos($value, '@') !== false || strpos($value, 'Controller') !== false) {
                return $value;
            }
        }

        return null;
    }

    private function extractMiddlewareFromStatement($statement)
    {
        $middlewares = [];

        if (preg_match_all('/->middleware\(\s*([^)]+)\)/', $statement, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $raw = trim($match[1]);
                if (preg_match('/\[(.*?)\]/', $raw, $arrayMatch)) {
                    if (preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $arrayMatch[1], $items)) {
                        foreach ($items[1] as $item) {
                            $middlewares[] = $item;
                        }
                    }
                } elseif (preg_match('/[\'\"]([^\'\"]+)[\'\"]/', $raw, $single)) {
                    $middlewares[] = $single[1];
                }
            }
        }

        return array_values(array_unique($middlewares));
    }

    private function extractClassName($content)
    {
        if (preg_match('/class\s+(\w+)/', $content, $match)) {
            return $match[1];
        }

        return null;
    }

    private function extractNamespace($content)
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function extractPropertyString($content, $property)
    {
        if (preg_match('/\$' . preg_quote($property, '/') . '\s*=\s*[\'\"]([^\'\"]+)[\'\"]/m', $content, $match)) {
            return $match[1];
        }

        return null;
    }

    private function extractArrayProperty($content, $property)
    {
        if (!preg_match('/\$' . preg_quote($property, '/') . '\s*=\s*\[(.*?)\];/s', $content, $match)) {
            return [];
        }

        if (preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/', $match[1], $items)) {
            return $items[1];
        }

        return [];
    }

    private function extractAssocArrayProperty($content, $property)
    {
        if (!preg_match('/\$' . preg_quote($property, '/') . '\s*=\s*\[(.*?)\];/s', $content, $match)) {
            return [];
        }

        $output = [];
        if (preg_match_all('/[\'\"]([^\'\"]+)[\'\"]\s*=>\s*[\'\"]([^\'\"]+)[\'\"]/m', $match[1], $items, PREG_SET_ORDER)) {
            foreach ($items as $item) {
                $output[$item[1]] = $item[2];
            }
        }

        return $output;
    }

    private function extractRelationTarget($args)
    {
        if (preg_match('/([A-Za-z0-9_\\\\]+)::class/', $args, $match)) {
            return $match[1];
        }

        return null;
    }

    private function guessForeignKey($type, $target)
    {
        if (!$target) {
            return null;
        }

        $segments = explode('\\\\', $target);
        $name = strtolower(end($segments));

        if ($type === 'belongsTo') {
            return $name . '_id';
        }

        return null;
    }

    private function extractRelationAccess($window)
    {
        if (preg_match('/->(\w+)->(\w+)/', $window, $match)) {
            return $match[1] . '.' . $match[2];
        }

        if (preg_match('/->(\w+)\s*\(/', $window, $match)) {
            return $match[1];
        }

        return null;
    }
}
