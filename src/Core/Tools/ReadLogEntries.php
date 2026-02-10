<?php

namespace FelipeReisDev\PhpBoost\Core\Tools;

class ReadLogEntries extends AbstractTool
{
    public function getName()
    {
        return 'ReadLogEntries';
    }

    public function getDescription()
    {
        return 'Read the last N entries from application logs';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'lines' => [
                    'type' => 'integer',
                    'description' => 'Number of lines to read from the end of the log',
                    'default' => 50,
                ],
                'file' => [
                    'type' => 'string',
                    'description' => 'Log file path (optional, uses default if not provided)',
                ],
            ],
        ];
    }

    public function execute(array $arguments)
    {
        $lines = $arguments['lines'] ?? 50;
        $file = $arguments['file'] ?? $this->getConfig('log_path');

        if (!$file) {
            throw new \RuntimeException('Log file path not configured');
        }

        if (!file_exists($file)) {
            return [
                'file' => $file,
                'entries' => [],
                'message' => 'Log file does not exist',
            ];
        }

        if (!is_readable($file)) {
            throw new \RuntimeException("Log file is not readable: {$file}");
        }

        $entries = $this->readLastLines($file, $lines);

        return [
            'file' => $file,
            'entries' => $entries,
            'count' => count($entries),
        ];
    }

    private function readLastLines($file, $lines)
    {
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            throw new \RuntimeException("Cannot open log file: {$file}");
        }

        $buffer = [];
        $lineCount = 0;

        fseek($handle, -1, SEEK_END);
        $pos = ftell($handle);

        while ($pos > 0 && $lineCount < $lines) {
            $char = fgetc($handle);
            
            if ($char === "\n") {
                $lineCount++;
                if ($lineCount >= $lines) {
                    break;
                }
            }
            
            $pos--;
            fseek($handle, $pos, SEEK_SET);
        }

        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line !== false && trim($line) !== '') {
                $buffer[] = rtrim($line);
            }
        }

        fclose($handle);

        return array_slice($buffer, -$lines);
    }
}
