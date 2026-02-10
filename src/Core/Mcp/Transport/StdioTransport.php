<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Transport;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\TransportInterface;

class StdioTransport implements TransportInterface
{
    private $stdin;
    private $stdout;

    public function __construct()
    {
        $this->stdin = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');
        $this->stdout = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');
    }

    public function read()
    {
        $line = fgets($this->stdin);
        
        if ($line === false) {
            return null;
        }

        return trim($line);
    }

    public function write($data)
    {
        fwrite($this->stdout, $data . "\n");
        fflush($this->stdout);
    }

    public function close()
    {
        if (is_resource($this->stdin)) {
            fclose($this->stdin);
        }
        
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
    }
}
