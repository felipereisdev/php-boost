<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Mcp\Protocol\Message;
use FelipeReisDev\PhpBoost\Core\Mcp\Protocol\JsonRpc;
use FelipeReisDev\PhpBoost\Core\Mcp\Protocol\Request;
use FelipeReisDev\PhpBoost\Core\Mcp\Protocol\Response;
use PHPUnit\Framework\TestCase;

class JsonRpcTest extends TestCase
{
    public function testDecodeRequest()
    {
        $json = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'test',
            'params' => ['key' => 'value'],
        ]);

        $message = JsonRpc::decode($json);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(1, $message->getId());
        $this->assertEquals('test', $message->getMethod());
        $this->assertEquals(['key' => 'value'], $message->getParams());
        $this->assertTrue($message->isRequest());
    }

    public function testDecodeInvalidJson()
    {
        $this->expectException(\RuntimeException::class);
        JsonRpc::decode('invalid json');
    }

    public function testEncodeResponse()
    {
        $response = Response::success(1, ['result' => 'ok']);
        $json = JsonRpc::encode($response);

        $data = json_decode($json, true);

        $this->assertEquals('2.0', $data['jsonrpc']);
        $this->assertEquals(1, $data['id']);
        $this->assertEquals(['result' => 'ok'], $data['result']);
    }

    public function testEncodeErrorResponse()
    {
        $response = Response::error(1, -32600, 'Invalid request');
        $json = JsonRpc::encode($response);

        $data = json_decode($json, true);

        $this->assertEquals('2.0', $data['jsonrpc']);
        $this->assertEquals(1, $data['id']);
        $this->assertEquals(-32600, $data['error']['code']);
        $this->assertEquals('Invalid request', $data['error']['message']);
    }
}
