<?php

namespace Tests\Unit;

use FelipeReisDev\PhpBoost\Core\Tools\APIContractMap;
use FelipeReisDev\PhpBoost\Core\Tools\DeadCodeHints;
use FelipeReisDev\PhpBoost\Core\Tools\ExplainQuery;
use FelipeReisDev\PhpBoost\Core\Tools\FeatureFlagsConfig;
use FelipeReisDev\PhpBoost\Core\Tools\FindNPlusOneRisk;
use FelipeReisDev\PhpBoost\Core\Tools\ListModels;
use FelipeReisDev\PhpBoost\Core\Tools\LogErrorDigest;
use FelipeReisDev\PhpBoost\Core\Tools\ModelRelations;
use FelipeReisDev\PhpBoost\Core\Tools\PolicyAudit;
use FelipeReisDev\PhpBoost\Core\Tools\QueueHealth;
use FelipeReisDev\PhpBoost\Core\Tools\SafeMigrationPreview;
use FelipeReisDev\PhpBoost\Core\Tools\SchemaDiff;
use FelipeReisDev\PhpBoost\Core\Tools\TableDDL;
use PHPUnit\Framework\TestCase;

class NewMcpToolsTest extends TestCase
{
    /**
     * @return array<int, object>
     */
    public function toolProvider()
    {
        return [
            [new ExplainQuery([])],
            [new TableDDL([])],
            [new LogErrorDigest([])],
            [new SchemaDiff([])],
            [new ListModels([])],
            [new ModelRelations([])],
            [new FindNPlusOneRisk([])],
            [new SafeMigrationPreview([])],
            [new QueueHealth([])],
            [new APIContractMap([])],
            [new FeatureFlagsConfig([])],
            [new PolicyAudit([])],
            [new DeadCodeHints([])],
        ];
    }

    /**
     * @dataProvider toolProvider
     */
    public function testToolContract($tool)
    {
        $this->assertNotEmpty($tool->getName());
        $this->assertNotEmpty($tool->getDescription());
        $schema = $tool->getInputSchema();
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertTrue($tool->isReadOnly());
    }
}
