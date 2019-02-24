<?php

namespace I18nBundle\Finder;

use I18nBundle\Definitions;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PathFinder
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var LocaleServiceInterface
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
     * @param RequestStack           $requestStack
     * @param LocaleServiceInterface $locale
     * @param ZoneManager            $zoneManager
     */
    public function __construct(
        RequestStack $requestStack,
        LocaleServiceInterface $locale,
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
     * /de-de/test.
     *
     * @todo implement zone manager to check valid languages/countries
     *
     * @param string $frontEndPath
     *
     * @return string|bool
     */
    public function checkPath($frontEndPath = null)
    {
        $request = $this->requestStack->getMasterRequest();
        $document = $request->get(DynamicRouter::CONTENT_KEY);

        if (!$document instanceof Document) {
            return false;
        }

        if ($document instanceof Document\Hardlink\Wrapper\Page) {
            $document = $document->getHardLinkSource();
        } elseif ($document instanceof Document\Hardlink\Wrapper\Link) {
            $document = $document->getHardLinkSource();
        }

        $currentLanguageIso = $document->getProperty('language');
        $currentCountryIso = null;

        if ($this->zoneManager->getCurrentZoneInfo('mode') === 'country' && !empty($currentLanguageIso)) {
            $currentCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        //extract locale fragments
        if (strpos($currentLanguageIso, '_') !== false) {
            $parts = explode('_', $currentLanguageIso);
            $currentLanguageIso = $parts[0];
            if (isset($parts[1]) && !empty($parts[1])) {
                $currentCountryIso = $parts[1];
            }
        }

        //only parse if country in i10n is active!
        if (is_null($currentCountryIso)) {
            return false;
        }

        if (!$this->locale->hasLocale()) {
            return false;
        }

        $urlPath = parse_url($frontEndPath, PHP_URL_PATH);
        $urlPathFragments = explode('/', ltrim($urlPath, '/'));

        //no path given.
        if (empty($urlPathFragments)) {
            return false;
        }

        $localePart = array_shift($urlPathFragments);

        //check if localePart is a valid country i18n part
        if ($this->hasDelimiterContext($localePart)) {
            //explode first path fragment, assuming that the first part is language/country slug
            $delimiter = strpos($localePart, '_') !== false ? '_' : '-';
            $pathElements = explode($delimiter, $localePart);

            //ensure correct country iso format
            if (count($pathElements) == 2) {
                $pathElements[1] = strtoupper($pathElements[1]);
            }

            $localePart = join('_', $pathElements);
            if (!$this->isValidLocale($localePart)) {
                return false;
            }

            //check if language is valid, otherwise there is no locale context.
        } elseif (!$this->isValidLocale($localePart)) {
            return false;
        }

        if ($currentCountryIso !== Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $formatting = $this->getContextFormatting($document, $request);
            $formattedCountryIso = !$formatting['uppercase'] ? strtolower($currentCountryIso) : $currentCountryIso;
            $this->localeFragment = [$currentLanguageIso . $formatting['delimiter'] . $formattedCountryIso];
        } else {
            $this->localeFragment = [$currentLanguageIso];
        }

        $newFrontEndPath = $this->buildLocaleUrl($urlPathFragments);
        if ($newFrontEndPath === $frontEndPath) {
            return false;
        }

        return $newFrontEndPath;
    }

    /**
     * @param array $url
     *
     * @return string
     */
    private function buildLocaleUrl($url = [])
    {
        return '/' . join('/', array_merge($this->localeFragment, $url));
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function hasDelimiterContext($path)
    {
        return strpos($path, '-') !== false || strpos($path, '_') !== false;
    }

    /**
     * @param string $fragment
     *
     * @return bool
     */
    private function isValidLocale($fragment)
    {
        return array_search($fragment, array_column($this->getValidLocales(), 'locale')) !== false;
    }

    /**
     * @return array
     */
    private function getValidLocales()
    {
        return $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();
    }

    /**
     * @param Document $document
     * @param Request  $request
     *
     * @return array
     */
    private function getContextFormatting(Document $document, Request $request)
    {
        $frontPageMappingAttribute = $request->attributes->get(Definitions::FRONT_PAGE_MAP);

        // use key of real language document instead of mapped front page map!
        if (is_array($frontPageMappingAttribute)) {
            $key = $frontPageMappingAttribute['key'];
        } else {
            $key = $document->getKey();
        }

        $delimiter = strpos($key, '_') !== false ? '_' : '-';
        $data = explode($delimiter, $key);
        $country = end($data);

        return [
            'delimiter' => $delimiter,
            'uppercase' => ctype_upper($country)
        ];
    }
}
