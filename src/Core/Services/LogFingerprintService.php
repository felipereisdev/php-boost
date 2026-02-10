<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class LogFingerprintService
{
    public function digest($path, $windowMinutes, $limit, $groupBy)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('Log file not found: ' . $path);
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Unable to read log file: ' . $path);
        }

        $slice = array_slice($lines, -1 * (int) $limit);
        $groups = [];
        $cutoff = time() - ((int) $windowMinutes * 60);

        foreach ($slice as $line) {
            $parsed = $this->parseLine($line);
            if (!$parsed['message']) {
                continue;
            }

            if ($parsed['timestamp'] && strtotime($parsed['timestamp']) !== false && strtotime($parsed['timestamp']) < $cutoff) {
                continue;
            }

            $target = isset($parsed[$groupBy]) ? $parsed[$groupBy] : $parsed['message'];
            $fingerprint = $this->fingerprint($target);

            if (!isset($groups[$fingerprint])) {
                $groups[$fingerprint] = [
                    'fingerprint' => $fingerprint,
                    'count' => 0,
                    'first_seen' => $parsed['timestamp'],
                    'last_seen' => $parsed['timestamp'],
                    'sample_message' => $parsed['message'],
                    'sample_context' => $parsed['context'],
                ];
            }

            $groups[$fingerprint]['count']++;
            if ($parsed['timestamp']) {
                if (!$groups[$fingerprint]['first_seen'] || $parsed['timestamp'] < $groups[$fingerprint]['first_seen']) {
                    $groups[$fingerprint]['first_seen'] = $parsed['timestamp'];
                }

                if (!$groups[$fingerprint]['last_seen'] || $parsed['timestamp'] > $groups[$fingerprint]['last_seen']) {
                    $groups[$fingerprint]['last_seen'] = $parsed['timestamp'];
                }
            }
        }

        usort($groups, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $groups;
    }

    private function parseLine($line)
    {
        $timestamp = null;
        $message = trim((string) $line);
        $context = null;
        $exception = null;
        $stack = null;

        if (preg_match('/\[(.*?)\].*?:\s*(.*)$/', $line, $matches)) {
            $timestamp = $matches[1];
            $message = $matches[2];
        }

        if (preg_match('/\{.*\}$/', $message, $jsonMatch)) {
            $context = $jsonMatch[0];
        }

        if (preg_match('/([A-Za-z_\\\\]+Exception|Error):\s*(.*)$/', $message, $exceptionMatch)) {
            $exception = $exceptionMatch[1] . ': ' . $exceptionMatch[2];
        }

        if (strpos($message, '#0 ') !== false || strpos($message, 'Stack trace:') !== false) {
            $stack = $message;
        }

        return [
            'timestamp' => $timestamp,
            'message' => $message,
            'context' => $context,
            'exception' => $exception,
            'stack' => $stack,
        ];
    }

    private function fingerprint($message)
    {
        $value = (string) $message;
        $value = preg_replace('/\b[0-9a-f]{8}-[0-9a-f-]{27}\b/i', '<uuid>', $value);
        $value = preg_replace('/\b\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+\-]\d{2}:?\d{2})?\b/', '<timestamp>', $value);
        $value = preg_replace('/\b\d+\b/', '<n>', $value);
        return sha1($value);
    }
}
