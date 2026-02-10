<?php

namespace FelipeReisDev\PhpBoost\Laravel\Tools;

use FelipeReisDev\PhpBoost\Core\Tools\AbstractTool;
use Illuminate\Support\Facades\Cache;

class CacheManager extends AbstractTool
{
    public function getName()
    {
        return 'CacheManager';
    }

    public function getDescription()
    {
        return 'Manage application cache (get, set, forget, clear)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'operation' => [
                    'type' => 'string',
                    'enum' => ['get', 'set', 'forget', 'clear', 'has'],
                    'description' => 'Cache operation to perform',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Cache key (required for get, set, forget, has)',
                ],
                'value' => [
                    'type' => 'string',
                    'description' => 'Value to store (required for set)',
                ],
                'ttl' => [
                    'type' => 'integer',
                    'description' => 'Time to live in seconds (optional for set, default: 3600)',
                ],
                'store' => [
                    'type' => 'string',
                    'description' => 'Cache store to use (default: default store)',
                ],
            ],
            'required' => ['operation'],
        ];
    }

    public function isReadOnly()
    {
        return false;
    }

    public function execute(array $arguments)
    {
        $this->validateArguments($arguments, ['operation']);

        $operation = $arguments['operation'];
        
        $validOperations = ['get', 'set', 'forget', 'clear', 'has'];
        if (!in_array($operation, $validOperations)) {
            throw new \InvalidArgumentException("Invalid operation: {$operation}. Valid operations: " . implode(', ', $validOperations));
        }
        
        $key = $arguments['key'] ?? null;
        $value = $arguments['value'] ?? null;
        $ttl = $arguments['ttl'] ?? 3600;
        $store = $arguments['store'] ?? null;

        $cache = $store ? Cache::store($store) : Cache::getFacadeRoot();

        try {
            switch ($operation) {
                case 'get':
                    if (!$key) {
                        throw new \InvalidArgumentException('Key is required for get operation');
                    }
                    
                    $result = $cache->get($key);
                    
                    return [
                        'operation' => 'get',
                        'key' => $key,
                        'value' => $result,
                        'exists' => $result !== null,
                    ];

                case 'set':
                    if (!$key) {
                        throw new \InvalidArgumentException('Key is required for set operation');
                    }
                    if ($value === null) {
                        throw new \InvalidArgumentException('Value is required for set operation');
                    }
                    
                    $cache->put($key, $value, $ttl);
                    
                    return [
                        'operation' => 'set',
                        'key' => $key,
                        'ttl' => $ttl,
                        'success' => true,
                    ];

                case 'forget':
                    if (!$key) {
                        throw new \InvalidArgumentException('Key is required for forget operation');
                    }
                    
                    $result = $cache->forget($key);
                    
                    return [
                        'operation' => 'forget',
                        'key' => $key,
                        'success' => $result,
                    ];

                case 'clear':
                    $result = $cache->flush();
                    
                    return [
                        'operation' => 'clear',
                        'success' => $result,
                        'message' => 'All cache cleared',
                    ];

                case 'has':
                    if (!$key) {
                        throw new \InvalidArgumentException('Key is required for has operation');
                    }
                    
                    $exists = $cache->has($key);
                    
                    return [
                        'operation' => 'has',
                        'key' => $key,
                        'exists' => $exists,
                    ];

                default:
                    throw new \InvalidArgumentException("Invalid operation: {$operation}");
            }
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'operation' => $operation,
            ];
        }
    }
}
