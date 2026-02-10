<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Protocol;

class Request extends Message
{
    public function __construct($id, $method, array $params = [])
    {
        parent::__construct($id, $method, $params);
    }

    public static function create($id, $method, array $params = [])
    {
        return new self($id, $method, $params);
    }
}
