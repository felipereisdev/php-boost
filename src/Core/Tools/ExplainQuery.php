<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

use FelipeReisDev\PhpBoost\Core\Services\DatabaseIntrospectorService;
use FelipeReisDev\PhpBoost\Core\Support\ToolResult;

class ExplainQuery extends AbstractTool
{
    public function getName()
    {
        return 'ExplainQuery';
    }

    public function getDescription()
    {
        return 'Run EXPLAIN with best available driver options and summarize bottlenecks';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'SELECT/CTE SQL query'],
                'analyze' => ['type' => 'boolean', 'default' => true],
                'buffers' => ['type' => 'boolean', 'default' => true],
                'format' => ['type' => 'string', 'enum' => ['json'], 'default' => 'json'],
                'timeout_ms' => ['type' => 'integer', 'default' => 3000],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments)
    {
        $start = microtime(true);
        $this->validateArguments($arguments, ['query']);

        $service = new DatabaseIntrospectorService($this->config);
        $response = $service->explain($arguments['query'], $arguments);

        $plan = $response['plan'];
        $warnings = $response['warnings'];

        $summary = $this->summarizePlan($plan);
        $bottlenecks = $this->findBottlenecks($plan);
        $suggestions = $this->buildSuggestions($bottlenecks);

        $meta = [
            'duration_ms' => round((microtime(true) - $start) * 1000),
            'driver' => $service->getDriver(),
            'limitations' => $warnings,
        ];

        $payload = [
            'plan_raw' => $plan,
            'cost_summary' => $summary,
            'bottlenecks' => $bottlenecks,
            'suggestions' => $suggestions,
        ];

        if (!empty($warnings)) {
            return ToolResult::warning($this->getName(), 'Explain generated with limited driver capabilities', $payload, $meta);
        }

        return ToolResult::success($this->getName(), 'Explain generated successfully', $payload, $meta);
    }

    private function summarizePlan($plan)
    {
        if (isset($plan[0]['Plan'])) {
            $node = $plan[0]['Plan'];
            return [
                'startup_cost' => isset($node['Startup Cost']) ? $node['Startup Cost'] : null,
                'total_cost' => isset($node['Total Cost']) ? $node['Total Cost'] : null,
                'estimated_rows' => isset($node['Plan Rows']) ? $node['Plan Rows'] : null,
                'node_type' => isset($node['Node Type']) ? $node['Node Type'] : null,
            ];
        }

        if (isset($plan['query_block'])) {
            return [
                'startup_cost' => null,
                'total_cost' => isset($plan['query_block']['cost_info']['query_cost']) ? $plan['query_block']['cost_info']['query_cost'] : null,
                'estimated_rows' => null,
                'node_type' => 'mysql_query_block',
            ];
        }

        return [
            'startup_cost' => null,
            'total_cost' => null,
            'estimated_rows' => null,
            'node_type' => 'fallback',
        ];
    }

    private function findBottlenecks($plan)
    {
        $text = json_encode($plan);
        $findings = [];

        if ($text && stripos($text, 'Seq Scan') !== false) {
            $findings[] = ['type' => 'seq_scan', 'message' => 'Sequential scan detected'];
        }

        if ($text && stripos($text, 'Nested Loop') !== false) {
            $findings[] = ['type' => 'nested_loop', 'message' => 'Nested loop detected'];
        }

        if ($text && stripos($text, 'Sort') !== false) {
            $findings[] = ['type' => 'sort', 'message' => 'Sort node detected'];
        }

        return $findings;
    }

    private function buildSuggestions(array $bottlenecks)
    {
        $suggestions = [];

        foreach ($bottlenecks as $item) {
            if ($item['type'] === 'seq_scan') {
                $suggestions[] = 'Evaluate indexes for filter/join columns';
            }
            if ($item['type'] === 'nested_loop') {
                $suggestions[] = 'Review join cardinality and indexes on foreign keys';
            }
            if ($item['type'] === 'sort') {
                $suggestions[] = 'Consider index that matches ORDER BY';
            }
        }

        return array_values(array_unique($suggestions));
    }
}
