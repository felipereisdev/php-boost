<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RemovedCliCommandsRegressionTest extends TestCase
{
    public function testLegacyCommandFilesWereRemoved()
    {
        $base = getcwd() . '/src/Laravel/Console/';

        $this->assertFileDoesNotExist($base . 'ValidateCommand.php');
        $this->assertFileDoesNotExist($base . 'MigrateGuideCommand.php');
        $this->assertFileDoesNotExist($base . 'HealthCommand.php');
        $this->assertFileDoesNotExist($base . 'SnippetCommand.php');
        $this->assertFileDoesNotExist($base . 'ProfileCommand.php');
        $this->assertFileDoesNotExist($base . 'DocsCommand.php');
        $this->assertFileDoesNotExist($base . 'AnalyzeCommand.php');
    }

    public function testLaravelProviderNoLongerRegistersRemovedCommands()
    {
        $provider = file_get_contents(getcwd() . '/src/Laravel/BoostServiceProvider.php');
        $this->assertIsString($provider);

        $this->assertStringNotContainsString('ValidateCommand::class', $provider);
        $this->assertStringNotContainsString('MigrateGuideCommand::class', $provider);
        $this->assertStringNotContainsString('HealthCommand::class', $provider);
        $this->assertStringNotContainsString('SnippetCommand::class', $provider);
        $this->assertStringNotContainsString('ProfileCommand::class', $provider);
        $this->assertStringNotContainsString('DocsCommand::class', $provider);
        $this->assertStringNotContainsString('AnalyzeCommand::class', $provider);
    }
}
