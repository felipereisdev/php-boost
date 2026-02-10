<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class EloquentModelMapService
{
    private $analysis;

    public function __construct()
    {
        $this->analysis = new StaticAnalysisService();
    }

    public function listModels($basePath, $namespaceRoot = null)
    {
        $files = $this->analysis->listPhpFiles([
            rtrim($basePath, '/') . '/app',
            rtrim($basePath, '/') . '/src',
        ]);

        $models = $this->analysis->extractModels($files);

        if ($namespaceRoot) {
            $models = array_values(array_filter($models, function ($model) use ($namespaceRoot) {
                return stripos($model['class'], $namespaceRoot) === 0;
            }));
        }

        return $models;
    }

    public function listRelations($basePath, $modelFilter = null, $namespaceRoot = null)
    {
        $models = $this->listModels($basePath, $namespaceRoot);
        if ($modelFilter) {
            $models = array_values(array_filter($models, function ($model) use ($modelFilter) {
                return stripos($model['class'], $modelFilter) !== false;
            }));
        }

        return $this->analysis->extractModelRelations($models);
    }
}
