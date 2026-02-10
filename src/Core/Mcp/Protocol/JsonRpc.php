<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp\Protocol;

class JsonRpc
{
    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;

    public static function decode($json)
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON parse error: ' . json_last_error_msg(), self::PARSE_ERROR);
        }

        if (!isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            throw new \RuntimeException('Invalid JSON-RPC version', self::INVALID_REQUEST);
        }

        return Message::fromArray($data);
    }

    public static function encode(Message $message)
    {
        return json_encode($message->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function createErrorResponse($id, $code, $message, $data = null)
    {
        return Response::error($id, $code, $message, $data);
    }
}
