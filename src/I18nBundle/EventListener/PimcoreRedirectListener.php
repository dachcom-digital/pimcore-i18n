<?php

namespace I18nBundle\EventListener;

use I18nBundle\Adapter\Redirector\CookieRedirector;
use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Helper\RequestValidatorHelper;
use Pimcore\Http\Request\Resolver\SiteResolver;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Adapter\Redirector\RedirectorInterface;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Registry\RedirectorRegistry;
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

class PimcoreRedirectListener implements EventSubscriberInterface
{
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
     * @var RedirectHandler
     */
    protected $redirectHandler;

    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @var RequestValidatorHelper
     */
    protected $requestValidatorHelper;

    /**
     * @param RedirectorRegistry     $redirectorRegistry
     * @param ZoneManager            $zoneManager
     * @param ContextManager         $contextManager
     * @param PathGeneratorManager   $pathGeneratorManager
     * @param RedirectHandler        $redirectHandler
     * @param SiteResolver           $siteResolver
     * @param DocumentHelper         $documentHelper
     * @param RequestValidatorHelper $requestValidatorHelper
     */
    public function __construct(
        RedirectorRegistry $redirectorRegistry,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        RedirectHandler $redirectHandler,
        SiteResolver $siteResolver,
        DocumentHelper $documentHelper,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->redirectorRegistry = $redirectorRegistry;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->redirectHandler = $redirectHandler;
        $this->siteResolver = $siteResolver;
        $this->documentHelper = $documentHelper;
        $this->requestValidatorHelper = $requestValidatorHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelRedirectException', 65]],     // before pimcore frontend routing listener (redirect check)
            KernelEvents::REQUEST   => [['onKernelRedirectRequest', 513]],      // before pimcore frontend routing listener (redirect check)
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
        if ($event->isMasterRequest() === false) {
            return;
        }

        if ($this->requestValidatorHelper->isValidForRedirect($event->getRequest(), false) === false) {
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
     *
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

        if (!$request->attributes->has('pimcore_request_source')) {
            $request->attributes->set('pimcore_request_source', 'document');
        }

        $this->zoneManager->initZones();
        $this->contextManager->initContext($this->zoneManager->getCurrentZoneInfo('mode'), $document);
        $this->pathGeneratorManager->initPathGenerator($request->attributes->get('pimcore_request_source'));

        $i18nType = $this->zoneManager->getCurrentZoneInfo('mode');
        $defaultLocale = $this->zoneManager->getCurrentZoneLocaleAdapter()->getDefaultLocale();
        $documentLocaleData = $this->documentHelper->getDocumentLocaleData($document, $i18nType);

        $options = [
            'i18nType'        => $i18nType,
            'request'         => $request,
            'document'        => $document,
            'documentLocale'  => $documentLocaleData['documentLocale'],
            'documentCountry' => $documentLocaleData['documentCountry'],
            'defaultLocale'   => $defaultLocale
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
            $response->setTargetUrl($document->getFullPath());

            return $response;
        }

        $localizedUrls = $this->pathGeneratorManager->getPathGenerator()->getUrls($document, false);

        if (count($localizedUrls) === 0) {
            $response->setTargetUrl($document->getFullPath());

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
     *
     * @return string
     */
    protected function resolveSite(Request $request)
    {
        $path = urldecode($request->getPathInfo());

        if ($this->requestValidatorHelper->isFrontendRequestByAdmin($request)) {
            return $path;
        }

        $host = $request->getHost();
        $site = Site::getByDomain($host);

        if (!$site instanceof Site) {
            return $path;
        }

        $path = $site->getRootPath() . $path;

        Site::setCurrentSite($site);

        $this->siteResolver->setSite($request, $site);
        $this->siteResolver->setSitePath($request, $path);

        return $path;
    }
}
