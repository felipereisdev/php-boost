<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\LocaleManager;

class LocaleManagerTest extends TestCase
{
    public function testDefaultLocaleIsEnglish()
    {
        $manager = new LocaleManager();
        $this->assertEquals('en', $manager->getLocale());
    }

    public function testSetLocale()
    {
        $manager = new LocaleManager();
        $manager->setLocale('pt-BR');
        $this->assertEquals('pt-BR', $manager->getLocale());
    }

    public function testNormalizeLocale()
    {
        $manager = new LocaleManager('pt_br');
        $this->assertEquals('pt-BR', $manager->getLocale());
    }

    public function testNormalizeSimplePortuguese()
    {
        $manager = new LocaleManager('pt');
        $this->assertEquals('pt-BR', $manager->getLocale());
    }

    public function testIsSupportedLocale()
    {
        $manager = new LocaleManager();
        
        $this->assertTrue($manager->isSupported('en'));
        $this->assertTrue($manager->isSupported('pt-BR'));
        $this->assertTrue($manager->isSupported('es'));
        $this->assertTrue($manager->isSupported('fr'));
        $this->assertTrue($manager->isSupported('de'));
        $this->assertFalse($manager->isSupported('invalid'));
    }

    public function testGetSupportedLocales()
    {
        $manager = new LocaleManager();
        $locales = $manager->getSupportedLocales();
        
        $this->assertIsArray($locales);
        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('pt-BR', $locales);
        $this->assertEquals('English', $locales['en']);
    }

    public function testResolveTemplatePathWithLocale()
    {
        $manager = new LocaleManager('en');
        $basePath = __DIR__ . '/../../../src/Core/Templates/Php';
        $templatePath = $basePath . '/php74.php';
        
        $resolved = $manager->resolveTemplatePath($templatePath);
        
        $expected = $basePath . '/en/php74.php';
        $this->assertEquals($expected, $resolved);
    }

    public function testResolveTemplatePathFallbackToDefault()
    {
        $manager = new LocaleManager('fr');
        $basePath = __DIR__ . '/../../fixtures/templates';
        $templatePath = $basePath . '/nonexistent.php';
        
        $resolved = $manager->resolveTemplatePath($templatePath);
        
        $this->assertEmpty($resolved);
    }

    public function testDetectLocaleReturnsDefault()
    {
        $manager = new LocaleManager();
        $detected = $manager->detectLocale();
        
        $this->assertContains($detected, ['en', 'pt-BR', 'es', 'fr', 'de']);
    }
}
