<?php

namespace I18nBundle\EventListener;

use I18nBundle\Http\RouteItemResolverInterface;
use I18nBundle\Manager\RouteItemManager;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\Site;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Authentication;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use I18nBundle\Definitions;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use I18nBundle\Helper\RequestValidatorHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimcore\Model\Document;
use Symfony\Component\Templating\EngineInterface;

class I18nStartupListener implements EventSubscriberInterface
{
    protected EngineInterface $templating;
    protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver;
    protected RouteItemManager $routeItemManager;
    protected EditmodeResolver $editmodeResolver;
    protected RouteItemResolverInterface $routeItemResolver;
    protected RequestValidatorHelper $requestValidatorHelper;

    public function __construct(
        EngineInterface $templating,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        RouteItemManager $routeItemManager,
        EditmodeResolver $editmodeResolver,
        RouteItemResolverInterface $routeItemResolver,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->templating = $templating;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->routeItemManager = $routeItemManager;
        $this->editmodeResolver = $editmodeResolver;
        $this->routeItemResolver = $routeItemResolver;
        $this->requestValidatorHelper = $requestValidatorHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 2],      // after pimcore context resolver
            ]
        ];
    }

    /**
     * Apply this method after the pimcore context resolver.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $request = $event->getRequest();

        $document = $this->pimcoreDocumentResolver->getDocument($request);
        if (!$document instanceof Document) {
            return;
        }

        if (!$this->requestValidatorHelper->isValidForRedirect($request)) {
            return;
        }

        $routeItem = $this->initializeRouteItem($request, $document);

        if (!$routeItem instanceof RouteItemInterface) {
            // @todo: log this?
            return;
        }

        // request may contains valid locale (default is "en") so we need to check against given document!
        if (empty($document->getProperty('language'))) {
            $this->setNotEditableAwareMessage($document, $event);
        }
    }

    protected function initializeRouteItem(Request $request, ?Document $document): ?RouteItemInterface
    {
        if ($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $document = $document->getSourceDocument();
        }

        $routeItem = $this->routeItemManager->buildRouteItemByRequest($request, $document);

        if (!$routeItem instanceof RouteItemInterface) {
            return null;
        }

        $this->routeItemResolver->setRouteItem($routeItem, $request);

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_CONTEXT, true);

        return $routeItem;
    }

    protected function setNotEditableAwareMessage(Document $document, RequestEvent $event): void
    {
        //if document is root, no language tag is required
        if ($this->editmodeResolver->isEditmode()) {
            $response = new Response();
            $language = 'en';
            if ($user = Admin::getCurrentUser()) {
                $language = $user->getLanguage();
            } elseif ($user = Authentication::authenticateSession($event->getRequest())) {
                $language = $user->getLanguage();
            }

            $response->setContent($this->templating->render('@I18n/not_editable_aware_message.html.twig', ['adminLocale' => $language]));
            $event->setResponse($response);

            return;
        }

        $siteId = 1;
        if (Site::isSiteRequest() === true) {
            $site = Site::getCurrentSite();
            $siteId = $site->getRootId();
        }

        //if document is root, no language tag is required
        if ($document->getId() !== $siteId) {
            throw new \Exception(sprintf('%s (%d) does not have a valid language property!', get_class($document), $document->getId()));
        }
    }
}
