<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class ApiContractService
{
    private $analysis;

    public function __construct()
    {
        $this->analysis = new StaticAnalysisService();
    }

    public function map($basePath, $routePrefix = null)
    {
        return $this->analysis->routeContracts($basePath, $routePrefix);
    }
}
