<?php

namespace FelipeReisDev\PhpBoost\Core\Support;

class ToolResult
{
    public static function success($tool, $summary, array $data = [], array $meta = [], array $findings = [], array $errors = [])
    {
        return self::build('ok', $tool, $summary, $data, $meta, $findings, $errors);
    }

    public static function warning($tool, $summary, array $data = [], array $meta = [], array $findings = [], array $errors = [])
    {
        return self::build('warning', $tool, $summary, $data, $meta, $findings, $errors);
    }

    public static function error($tool, $summary, array $data = [], array $meta = [], array $findings = [], array $errors = [])
    {
        return self::build('error', $tool, $summary, $data, $meta, $findings, $errors);
    }

    public static function finding($severity, $code, $message, array $evidence = [])
    {
        return [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'evidence' => $evidence,
        ];
    }

    private static function build($status, $tool, $summary, array $data, array $meta, array $findings, array $errors)
    {
        $baseMeta = [
            'version' => '1.0.0',
            'generated_at' => date('c'),
        ];

        $result = [
            'tool' => $tool,
            'status' => $status,
            'summary' => $summary,
            'meta' => array_merge($baseMeta, $meta),
            'data' => $data,
        ];

        if (!empty($findings)) {
            $result['findings'] = $findings;
        }

        if (!empty($errors)) {
            $result['errors'] = $errors;
        }

        return $result;
    }
}
