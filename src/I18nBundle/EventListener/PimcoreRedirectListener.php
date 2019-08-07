<?php

namespace I18nBundle\EventListener;

use I18nBundle\Adapter\Redirector\CookieRedirector;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Adapter\Redirector\RedirectorInterface;
use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Registry\RedirectorRegistry;
use I18nBundle\Tool\System;
use Pimcore\Model\Site;
use Pimcore\Routing\RedirectHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;

class PimcoreRedirectListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    /**
     * @var string
     */
    private $i18nType = 'language';

    /**
     * @var string
     */
    private $defaultLocale = null;

    /**
     * @var array
     */
    private $validLocales = [];

    /**
     * @var Document
     */
    private $document = null;

    /**
     * @var null
     */
    private $documentLocale = null;

    /**
     * @var null
     */
    private $documentLanguage = null;

    /**
     * @var null
     */
    private $documentCountry = null;

    /**
     * @var RedirectorRegistry
     */
    protected $redirectorRegistry;

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
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var RedirectHandler
     */
    protected $redirectHandler;

    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param RedirectorRegistry   $redirectorRegistry
     * @param RequestHelper        $requestHelper
     * @param ZoneManager          $zoneManager
     * @param ContextManager       $contextManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param RedirectHandler      $redirectHandler
     * @param SiteResolver         $siteResolver
     */
    public function __construct(
        RedirectorRegistry $redirectorRegistry,
        RequestHelper $requestHelper,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        RedirectHandler $redirectHandler,
        SiteResolver $siteResolver
    ) {
        $this->redirectorRegistry = $redirectorRegistry;
        $this->requestHelper = $requestHelper;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->redirectHandler = $redirectHandler;
        $this->siteResolver = $siteResolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelRedirectException', 65]],     // before pimcore frontend routing listener (redirect check)
            KernelEvents::REQUEST   => [['onKernelRedirectRequest', 513]],        // before pimcore frontend routing listener (redirect check)
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRedirectException(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof NotFoundHttpException) {
            return;
        }

        $response = $this->checkI18nPimcoreRedirects($event->getRequest());
        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRedirectRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->matchesPimcoreContext($event->getRequest(), PimcoreContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        if (!$this->matchesPimcoreContext($event->getRequest(), PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $this->resolveSite($event->getRequest());

        $response = $this->checkI18nPimcoreRedirects($event->getRequest(), true);

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    /**
     * @param Request $request
     * @param bool    $override
     *
     * @return Response|null
     * @throws \Exception
     */
    protected function checkI18nPimcoreRedirects(Request $request, $override = false)
    {
        $response = $this->redirectHandler->checkForRedirect($request, $override);
        if (!$response instanceof RedirectResponse) {
            return null;
        }

        $oldTargetUrl = $response->getTargetUrl();
        $oldTargetUrlParts = parse_url($oldTargetUrl);

        preg_match('/\{i18n_localized_target_page=([0-9]+)\}/', $response->getTargetUrl(), $matches);

        if (!is_array($matches) || count($matches) !== 2) {
            return $response;
        }

        $validDecision = null;
        $documentId = $matches[1];
        $document = Document::getById($documentId);

        if (!$document instanceof Document) {
            return $response;
        }

        $this->document = $document;
        $this->request = $request;

        if (!$this->request->attributes->has('pimcore_request_source')) {
            $this->request->attributes->set('pimcore_request_source', 'document');
        }

        $this->zoneManager->initZones();

        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'), $document);
        $this->pathGeneratorManager->initPathGenerator($request->attributes->get('pimcore_request_source'));
        $this->i18nType = $this->zoneManager->getCurrentZoneInfo('mode');
        $this->validLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();
        $this->defaultLocale = $this->zoneManager->getCurrentZoneLocaleAdapter()->getDefaultLocale();

        $this->setDocumentLocale();

        $options = [
            'i18nType'        => $this->i18nType,
            'request'         => $this->request,
            'document'        => $this->document,
            'documentLocale'  => $this->documentLocale,
            'documentCountry' => $this->documentCountry,
            'defaultLocale'   => $this->defaultLocale
        ];

        $redirectorBag = new RedirectorBag($options);
        /** @var RedirectorInterface $redirector */
        foreach ($this->redirectorRegistry->all() as $redirector) {

            // do not use redirector with storage functionality
            if ($redirector instanceof CookieRedirector) {
                continue;
            }

            $redirector->makeDecision($redirectorBag);
            $decision = $redirector->getDecision();

            if ($decision['valid'] === true) {
                $validDecision = $decision;
            }

            $redirectorBag->addRedirectorDecisionToBag($redirector->getName(), $decision);
        }

        if ($validDecision === null) {
            return $response;
        }

        $localizedUrls = $this->pathGeneratorManager->getPathGenerator()->getUrls($document, false);

        if (count($localizedUrls) === 0) {
            return $response;
        }

        $newTargetUrl = null;
        foreach ($localizedUrls as $localizedUrl) {
            if ($localizedUrl['locale'] === $validDecision['locale']) {
                $newTargetUrl = $localizedUrl['url'];
            }
        }

        // no link found, use first one we've found.
        if ($newTargetUrl === null) {
            $response->setTargetUrl($localizedUrls[0]['url']);
            return $response;
        }

        if (isset($oldTargetUrlParts['query']) && !empty($oldTargetUrlParts['query'])) {
            $newTargetUrl .= strpos($newTargetUrl, '?') === false ? '?' : '&';
            $newTargetUrl .= $oldTargetUrlParts['query'];
        }

        $response->setTargetUrl($newTargetUrl);

        return $response;
    }

    /**
     * @param Request $request
     * @param bool    $allowAjax
     *
     * @return bool
     */
    protected function isValidI18nCheckRequest(Request $request, $allowAjax = false)
    {
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return false;
        }

        if (System::isInCliMode() || ($allowAjax === false && $request->isXmlHttpRequest())) {
            return false;
        }

        return true;
    }

    protected function setDocumentLocale()
    {
        if ($this->document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            /** @var Document\Hardlink\Wrapper $wrapperDocument */
            $wrapperDocument = $this->document;
            $this->documentLocale = $wrapperDocument->getHardLinkSource()->getProperty('language');
        } else {
            $this->documentLocale = $this->document->getProperty('language');
        }

        if ($this->i18nType === 'country') {
            $this->documentCountry = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;
        }

        $this->documentLanguage = $this->documentLocale;

        if (strpos($this->documentLocale, '_') !== false) {
            $parts = explode('_', $this->documentLocale);
            $this->documentLanguage = strtolower($parts[0]);
            if (isset($parts[1]) && !empty($parts[1])) {
                $this->documentCountry = strtoupper($parts[1]);
            }
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function resolveSite(Request $request)
    {
        $path = urldecode($request->getPathInfo());

        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return $path;
        }

        try {
            $site = Site::getByDomain($request->getHost());
            $path = $site->getRootPath() . $path;

            Site::setCurrentSite($site);

            $this->siteResolver->setSite($request, $site);
            $this->siteResolver->setSitePath($request, $path);
        } catch (\Exception $e) {
            // fail silently
        }

        return $path;
    }
}
