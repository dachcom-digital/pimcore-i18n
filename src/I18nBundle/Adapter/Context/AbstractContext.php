<?php

namespace I18nBundle\Adapter\Context;

use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\UserHelper;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Model\Document;
use Pimcore\Cache;
use Symfony\Component\Intl\Languages;

abstract class AbstractContext implements ContextInterface
{
    protected ZoneManager $zoneManager;
    protected PathGeneratorManager $pathGeneratorManager;
    protected DocumentHelper $documentHelper;
    protected UserHelper $userHelper;
    protected Document $document;

    public function __construct(
        ZoneManager $zoneManager,
        PathGeneratorManager $pathGeneratorManager,
        DocumentHelper $documentHelper,
        UserHelper $userHelper
    ) {
        $this->zoneManager = $zoneManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->documentHelper = $documentHelper;
        $this->userHelper = $userHelper;
    }

    public function setDocument(Document $document): void
    {
        $this->document = $document;
    }

    /**
     * @throws \Exception
     */
    public function getDocument(): Document
    {
        if (!$this->document instanceof Document) {
            throw new \Exception('AbstractContext has no valid document');
        }

        return $this->document;
    }

    public function getCurrentLocale(): ?string
    {
        if (!Cache\Runtime::isRegistered('i18n.locale')) {
            return null;
        }

        try {
            $locale = Cache\Runtime::get('i18n.locale');
        } catch (\Exception $e) {
            return null;
        }

        return $locale;
    }

    public function getCurrentLanguageIso(): ?string
    {
        if (!Cache\Runtime::isRegistered('i18n.languageIso')) {
            return null;
        }

        try {
            $isoCode = Cache\Runtime::get('i18n.languageIso');
        } catch (\Exception $e) {
            return null;
        }

        return $isoCode;
    }

    public function getCurrentCountryIso(): ?string
    {
        return null;
    }

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array
    {
        return $this->pathGeneratorManager->getPathGenerator()->getUrls($this->getDocument(), $onlyShowRootLanguages);
    }

    public function getLanguageNameByIsoCode(?string $languageIso, ?string $locale = null, ?string $region = null): ?string
    {
        if (empty($languageIso)) {
            return null;
        }

        $languageName = Languages::getName($languageIso, $locale);

        if (!empty($languageName)) {
            return $languageName;
        }

        return null;
    }

    public function getCurrentContextInfo(string $slot = null): mixed
    {
        $tree = $this->zoneManager->getCurrentZoneDomains(true);

        if ($this->document instanceof Document) {
            $locale = $this->document->getProperty('language');
        }

        if (empty($locale)) {
            throw new \Exception('I18n: locale for current request not found.');
        }

        $treeIndex = array_search($locale, array_column($tree, 'locale'), true);
        if ($treeIndex === false) {
            throw new \Exception(sprintf('I18n: no valid zone for locale "%s" found.', $locale));
        }

        return $tree[$treeIndex][$slot];
    }

    protected function mapLanguageInfo(string $locale, string $href): array
    {
        $iso = explode('_', $locale);

        return [
            'iso'         => $iso[0],
            'titleNative' => Languages::getName($locale, $iso[0]),
            'title'       => Languages::getName($locale, $this->getCurrentLanguageIso()),
            'href'        => $href
        ];
    }
}
