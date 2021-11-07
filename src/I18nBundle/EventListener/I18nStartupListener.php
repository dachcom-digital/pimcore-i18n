<?php

namespace I18nBundle\EventListener;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Http\I18nContextResolverInterface;
use I18nBundle\Manager\I18nContextManager;
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
    protected I18nContextManager $i18nContextManager;
    protected EditmodeResolver $editmodeResolver;
    protected I18nContextResolverInterface $i18nContextResolver;
    protected RequestValidatorHelper $requestValidatorHelper;

    public function __construct(
        EngineInterface $templating,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver,
        I18nContextManager $i18nContextManager,
        EditmodeResolver $editmodeResolver,
        I18nContextResolverInterface $i18nContextResolver,
        RequestValidatorHelper $requestValidatorHelper
    ) {
        $this->templating = $templating;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->i18nContextManager = $i18nContextManager;
        $this->editmodeResolver = $editmodeResolver;
        $this->i18nContextResolver = $i18nContextResolver;
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

        $i18nContext = $this->initializeI18nContext($request, $document);

        if (!$i18nContext instanceof I18nContextInterface) {
            // @todo: log this?
            return;
        }

        // request may contains valid locale (default is "en") so we need to check against given document!
        if (empty($document->getProperty('language'))) {
            $this->setNotEditableAwareMessage($document, $event);
        }
    }

    protected function initializeI18nContext(Request $request, ?Document $document): ?I18nContextInterface
    {
        if ($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $document = $document->getSourceDocument();
        }

        $i18nContext = $this->i18nContextManager->buildContextByRequest($request, $document, true);

        if (!$i18nContext instanceof I18nContextInterface) {
            return null;
        }

        $this->i18nContextResolver->setContext($i18nContext, $request);

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_CONTEXT, true);

        return $i18nContext;
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
