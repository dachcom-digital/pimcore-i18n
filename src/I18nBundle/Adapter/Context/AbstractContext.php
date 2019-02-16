<?php

namespace I18nBundle\Adapter\Context;

use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\UserHelper;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Model\Document;
use Pimcore\Cache;
use Symfony\Component\Intl\Intl;

abstract class AbstractContext implements ContextInterface
{
    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var PathGeneratorManager
     */
    protected $pathGeneratorManager;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var Document
     */
    protected $document;

    /**
     * DetectorListener constructor.
     *
     * @param ZoneManager          $zoneManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param DocumentHelper       $documentHelper
     * @param UserHelper           $userHelper
     */
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

    /**
     * @param Document $document
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @return Document
     *
     * @throws \Exception
     */
    public function getDocument()
    {
        if (!$this->document instanceof Document) {
            throw new \Exception('AbstractContext has no valid document');
        }

        return $this->document;
    }

    /**
     * Helper: Get current Locale.
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        if (Cache\Runtime::isRegistered('i18n.locale')) {
            $locale = Cache\Runtime::get('i18n.locale');

            return $locale;
        }

        return false;
    }

    /**
     * Helper: Get current Language Iso.
     *
     * @return string
     */
    public function getCurrentLanguageIso()
    {
        if (Cache\Runtime::isRegistered('i18n.languageIso')) {
            $isoCode = Cache\Runtime::get('i18n.languageIso');

            return $isoCode;
        }

        return false;
    }

    /**
     * Helper: Get current Country Iso.
     *
     * Get valid Country Iso
     *
     * @return bool|string
     */
    public function getCurrentCountryIso()
    {
        return false;
    }

    /**
     * Helper: Get all linked pages from current document.
     *
     * @param bool $onlyShowRootLanguages
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getLinkedLanguages($onlyShowRootLanguages = false)
    {
        $currentDocument = $this->getDocument();
        $urls = $this->pathGeneratorManager->getPathGenerator()->getUrls($currentDocument, $onlyShowRootLanguages);

        return $urls;
    }

    /**
     * Helper: Get Language Name By Iso Code.
     *
     * @param string $languageIso
     * @param string $locale
     * @param string $region      ignored in abstract context. only available in country context.
     *
     * @return string|null
     */
    public function getLanguageNameByIsoCode($languageIso, $locale = null, $region = null)
    {
        if ($languageIso === false) {
            return null;
        }

        $languageName = Intl::getLanguageBundle()->getLanguageName($languageIso, null, $locale);

        if (!empty($languageName)) {
            return $languageName;
        }

        return null;
    }

    /**
     * Helper: Get Information about current Context.
     *
     * @param null $slot
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getCurrentContextInfo($slot = null)
    {
        $tree = $this->zoneManager->getCurrentZoneDomains(true);

        if ($this->document instanceof Document) {
            $locale = $this->document->getProperty('language');
        }

        if (empty($locale)) {
            throw new \Exception('I18n: locale for current request not found.');
        }

        $treeIndex = array_search($locale, array_column($tree, 'locale'));
        if ($treeIndex === false) {
            throw new \Exception(sprintf('I18n: no valid zone for locale "%s" found.', $locale));
        }

        return $tree[$treeIndex][$slot];
    }

    /**
     * @param string $languageIso
     * @param string $countryIso
     * @param string $href
     *
     * @return array
     */
    protected function mapLanguageInfo($languageIso, $countryIso, $href)
    {
        return [
            'iso'         => $languageIso,
            'titleNative' => Intl::getLanguageBundle()->getLanguageName($languageIso, $countryIso, $languageIso),
            'title'       => Intl::getLanguageBundle()->getLanguageName($languageIso, $countryIso, $this->getCurrentLanguageIso()),
            'href'        => $href
        ];
    }
}
