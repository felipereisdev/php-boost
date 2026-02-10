<?php

namespace FelipeReisDev\PhpBoost\Laravel\Tools;

use FelipeReisDev\PhpBoost\Core\Tools\AbstractTool;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class QueueStatus extends AbstractTool
{
    public function getName()
    {
        return 'QueueStatus';
    }

    public function getDescription()
    {
        return 'Check queue status, list pending and failed jobs';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['pending', 'failed', 'stats', 'retry'],
                    'description' => 'Type of information to retrieve',
                ],
                'queue' => [
                    'type' => 'string',
                    'description' => 'Queue name to filter (optional)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Limit number of results',
                    'default' => 50,
                ],
                'job_id' => [
                    'type' => 'string',
                    'description' => 'Job ID to retry (required for retry type)',
                ],
            ],
            'required' => ['type'],
        ];
    }

    public function isReadOnly()
    {
        return false;
    }

    public function execute(array $arguments)
    {
        $this->validateArguments($arguments, ['type']);

        $type = $arguments['type'];
        
        $validTypes = ['pending', 'failed', 'stats', 'retry'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid type: {$type}. Valid types: " . implode(', ', $validTypes));
        }
        
        $queue = $arguments['queue'] ?? null;
        $limit = $arguments['limit'] ?? 50;

        try {
            switch ($type) {
                case 'pending':
                    return $this->getPendingJobs($queue, $limit);

                case 'failed':
                    return $this->getFailedJobs($queue, $limit);

                case 'stats':
                    return $this->getQueueStats($queue);

                case 'retry':
                    $jobId = $arguments['job_id'] ?? null;
                    if (!$jobId) {
                        throw new \InvalidArgumentException('job_id is required for retry operation');
                    }
                    return $this->retryJob($jobId);

                default:
                    throw new \InvalidArgumentException("Invalid type: {$type}");
            }
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'type' => $type,
            ];
        }
    }

    private function getPendingJobs($queue, $limit)
    {
        $query = DB::table('jobs');

        if ($queue) {
            $query->where('queue', $queue);
        }

        $jobs = $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'attempts' => $job->attempts,
                    'reserved_at' => $job->reserved_at,
                    'available_at' => date('Y-m-d H:i:s', $job->available_at),
                    'created_at' => date('Y-m-d H:i:s', $job->created_at),
                ];
            })
            ->toArray();

        return [
            'type' => 'pending',
            'count' => count($jobs),
            'jobs' => $jobs,
        ];
    }

    private function getFailedJobs($queue, $limit)
    {
        $query = DB::table('failed_jobs');

        if ($queue) {
            $query->where('queue', $queue);
        }

        $jobs = $query
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'exception' => substr($job->exception, 0, 500),
                    'failed_at' => $job->failed_at,
                ];
            })
            ->toArray();

        return [
            'type' => 'failed',
            'count' => count($jobs),
            'jobs' => $jobs,
        ];
    }

    private function getQueueStats($queue)
    {
        $stats = [
            'type' => 'stats',
        ];

        if (DB::getSchemaBuilder()->hasTable('jobs')) {
            $pendingQuery = DB::table('jobs');
            if ($queue) {
                $pendingQuery->where('queue', $queue);
            }
            $stats['pending'] = $pendingQuery->count();
        }

        if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $failedQuery = DB::table('failed_jobs');
            if ($queue) {
                $failedQuery->where('queue', $queue);
            }
            $stats['failed'] = $failedQuery->count();
        }

        if (!$queue && DB::getSchemaBuilder()->hasTable('jobs')) {
            $stats['queues'] = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get()
                ->pluck('count', 'queue')
                ->toArray();
        }

        return $stats;
    }

    private function retryJob($jobId)
    {
        $job = DB::table('failed_jobs')->where('id', $jobId)->first();

        if (!$job) {
            throw new \RuntimeException("Failed job not found: {$jobId}");
        }

        DB::table('jobs')->insert([
            'queue' => $job->queue,
            'payload' => $job->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        DB::table('failed_jobs')->where('id', $jobId)->delete();

        return [
            'type' => 'retry',
            'job_id' => $jobId,
            'success' => true,
            'message' => 'Job re-queued successfully',
        ];
    }
}
