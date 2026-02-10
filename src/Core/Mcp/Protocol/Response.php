<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Protocol;

class Response extends Message
{
    public function __construct($id, $result = null, array $error = null)
    {
        parent::__construct($id, null, [], $result, $error);
    }

    public static function success($id, $result)
    {
        return new self($id, $result);
    }

    public static function error($id, $code, $message, $data = null)
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $error['data'] = $data;
        }

        return new self($id, null, $error);
    }
}
