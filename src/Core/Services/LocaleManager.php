<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class LocaleManager
{
    const DEFAULT_LOCALE = 'en';
    
    const SUPPORTED_LOCALES = [
        'en' => 'English',
        'pt-BR' => 'Português (Brasil)',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
    ];

    private $locale;
    private $fallbackLocale;

    public function __construct($locale = null)
    {
        $this->locale = $this->normalizeLocale($locale ?: self::DEFAULT_LOCALE);
        $this->fallbackLocale = self::DEFAULT_LOCALE;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $this->normalizeLocale($locale);
    }

    public function getFallbackLocale()
    {
        return $this->fallbackLocale;
    }

    public function isSupported($locale)
    {
        $normalized = $this->normalizeLocale($locale);
        return isset(self::SUPPORTED_LOCALES[$normalized]);
    }

    public function getSupportedLocales()
    {
        return self::SUPPORTED_LOCALES;
    }

    public function detectLocale()
    {
        $detectedLocale = $this->detectFromEnvironment();
        
        if ($detectedLocale && $this->isSupported($detectedLocale)) {
            return $detectedLocale;
        }

        return self::DEFAULT_LOCALE;
    }

    public function resolveTemplatePath($templatePath, $locale = null)
    {
        $locale = $locale ?: $this->locale;
        $baseDir = dirname($templatePath);
        $filename = basename($templatePath);
        
        $localizedPath = $baseDir . '/' . $locale . '/' . $filename;
        
        if (file_exists($localizedPath)) {
            return $localizedPath;
        }

        if ($locale !== $this->fallbackLocale) {
            $fallbackPath = $baseDir . '/' . $this->fallbackLocale . '/' . $filename;
            
            if (file_exists($fallbackPath)) {
                return $fallbackPath;
            }
        }

        if (file_exists($templatePath)) {
            return $templatePath;
        }

        return '';
    }

    private function normalizeLocale($locale)
    {
        $locale = str_replace('_', '-', $locale);
        $locale = strtolower($locale);
        
        $parts = explode('-', $locale);
        
        if (count($parts) === 2) {
            return $parts[0] . '-' . strtoupper($parts[1]);
        }
        
        if ($locale === 'pt') {
            return 'pt-BR';
        }
        
        return $locale;
    }

    private function detectFromEnvironment()
    {
        if (isset($_SERVER['LANG'])) {
            return $this->extractLocaleFromLang($_SERVER['LANG']);
        }

        if (isset($_SERVER['LC_ALL'])) {
            return $this->extractLocaleFromLang($_SERVER['LC_ALL']);
        }

        if (function_exists('locale_get_default')) {
            return locale_get_default();
        }

        return null;
    }

    private function extractLocaleFromLang($langValue)
    {
        if (empty($langValue)) {
            return null;
        }

        $parts = explode('.', $langValue);
        $locale = $parts[0];

        return $this->normalizeLocale($locale);
    }
}
