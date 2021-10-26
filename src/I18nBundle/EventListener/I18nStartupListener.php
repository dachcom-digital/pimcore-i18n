<?php

namespace I18nBundle\EventListener;

use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Model\I18nZoneInterface;
use Pimcore\Model\Site;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Authentication;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use I18nBundle\Definitions;
use I18nBundle\Manager\ZoneManager;
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
    protected ZoneManager $zoneManager;
    protected EditmodeResolver $editmodeResolver;
    protected ZoneResolverInterface $zoneResolver;
    protected RequestValidatorHelper $requestValidatorHelper;

    public function __construct(
        EngineInterface $templating,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        ZoneManager $zoneManager,
        EditmodeResolver $editmodeResolver,
        ZoneResolverInterface $zoneResolver,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->templating = $templating;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->zoneManager = $zoneManager;
        $this->editmodeResolver = $editmodeResolver;
        $this->zoneResolver = $zoneResolver;
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

        $zone = $this->initializeZone($request, $document);

        if (!$zone instanceof I18nZoneInterface) {
            // @todo: log this?
            return;
        }

        // request may contains valid locale (default is "en") so we need to check against given document!
        if (empty($document->getProperty('language'))) {
            $this->setNotEditableAwareMessage($document, $event);
        }
    }

    protected function initializeZone(Request $request, ?Document $document): ?I18nZoneInterface
    {
        if ($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $document = $document->getSourceDocument();
        }

        $zone = $this->zoneManager->buildZoneByRequest($request, $document);

        if (!$zone instanceof I18nZoneInterface) {
            return null;
        }

        $this->zoneResolver->setZone($zone, $request);

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_CONTEXT, true);

        return $zone;
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
            $siteId = $site?->getRootId();
        }

        //if document is root, no language tag is required
        if ($document->getId() !== $siteId) {
            throw new \Exception(sprintf('%s (%d) does not have a valid language property!', get_class($document), $document->getId()));
        }
    }
}
