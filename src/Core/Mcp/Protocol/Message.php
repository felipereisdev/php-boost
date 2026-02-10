<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Protocol;

class Message
{
    private $id;
    private $method;
    private $params;
    private $result;
    private $error;
    private $jsonrpc;

    public function __construct($id = null, $method = null, array $params = [], $result = null, array $error = null)
    {
        $this->id = $id;
        $this->method = $method;
        $this->params = $params;
        $this->result = $result;
        $this->error = $error;
        $this->jsonrpc = '2.0';
    }

    public static function fromArray(array $data)
    {
        return new self(
            $data['id'] ?? null,
            $data['method'] ?? null,
            $data['params'] ?? [],
            $data['result'] ?? null,
            $data['error'] ?? null
        );
    }

    public function toArray()
    {
        $data = ['jsonrpc' => $this->jsonrpc];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->method !== null) {
            $data['method'] = $this->method;
        }

        if (!empty($this->params)) {
            $data['params'] = $this->params;
        }

        if ($this->result !== null) {
            $data['result'] = $this->result;
        }

        if ($this->error !== null) {
            $data['error'] = $this->error;
        }

        return $data;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getError()
    {
        return $this->error;
    }

    public function isRequest()
    {
        return $this->method !== null;
    }

    public function isResponse()
    {
        return $this->result !== null || $this->error !== null;
    }

    public function isNotification()
    {
        return $this->method !== null && $this->id === null;
    }
}
