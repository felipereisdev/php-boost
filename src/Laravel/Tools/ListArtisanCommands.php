<?php

namespace FelipeReisDev\PhpBoost\Laravel\Tools;

use FelipeReisDev\PhpBoost\Core\Tools\AbstractTool;
use Illuminate\Support\Facades\Artisan;

class ListArtisanCommands extends AbstractTool
{
    public function getName()
    {
        return 'ListArtisanCommands';
    }

    public function getDescription()
    {
        return 'List all available Artisan commands in the Laravel/Lumen application';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'namespace' => [
                    'type' => 'string',
                    'description' => 'Filter by command namespace (e.g., "make", "migrate", "cache")',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search for commands containing this text',
                ],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $namespace = $arguments['namespace'] ?? null;
        $search = $arguments['search'] ?? null;

        try {
            $allCommands = Artisan::all();
            
            $commands = [];

            foreach ($allCommands as $name => $command) {
                if ($namespace && strpos($name, $namespace . ':') !== 0) {
                    continue;
                }

                if ($search && stripos($name, $search) === false && stripos($command->getDescription(), $search) === false) {
                    continue;
                }

                $commands[] = [
                    'name' => $name,
                    'description' => $command->getDescription(),
                    'hidden' => $command->isHidden(),
                ];
            }

            usort($commands, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return [
                'total' => count($commands),
                'commands' => $commands,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
