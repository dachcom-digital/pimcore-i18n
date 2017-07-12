<?php

namespace I18nBundle\EventListener;

use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\UserHelper;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Tool\System;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

use Pimcore\Cache;
use Pimcore\Logger;

use Pimcore\Model\Document;
use Pimcore\Service\Request\DocumentResolver;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;

class DetectorListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var string
     */
    private $i18nType = 'language';

    /**
     * @var string
     */
    private $defaultLanguage = NULL;

    /**
     * @var array
     */
    private $validLanguages = [];

    /**
     * @var array
     */
    private $validCountries = [];

    /**
     * @var null
     */
    private $globalPrefix = NULL;

    /**
     * @var \Pimcore\Model\Document
     */
    private $document = NULL;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * @var PathGeneratorManager
     */
    protected $pathGeneratorManager;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * DetectorListener constructor.
     *
     * @param DocumentResolver     $documentResolver
     * @param ZoneManager          $zoneManager
     * @param ContextManager       $contextManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param DocumentHelper       $documentHelper
     * @param UserHelper           $userHelper
     */
    public function __construct(
        DocumentResolver $documentResolver,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        DocumentHelper $documentHelper,
        UserHelper $userHelper
    ) {
        $this->documentResolver = $documentResolver;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->documentHelper = $documentHelper;
        $this->userHelper = $userHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest']
        ];
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->request = $event->getRequest();
        if (!$this->matchesPimcoreContext($this->request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $this->document = $this->documentResolver->getDocument($this->request);
        if (!$this->document) {
            return;
        }

        if (!$this->isValidI18nCheckRequest(TRUE)) {
            return;
        }

        //initialize all managers!
        $this->zoneManager->initZones();
        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'));
        $this->pathGeneratorManager->initPathGenerator($this->request->attributes->get('pimcore_request_source'));

        $this->i18nType = $this->zoneManager->getCurrentZoneInfo('mode');

        $this->validLanguages = $this->zoneManager->getCurrentZoneLanguageAdapter()->getValidLanguages();
        $this->defaultLanguage = $this->zoneManager->getCurrentZoneLanguageAdapter()->getDefaultLanguage();

        if ($this->i18nType === 'country') {
            $this->validCountries = $this->zoneManager->getCurrentZoneCountryAdapter()->getActiveCountries();
        }

        $globalPrefix = $this->zoneManager->getCurrentZoneInfo('global_prefix');
        if ($globalPrefix !== FALSE) {
            $this->globalPrefix = $globalPrefix;
        }

        if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $documentCountry = $this->document->getHardLinkSource()->getProperty('country');
            $documentLanguage = $this->document->getHardLinkSource()->getProperty('language');
        } else {
            $documentCountry = $this->document->getProperty('country');
            $documentLanguage = $this->document->getProperty('language');
        }

        //$isStaticRoute = $this->request->attributes->get('pimcore_request_source') === 'staticroute';

        if (empty($documentLanguage)) {

            $siteId = 1;
            if (\Pimcore\Model\Site::isSiteRequest() === TRUE) {
                $site = \Pimcore\Model\Site::getCurrentSite();
                $siteId = $site->getRootId();
            }

            //if document is root, no language tag is required
            if ($this->document->getId() !== $siteId) {
                throw new \Exception(get_class($this->document) . ' (' . $this->document->getId() . ') does not have a valid language property!');
            }
        }

        //if i18n has been disabled
        if ($this->document->getProperty('disable_i18n') === TRUE) {
            return;
        }

        $currentCountry = FALSE;
        $currentLanguage = FALSE;

        $validCountry = !empty($documentCountry) && array_search(strtoupper($documentCountry), array_column($this->validCountries, 'isoCode')) !== FALSE;
        $validLanguage = !empty($documentLanguage) && array_search($documentLanguage, array_column($this->validLanguages, 'isoCode')) !== FALSE;

        $route = NULL;

        $currentRouteName = $this->request->get('_route');

        if ($currentRouteName !== NULL) {
            if ($this->i18nType === 'language') {
                //first get valid language
                if (!$validLanguage) {
                    if ($this->canRedirect() && $this->i18nType === 'language') {
                        $url = $this->getRedirectUrl($this->getLanguageUrl());
                        $event->setResponse(new RedirectResponse($url));
                        return;
                    }
                }
            } else if ($this->i18nType === 'country') {
                //we are wrong. redirect user!
                if ($this->canRedirect() && (!$validCountry || !$validLanguage)) {
                    $url = $this->getRedirectUrl($this->getCountryUrl());
                    $event->setResponse(new RedirectResponse($url));

                    return;
                }

                $currentCountry = strtoupper($documentCountry);
            }

            $currentLanguage = $documentLanguage;
        }

        //Set Locale.
        Cache\Runtime::set('i18n.langIso', strtolower($currentLanguage));

        //Set Country. This variable is only !false if i18n country is active
        if ($currentCountry !== FALSE) {
            Cache\Runtime::set('i18n.countryIso', $currentCountry);
        }

        //check if language or country has been changed, trigger event for 3th party.
        $this->detectLanguageOrCountrySwitch($currentLanguage, $currentCountry);

        //update session
        $this->updateSessionData($currentLanguage, $currentCountry);
    }

    /**
     * @todo: language switch detection will not work in cross-domain environment
     *
     * @param $currentLanguage
     * @param $currentCountry
     *
     * @return bool
     */
    private function detectLanguageOrCountrySwitch($currentLanguage, $currentCountry)
    {
        $session = $this->getSessionData();

        $languageHasSwitched = FALSE;
        $countryHasSwitched = FALSE;

        if (is_null($session['lastLanguage']) || (!is_null($session['lastLanguage']) && $currentLanguage !== $session['lastLanguage'])) {
            $languageHasSwitched = TRUE;
        }

        if ($this->i18nType === 'country' && (is_null($session['lastCountry']) || (!is_null($session['lastCountry']) && $currentCountry !== $session['lastCountry']))) {
            $countryHasSwitched = TRUE;
        }

        if ($languageHasSwitched || $countryHasSwitched) {
            if ($languageHasSwitched === TRUE) {
                Logger::log('switch language! from ' . $session['lastLanguage'] . ' to ' . $currentLanguage . ' triggered by: ' . $_SERVER['REQUEST_URI']);
            }

            $params = [
                'languageHasSwitched' => $languageHasSwitched,
                'languageFrom'        => $session['lastLanguage'],
                'languageTo'          => $currentLanguage,
            ];

            if ($this->i18nType === 'country') {
                $params = array_merge(
                    $params,
                    [
                        'countryHasSwitched' => $countryHasSwitched,
                        'countryFrom'        => $session['lastCountry'],
                        'countryTo'          => $currentCountry,
                    ]
                );

                if ($countryHasSwitched === TRUE) {
                    Logger::log('switch country! from ' . $session['lastCountry'] . ' to ' . $currentCountry . ' triggered by: ' . $_SERVER['REQUEST_URI']);
                }
            }

            $event = new GenericEvent($this, [
                'params' => $params
            ]);

            //@todo: website.i18nSwitch old
            \Pimcore::getEventDispatcher()->dispatch(
                'i18n.switch', $event
            );
        }
    }

    /**
     * @todo: implement multi-country-domain
     * Returns absolute Url to website with language-country context.
     * Because this could be a different domain, absolute url is necessary
     * @return bool|string
     */
    private function getCountryUrl()
    {
        $globalPrefix = $this->zoneManager->getCurrentZoneInfo('global_prefix');

        $userLanguage = $this->userHelper->guessLanguage($this->validLanguages);
        $userCountry = $this->userHelper->guessCountry($this->validCountries);
        $userCountry = $userCountry === FALSE && $globalPrefix !== FALSE ? $globalPrefix : $userCountry;

        $countrySlug = $userCountry === FALSE ? '' : '-' . $userCountry;

        $rootPath = $this->documentHelper->getCurrentPageRootPath();

        if(\Pimcore\Model\Site::isSiteRequest()) {
            $hostUrl = \Pimcore\Tool::getRequestScheme() . '://' . \Pimcore\Model\Site::getCurrentSite()->getMainDomain();
        } else {
            $hostUrl = \Pimcore\Tool::getHostUrl();
        }

        //check if path exists
        if (Document\Service::pathExists($rootPath . $userLanguage . $countrySlug . '/')
            && Document\Service::getByUrl($rootPath . $userLanguage . $countrySlug . '/')->isPublished()
        ) {
            $path = '/' . $userLanguage . $countrySlug;
        }
        //maybe country exists, but not the valid language?
        else if (Document\Service::pathExists($rootPath . $this->defaultLanguage . $countrySlug . '/')) {
            $path = '/' . $this->defaultLanguage . $countrySlug;
        } //nothing found. redirect to default /$this->globalPrefix-$this->defaultLanguage/
        else {
            $path = '/' . $this->defaultLanguage . (!is_null($this->globalPrefix) ? '-' . $this->globalPrefix : '');
        }

        return System::joinPath([$hostUrl, $path]);
    }

    /**
     * Returns absolute Url to website with language context.
     * Because this could be a different domain, absolute url is necessary
     * @return bool|string
     */
    private function getLanguageUrl()
    {
        $guessedLanguage = $this->userHelper->guessLanguage($this->validLanguages);
        $languageIso = $this->defaultLanguage;

        $defaultLanguageUrl = '';
        $languageUrl = '';

        // 1. get all linked && translated tree-nodes
        // 2. search for guessed Language Code!
        if (\Pimcore\Model\Site::isSiteRequest()) {
            $rootConnectedDocuments = $this->documentHelper->getRootConnectedDocuments();

            foreach ($rootConnectedDocuments as $document) {
                if ($document['langIso'] === $languageIso) {
                    $defaultLanguageUrl = $document['homeUrl'];
                }

                if ($document['langIso'] === $guessedLanguage) {
                    $languageUrl = $document['homeUrl'];
                }
            }

            if (empty($languageUrl)) {
                $languageUrl = $defaultLanguageUrl;
            }
        } else {
            $rootPath = $this->documentHelper->getCurrentPageRootPath();

            //check if path exists
            $doc = Document\Service::getByUrl($rootPath . $guessedLanguage . '/');

            $languagePath = $languageIso;

            if (!is_null($doc) && $doc->isPublished()) {
                $languagePath = $guessedLanguage;
            }

            $languageUrl = System::joinPath([\Pimcore\Tool::getHostUrl(), $languagePath]);
        }

        return $languageUrl;
    }

    /**
     * @return array
     */
    private function getSessionData()
    {
        /** @var \Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag $bag */
        $bag = $this->request->getSession()->getBag('i18n_session');

        $data = ['lastLanguage' => NULL];

        if ($bag->has('lastLanguage')) {
            $data['lastLanguage'] = $bag->get('lastLanguage');
        }

        if ($this->i18nType == 'country') {
            $data['lastCountry'] = NULL;

            if ($bag->get('lastCountry')) {
                $data['lastCountry'] = $bag->get('lastCountry');
            }
        }

        return $data;
    }

    /**
     * @param bool $languageData
     * @param bool $countryData
     *
     * @return void
     */
    private function updateSessionData($languageData = FALSE, $countryData = FALSE)
    {
        /** @var \Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag $bag */
        $bag = $this->request->getSession()->getBag('i18n_session');

        if ($languageData !== FALSE) {
            $bag->set('lastLanguage', $languageData);
        }

        if ($this->i18nType == 'country') {
            if ($countryData !== FALSE) {
                $bag->set('lastCountry', $countryData);
            }
        }
    }

    /**
     * @param $path
     *
     * @return string
     */
    private function getRedirectUrl($path)
    {
        $config = \Pimcore\Config::getSystemConfig();

        $endPath = rtrim($path, '/');

        if ($config->documents->allowtrailingslash !== 'no') {
            $endPath = $endPath . '/';
        }

        return $endPath;
    }

    /**
     * @param bool $allowAjax
     *
     * @return bool
     */
    private function isValidI18nCheckRequest($allowAjax = FALSE)
    {
        if (
            System::isInCliMode() ||
            ($allowAjax === FALSE && $this->request->isXmlHttpRequest())
        ) {
            return FALSE;
        }

        return TRUE;
    }

    private function canRedirect()
    {
        return !System::isInBackend($this->request);
    }
}