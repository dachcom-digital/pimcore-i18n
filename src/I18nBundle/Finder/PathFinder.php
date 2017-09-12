<?php

namespace I18nBundle\Finder;

use I18nBundle\Definitions;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Localization\Locale;
use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\RequestStack;

class PathFinder
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var Locale
     */
    protected $locale;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var array
     */
    protected $localeFragment = [];

    /**
     * PathFinder constructor.
     *
     * @param RequestStack $requestStack
     * @param Locale       $locale
     * @param ZoneManager  $zoneManager
     */
    public function __construct(
        RequestStack $requestStack,
        Locale $locale,
        ZoneManager $zoneManager
    ) {
        $this->requestStack = $requestStack;
        $this->zoneManager = $zoneManager;
        $this->locale = $locale;
    }

    /**
     * Valid Paths:
     * /de/test
     * /global-de/test
     * /de-de/test
     *
     * @todo implement zone manager to check valid languages/countries
     * @param string $frontEndPath
     *
     * @return string|bool
     */
    public function checkPath($frontEndPath = NULL)
    {
        $document = $this->requestStack->getMasterRequest()->get(DynamicRouter::CONTENT_KEY);

        if (!$document instanceof Document) {
            return FALSE;
        }

        if ($document instanceof Document\Hardlink\Wrapper\Page) {
            $document = $document->getHardLinkSource();
        } else if ($document instanceof Document\Hardlink\Wrapper\Link) {
            $document = $document->getHardLinkSource();
        }

        $currentCountryIso = $document->getProperty('country');
        $currentLanguageIso = $document->getProperty('language');

        if(strpos($currentLanguageIso, '_') !== FALSE) {
            $parts = explode('_', $currentLanguageIso);
            $currentLanguageIso = $parts[0];
        }

        //only parse if country in i10n is active!
        if (is_null($currentCountryIso)) {
            return FALSE;
        }

        if (!$this->locale->hasLocale()) {
            return FALSE;
        }

        $urlPath = parse_url($frontEndPath, PHP_URL_PATH);
        $urlPathFragments = explode('/', ltrim($urlPath, '/'));

        //no path given.
        if (empty($urlPathFragments)) {
            return FALSE;
        }

        $localePart = array_shift($urlPathFragments);

        //check if localePart is a valid i18n part
        if ($this->hasI18nContext($localePart)) {

            //explode first path fragment, assuming that the first part is language/country slug
            $pathElements = explode('-', $localePart);

            //invalid i18n format
            if (count($pathElements) !== 2) {
                return FALSE;
            } else if (!$this->isValidLanguage($pathElements[0])) {
                return FALSE;
            } else if (!$this->isValidCountry($pathElements[1])) {
                return FALSE;
            }

            //check if language is valid, otherwise there is no locale context.
        } else if (!$this->isValidLanguage($localePart)) {
            return FALSE;
        }

        if ($currentCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $this->localeFragment = [$currentLanguageIso . '-' . $currentCountryIso];
        } else {
            $this->localeFragment = [$currentLanguageIso];
        }

        $newFrontEndPath = $this->buildLocaleUrl($urlPathFragments);

        //same same. return false.
        if ($newFrontEndPath === $frontEndPath) {
            return FALSE;
        }

        //\Pimcore\Logger::log('i18n path: ' . $frontEndPath . ' => ' . $newFrontEndPath);

        return $newFrontEndPath;
    }

    private function buildLocaleUrl($url = [])
    {
        return strtolower('/' . join('/', array_merge($this->localeFragment, $url)));
    }

    /**
     * @param $path
     *
     * @return bool
     */
    private function hasI18nContext($path)
    {
        return strpos($path, '-') !== FALSE;
    }

    /**
     * @param $fragment
     *
     * @return bool
     */
    private function isValidLanguage($fragment)
    {
        return array_search($fragment, array_column($this->getValidLanguages(), 'isoCode')) !== FALSE;
    }

    /**
     * @param $fragment
     *
     * @return bool
     */
    private function isValidCountry($fragment)
    {
        return array_search(strtoupper($fragment), array_column($this->getValidCountries(), 'isoCode')) !== FALSE;
    }

    private function getValidLanguages()
    {
        return $this->zoneManager->getCurrentZoneLanguageAdapter()->getActiveLanguages();
    }

    private function getValidCountries()
    {
        return $this->zoneManager->getCurrentZoneCountryAdapter()->getActiveCountries();
    }
}