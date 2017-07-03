<?php

namespace I18nBundle\EventListener;

use I18nBundle\Tool\System;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Document\Helper\DocumentRelationHelper;

use I18nBundle\User\I18nGuesser;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

use Pimcore\Cache;
use Pimcore\Config;
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
    private $defaultLanguage = 'de';

    /**
     * @var array
     */
    private $validLanguages = [];

    /**
     * @var null
     */
    private $globalPrefix = NULL;

    /**
     * @var array
     */
    private $validCountries = [];

    /**
     * @var \Pimcore\Model\Document
     */
    private $document = NULL;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var DocumentRelationHelper
     */
    protected $documentRelationHelper;

    /**
     * @var I18nGuesser
     */
    protected  $guesser;

    /**
     * @var Request
     */
    protected $request;

    /**
     * DetectorListener constructor.
     *
     * @param Configuration    $configuration
     * @param DocumentResolver $documentResolver
     * @param DocumentRelationHelper $documentRelationHelper
     */
    public function __construct(Configuration $configuration, DocumentResolver $documentResolver, DocumentRelationHelper $documentRelationHelper, I18nGuesser $guesser)
    {
        $this->configuration = $configuration;
        $this->documentResolver = $documentResolver;
        $this->documentRelationHelper = $documentRelationHelper;
        $this->guesser = $guesser;

        $config = Config::getSystemConfig();
        $this->defaultLanguage = $config->general->defaultLanguage;
        $this->validLanguages = \Pimcore\Tool::getValidLanguages();

        $this->i18nType = $this->configuration->getConfig('mode');

        if ($this->i18nType === 'country') {
            $this->validCountries = $this->configuration->getCountryAdapter()->getActiveCountries();
        }

        /** @var @deprecated $globalPrefix */
        $globalPrefix = $this->configuration->getConfig('globalPrefix');
        if ($globalPrefix !== FALSE) {
            $this->globalPrefix = $globalPrefix;
        }
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
        if (! $this->document) {
            return;
        }

        if (!$this->isValidI18nCheckRequest(TRUE)) {
            return;
        }

        if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $documentCountry = $this->document->getHardLinkSource()->getProperty('country');
            $documentLanguage = $this->document->getHardLinkSource()->getProperty('language');
        } else {
            $documentCountry = $this->document->getProperty('country');
            $documentLanguage = $this->document->getProperty('language');
        }

        $isStaticRoute = $event->getRequest()->attributes->get('pimcore_request_source') === 'staticroute';

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

        $validCountry = !empty($documentCountry) && isset($this->validCountries[strtoupper($documentCountry)]);
        $validLanguage = !empty($documentLanguage) && in_array($documentLanguage, $this->validLanguages);

        $route = NULL;

        $currentRouteName = $this->request->get('_route');

        if ($currentRouteName !== NULL) {
            if ($this->i18nType === 'language') {
                //first get valid language
                if (!$validLanguage) {
                    if ($this->i18nType === 'language') {
                        $url = $this->getRedirectUrl($this->getLanguageUrl());
                        $event->setResponse(new RedirectResponse($url));
                        return;
                    }
                }
            } else if ($this->i18nType === 'country') {
                //we are wrong. redirect user!
                if ((!$validCountry || !$validLanguage)) {
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
     * Because this could be a different domain, absolute url is necessary (not implemented yet)
     * @return bool|string
     */
    private function getCountryUrl()
    {
        $userCountry = $this->guesser->guessCountry();
        $userLanguage = $this->guesser->guessLanguage();

        $rootPath = $this->documentRelationHelper->getCurrentPageRootPath();
        $countrySlug = $userCountry === FALSE ? '' : '-' . $userCountry;

        //check if path exists
        if (Document\Service::pathExists($rootPath . $userLanguage . $countrySlug . '/')
            && Document\Service::getByUrl($rootPath . $userLanguage . $countrySlug . '/')->isPublished()
        ) {
            $path = '/' . $userLanguage . $countrySlug;
        } //maybe country exists, but not the valid language?
        else if (Document\Service::pathExists($rootPath . $this->defaultLanguage . $countrySlug . '/')) {
            $path = '/' . $this->defaultLanguage . $countrySlug;
        } //nothing found. redirect to default /$this->globalPrefix-$this->defaultLanguage/
        else {
            $path = '/' . $this->defaultLanguage . (!is_null($this->globalPrefix) ? '-' . $this->globalPrefix : '');
        }

        return System::joinPath([\Pimcore\Tool::getHostUrl(), $path]);
    }

    /**
     * Returns absolute Url to website with language context.
     * Because this could be a different domain, absolute url is necessary
     * @return bool|string
     */
    private function getLanguageUrl()
    {
        $guessedLanguage =$this->guesser->guessLanguage();
        $languageIso = $this->defaultLanguage;

        $defaultLanguageUrl = '';
        $languageUrl = '';

        // 1. get all linked && translated tree-nodes
        // 2. search for guessed Language Code!
        if (\Pimcore\Model\Site::isSiteRequest()) {
            $rootConnectedDocuments = $this->documentRelationHelper->getRootConnectedDocuments();

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
            $rootPath = $this->documentRelationHelper->getCurrentPageRootPath();

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
        $config = Config::getSystemConfig();

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
            System::isInBackend($this->request) || System::isInCliMode() ||
            ($allowAjax === FALSE && $this->request->isXmlHttpRequest())
        ) {
            return FALSE;
        }

        return TRUE;
    }
}