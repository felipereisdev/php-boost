<?php

namespace FelipeReisDev\PhpBoost\Core\Mcp;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\TransportInterface;
use FelipeReisDev\PhpBoost\Core\Mcp\Protocol\JsonRpc;
use FelipeReisDev\PhpBoost\Core\Mcp\Protocol\Response;
use FelipeReisDev\PhpBoost\Core\Mcp\Registry\ToolRegistry;

class Server
{
    private $transport;
    private $toolRegistry;
    private $config;
    private $initialized = false;
    private $serverInfo = [
        'name' => 'php-boost',
        'version' => '1.0.0',
    ];

    public function __construct(TransportInterface $transport, array $config = [])
    {
        $this->transport = $transport;
        $this->config = $config;
        $this->toolRegistry = new ToolRegistry();
    }

    public function getToolRegistry()
    {
        return $this->toolRegistry;
    }

    public function start()
    {
        while (true) {
            $input = $this->transport->read();

            if ($input === null) {
                break;
            }

            if ($input === '') {
                continue;
            }

            try {
                $message = JsonRpc::decode($input);
                $response = $this->handleMessage($message);

                if ($response !== null) {
                    $this->transport->write(JsonRpc::encode($response));
                }
            } catch (\Exception $e) {
                $errorResponse = JsonRpc::createErrorResponse(
                    null,
                    JsonRpc::INTERNAL_ERROR,
                    $e->getMessage()
                );
                $this->transport->write(JsonRpc::encode($errorResponse));
            }
        }
    }

    private function handleMessage($message)
    {
        if ($message->isNotification()) {
            return null;
        }

        if (!$message->isRequest()) {
            return JsonRpc::createErrorResponse(
                $message->getId(),
                JsonRpc::INVALID_REQUEST,
                'Invalid request'
            );
        }

        $method = $message->getMethod();

        switch ($method) {
            case 'initialize':
                return $this->handleInitialize($message);
            case 'tools/list':
                return $this->handleToolsList($message);
            case 'tools/call':
                return $this->handleToolsCall($message);
            case 'ping':
                return $this->handlePing($message);
            default:
                return JsonRpc::createErrorResponse(
                    $message->getId(),
                    JsonRpc::METHOD_NOT_FOUND,
                    "Method '{$method}' not found"
                );
        }
    }

    private function handleInitialize($message)
    {
        $params = $message->getParams();
        
        $this->initialized = true;

        return Response::success($message->getId(), [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => (object)[],
                'resources' => (object)[],
                'prompts' => (object)[],
            ],
            'serverInfo' => $this->serverInfo,
        ]);
    }

    private function handleToolsList($message)
    {
        if (!$this->initialized) {
            return JsonRpc::createErrorResponse(
                $message->getId(),
                JsonRpc::INTERNAL_ERROR,
                'Server not initialized'
            );
        }

        return Response::success($message->getId(), [
            'tools' => $this->toolRegistry->list(),
        ]);
    }

    private function handleToolsCall($message)
    {
        if (!$this->initialized) {
            return JsonRpc::createErrorResponse(
                $message->getId(),
                JsonRpc::INTERNAL_ERROR,
                'Server not initialized'
            );
        }

        $params = $message->getParams();
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!$toolName) {
            return JsonRpc::createErrorResponse(
                $message->getId(),
                JsonRpc::INVALID_PARAMS,
                'Tool name is required'
            );
        }

        try {
            $startTime = microtime(true);
            $tool = $this->toolRegistry->get($toolName);
            $result = $tool->execute($arguments);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $result = $this->normalizeResultEnvelope($toolName, $result, $durationMs);

            $encodedResult = $this->encodeToolResult($result);

            return Response::success($message->getId(), [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $encodedResult,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $errorCode = JsonRpc::INTERNAL_ERROR;
            if (strpos($e->getMessage(), "Tool '{$toolName}' not found") !== false) {
                $errorCode = JsonRpc::METHOD_NOT_FOUND;
            }

            return JsonRpc::createErrorResponse(
                $message->getId(),
                $errorCode,
                $e->getMessage()
            );
        }
    }

    private function handlePing($message)
    {
        return Response::success($message->getId(), (object)[]);
    }

    private function encodeToolResult($result)
    {
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            return $json;
        }

        return json_encode(
            ['tool_result_encoding_error' => 'Unable to encode tool response', 'type' => gettype($result)],
            JSON_PRETTY_PRINT
        );
    }

    private function normalizeResultEnvelope($toolName, $result, $durationMs)
    {
        if (is_array($result) && isset($result['tool']) && isset($result['status']) && isset($result['meta']) && is_array($result['meta'])) {
            $result['meta']['duration_ms'] = $durationMs;
            $result['meta']['tool'] = $toolName;
            return $result;
        }

        $summary = is_string($result) ? $result : 'Tool executed successfully';
        $data = is_array($result) ? $result : ['value' => $result];

        return [
            'tool' => $toolName,
            'status' => 'ok',
            'summary' => $summary,
            'meta' => [
                'version' => '1.0.0',
                'generated_at' => date('c'),
                'duration_ms' => $durationMs,
                'tool' => $toolName,
                'normalized_legacy_output' => true,
            ],
            'data' => $data,
        ];
    }
}
