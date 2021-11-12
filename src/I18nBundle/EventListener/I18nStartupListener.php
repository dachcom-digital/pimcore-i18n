<?php

namespace I18nBundle\EventListener;

use I18nBundle\Http\I18nContextResolverInterface;
use I18nBundle\Manager\I18nContextManager;
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
     * @throws \Throwable
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

        if ($document instanceof Document\Link) {
            return;
        }

        try {
            $this->initializeI18nContext($request, $document);
        } catch (\Throwable $e) {
            $this->handleContextException($e, $event);
            return;
        }

        if ($document->getProperty('language') === null) {
            $this->buildEditModeResponse($event);
        }
    }

    /**
     * @throws \Throwable
     */
    protected function initializeI18nContext(Request $request, ?Document $document): void
    {
        $documentLocale = $document?->getProperty('language');

        // we need to assert requests locale against the documents one
        // symfony will use the _route key from requests attributes
        // which can be wrong (e.g. en-us instead of en_US)
        $request->setLocale($documentLocale ?? '');

        $i18nContext = $this->i18nContextManager->buildContextByRequest($request, $document, true);

        $this->i18nContextResolver->setContext($i18nContext, $request);

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_CONTEXT, true);
    }

    /**
     * @throws \Throwable
     */
    protected function handleContextException(\Throwable $exception, RequestEvent $event): void
    {
        if (!$this->editmodeResolver->isEditmode()) {
            throw $exception;
        }

        $this->buildEditModeResponse($event, $exception->getMessage());
    }

    protected function buildEditModeResponse(RequestEvent $event, ?string $message = null): void
    {
        if (!$this->editmodeResolver->isEditmode()) {
            return;
        }

        $response = new Response();
        $language = 'en';
        if ($user = Admin::getCurrentUser()) {
            $language = $user->getLanguage();
        } elseif ($user = Authentication::authenticateSession($event->getRequest())) {
            $language = $user->getLanguage();
        }

        $response->setContent($this->templating->render(
            '@I18n/not_editable_aware_message.html.twig',
            [
                'adminLocale'      => $language,
                'exceptionMessage' => $message
            ])
        );

        $event->setResponse($response);
    }
}
