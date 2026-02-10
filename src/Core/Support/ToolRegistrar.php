<?php

namespace FelipeReisDev\PhpBoost\Core\Support;

use FelipeReisDev\PhpBoost\Core\Tools\APIContractMap;
use FelipeReisDev\PhpBoost\Core\Tools\BoostAnalyze;
use FelipeReisDev\PhpBoost\Core\Tools\BoostDocs;
use FelipeReisDev\PhpBoost\Core\Tools\BoostHealth;
use FelipeReisDev\PhpBoost\Core\Tools\BoostMigrateGuide;
use FelipeReisDev\PhpBoost\Core\Tools\BoostProfile;
use FelipeReisDev\PhpBoost\Core\Tools\BoostSnippet;
use FelipeReisDev\PhpBoost\Core\Tools\BoostValidate;
use FelipeReisDev\PhpBoost\Core\Tools\DatabaseQuery;
use FelipeReisDev\PhpBoost\Core\Tools\DatabaseSchema;
use FelipeReisDev\PhpBoost\Core\Tools\DeadCodeHints;
use FelipeReisDev\PhpBoost\Core\Tools\ExplainQuery;
use FelipeReisDev\PhpBoost\Core\Tools\FeatureFlagsConfig;
use FelipeReisDev\PhpBoost\Core\Tools\FindNPlusOneRisk;
use FelipeReisDev\PhpBoost\Core\Tools\GetConfig;
use FelipeReisDev\PhpBoost\Core\Tools\ListModels;
use FelipeReisDev\PhpBoost\Core\Tools\LogErrorDigest;
use FelipeReisDev\PhpBoost\Core\Tools\ModelRelations;
use FelipeReisDev\PhpBoost\Core\Tools\PolicyAudit;
use FelipeReisDev\PhpBoost\Core\Tools\QueueHealth;
use FelipeReisDev\PhpBoost\Core\Tools\ReadLogEntries;
use FelipeReisDev\PhpBoost\Core\Tools\SafeMigrationPreview;
use FelipeReisDev\PhpBoost\Core\Tools\SchemaDiff;
use FelipeReisDev\PhpBoost\Core\Tools\TableDDL;
use FelipeReisDev\PhpBoost\Laravel\Tools\ApplicationInfo;
use FelipeReisDev\PhpBoost\Laravel\Tools\CacheManager;
use FelipeReisDev\PhpBoost\Laravel\Tools\ListArtisanCommands;
use FelipeReisDev\PhpBoost\Laravel\Tools\ListRoutes;
use FelipeReisDev\PhpBoost\Laravel\Tools\QueueStatus;
use FelipeReisDev\PhpBoost\Laravel\Tools\Tinker;

class ToolRegistrar
{
    public static function registerCoreTools($registry, array $config)
    {
        $registry->register(new GetConfig($config));
        $registry->register(new DatabaseSchema($config));
        $registry->register(new DatabaseQuery($config));
        $registry->register(new ReadLogEntries($config));

        $registry->register(new ExplainQuery($config));
        $registry->register(new TableDDL($config));
        $registry->register(new LogErrorDigest($config));
        $registry->register(new SchemaDiff($config));

        $registry->register(new ListModels($config));
        $registry->register(new ModelRelations($config));
        $registry->register(new APIContractMap($config));
        $registry->register(new PolicyAudit($config));
        $registry->register(new FeatureFlagsConfig($config));

        $registry->register(new FindNPlusOneRisk($config));
        $registry->register(new SafeMigrationPreview($config));
        $registry->register(new QueueHealth($config));
        $registry->register(new DeadCodeHints($config));

        $registry->register(new BoostValidate($config));
        $registry->register(new BoostMigrateGuide($config));
        $registry->register(new BoostHealth($config));
        $registry->register(new BoostSnippet($config));
        $registry->register(new BoostProfile($config));
        $registry->register(new BoostDocs($config));
        $registry->register(new BoostAnalyze($config));
    }

    public static function registerLaravelTools($registry, array $config)
    {
        $registry->register(new ListRoutes($config));
        $registry->register(new ApplicationInfo($config));
        $registry->register(new ListArtisanCommands($config));
        $registry->register(new QueueStatus($config));
        $registry->register(new CacheManager($config));
        $registry->register(new Tinker($config));
    }

    public static function registerLumenTools($registry, array $config)
    {
        $registry->register(new ListRoutes($config));
    }
}
