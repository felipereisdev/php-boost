<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Mcp\Contracts\TransportInterface;
use FelipeReisDev\PhpBoost\Core\Mcp\Server;
use FelipeReisDev\PhpBoost\Core\Support\ToolRegistrar;
use PHPUnit\Framework\TestCase;

class McpServerE2ETest extends TestCase
{
    public function testInitializeToolsListAndToolsCallFlow()
    {
        $transport = new ArrayTransport([
            $this->request(1, 'initialize', (object) []),
            $this->request(2, 'tools/list', (object) []),
            $this->request(3, 'tools/call', [
                'name' => 'GetConfig',
                'arguments' => ['key' => 'database.driver', 'default' => 'sqlite'],
            ]),
        ]);

        $server = new Server($transport, []);
        ToolRegistrar::registerCoreTools($server->getToolRegistry(), []);

        $server->start();

        $messages = $transport->decodedWrites();
        $this->assertCount(3, $messages);

        $this->assertEquals(1, $messages[0]['id']);
        $this->assertArrayHasKey('result', $messages[0]);
        $this->assertEquals('2024-11-05', $messages[0]['result']['protocolVersion']);

        $this->assertEquals(2, $messages[1]['id']);
        $this->assertArrayHasKey('result', $messages[1]);
        $toolNames = array_map(function ($tool) {
            return $tool['name'];
        }, $messages[1]['result']['tools']);
        $this->assertContains('ExplainQuery', $toolNames);
        $this->assertContains('GetConfig', $toolNames);

        $this->assertEquals(3, $messages[2]['id']);
        $this->assertArrayHasKey('result', $messages[2]);
        $this->assertArrayHasKey('content', $messages[2]['result']);
        $this->assertSame('text', $messages[2]['result']['content'][0]['type']);
        $this->assertStringContainsString('database.driver', $messages[2]['result']['content'][0]['text']);
    }

    public function testToolsListBeforeInitializeReturnsError()
    {
        $transport = new ArrayTransport([
            $this->request(10, 'tools/list', (object) []),
        ]);

        $server = new Server($transport, []);
        ToolRegistrar::registerCoreTools($server->getToolRegistry(), []);

        $server->start();

        $messages = $transport->decodedWrites();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('error', $messages[0]);
        $this->assertSame(-32603, $messages[0]['error']['code']);
    }

    public function testUnknownToolReturnsMethodNotFound()
    {
        $transport = new ArrayTransport([
            $this->request(20, 'initialize', (object) []),
            $this->request(21, 'tools/call', [
                'name' => 'DoesNotExist',
                'arguments' => [],
            ]),
        ]);

        $server = new Server($transport, []);
        ToolRegistrar::registerCoreTools($server->getToolRegistry(), []);

        $server->start();

        $messages = $transport->decodedWrites();
        $this->assertCount(2, $messages);
        $this->assertArrayHasKey('error', $messages[1]);
        $this->assertSame(-32601, $messages[1]['error']['code']);
    }

    private function request($id, $method, $params)
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ]);
    }
}

class ArrayTransport implements TransportInterface
{
    private $queue = [];
    private $writes = [];

    public function __construct(array $messages)
    {
        $this->queue = array_values($messages);
    }

    public function read()
    {
        if (empty($this->queue)) {
            return null;
        }

        return array_shift($this->queue);
    }

    public function write($data)
    {
        $this->writes[] = $data;
    }

    public function close()
    {
    }

    public function decodedWrites()
    {
        return array_map(function ($line) {
            return json_decode($line, true);
        }, $this->writes);
    }
}
