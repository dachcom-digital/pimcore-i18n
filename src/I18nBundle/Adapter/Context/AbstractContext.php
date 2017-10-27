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
     * @return string
     */
    public function getCurrentLanguageIso()
    {
        if (Cache\Runtime::isRegistered('i18n.languageIso')) {
            $isoCode = Cache\Runtime::get('i18n.languageIso');
            return $isoCode;
        }

        return FALSE;
    }

    /**
     * Get valid Country Iso
     * @return bool|string
     */
    public function getCurrentCountryIso()
    {
        if (Cache\Runtime::isRegistered('i18n.countryIso')) {
            $isoCode = Cache\Runtime::get('i18n.countryIso');
            return $isoCode;
        }

        return FALSE;
    }

    /**
     * @param null $slot
     * @param null $locale
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentContextInfo($slot = NULL, $locale = NULL)
    {
        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);

        if(empty($locale)) {
            if($this->document instanceof Document) {
                $locale = $this->document->getProperty('language');
            }
        }

        if(empty($locale)) {
            throw new \Exception('I18n: locale for current request not found');
        }

        $treeIndex = array_search($locale, array_column($tree, 'locale'));
        if($treeIndex === FALSE) {
            throw new \Exception(sprintf('I18n: no valid zone for locale "%s" found.'));
        }

        return $tree[$treeIndex][$slot];
    }


    /**
     * @param $languageIso
     * @param $countryIso
     * @param $href
     *
     * @return array
     */
    public function mapLanguageInfo($languageIso, $countryIso = NULL, $href)
    {
        return [
            'iso'         => $languageIso,
            'titleNative' => Intl::getLanguageBundle()->getLanguageName($languageIso, $countryIso, $this->getCurrentLanguageIso()),
            'title'       => Intl::getLanguageBundle()->getLanguageName($languageIso, $countryIso, $languageIso),
            'href'        => $href
        ];
    }
}