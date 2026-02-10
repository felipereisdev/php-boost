<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Contracts;

interface TransportInterface
{
    public function read();

    public function write($data);

    public function close();
}
