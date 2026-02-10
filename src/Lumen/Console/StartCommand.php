<?php

namespace FelipeReisDev\PhpBoost\Lumen\Console;

use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use Illuminate\Console\Command;

class StartCommand extends Command
{
    protected $signature = 'boost:start';
    
    protected $description = 'Start the PHP Boost MCP server';

    public function handle()
    {
        $server = app(Server::class);
        $server->start();
    }
}
