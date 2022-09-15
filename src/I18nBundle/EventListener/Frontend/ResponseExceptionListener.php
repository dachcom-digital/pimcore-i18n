<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Exception\RouteItemException;
use I18nBundle\Exception\ZoneSiteNotFoundException;
use I18nBundle\Http\I18nContextResolverInterface;
use Pimcore\Document\Renderer\DocumentRenderer;
use I18nBundle\Manager\I18nContextManager;
use Pimcore\Bundle\CoreBundle\EventListener\PimcoreContextListener;
use Pimcore\Config;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\DataObject;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    protected I18nContextManager $i18nContextManager;
    protected I18nContextResolverInterface $i18nContextResolver;
    protected SiteResolver $siteResolver;
    protected Document\Service $documentService;
    protected Config $pimcoreConfig;
    protected DocumentRenderer $documentRenderer;

    public function __construct(
        I18nContextManager $i18nContextManager,
        I18nContextResolverInterface $i18nContextResolver,
        SiteResolver $siteResolver,
        Document\Service $documentService,
        Config $pimcoreConfig,
        DocumentRenderer $documentRenderer
    ) {
        $this->i18nContextManager = $i18nContextManager;
        $this->i18nContextResolver = $i18nContextResolver;
        $this->siteResolver = $siteResolver;
        $this->documentService = $documentService;
        $this->pimcoreConfig = $pimcoreConfig;
        $this->documentRenderer = $documentRenderer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10]
        ];
    }

    /**
     * @throws RouteItemException
     * @throws ZoneSiteNotFoundException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $renderErrorPage = $this->pimcoreConfig['error_handling']['render_error_document'];

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());

            // a response was explicitly set -> do not continue to error page
            return;
        }

        if ($renderErrorPage === false) {
            return;
        }

        if (!$this->matchesPimcoreContext($event->getRequest(), PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $this->handleErrorPage($event);
    }

    /**
     * @throws RouteItemException
     * @throws ZoneSiteNotFoundException
     */
    protected function handleErrorPage(ExceptionEvent $event): void
    {
        if (\Pimcore::inDebugMode()) {
            return;
        }

        $statusCode = 500;
        $headers = [];

        $exception = $event->getThrowable();

        if ($event->getThrowable() instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        }

        $request = $event->getRequest();

        /**
         * This is basically the same as in pimcore's ResponseExceptionListener
         * We need a document to initialize a valid zone!
         */
        $document = $this->determineErrorDocument($event->getRequest());

        if (!$document instanceof Document) {
            return;
        }

        $documentLocale = $document->getProperty('language');

        if (!empty($documentLocale)) {
            $request->setLocale($documentLocale);
        }

        if (!$request->attributes->has('_route')) {
            $request->attributes->set('_route', sprintf('document_%d', $document->getId()));
        }

        $i18nContext = $this->i18nContextManager->buildContextByRequest($request, $document, true);

        if (!$i18nContext instanceof I18nContextInterface) {
            return;
        }

        $this->i18nContextResolver->setContext($i18nContext, $request);

        $this->enablePimcoreContext();

        try {
            $response = $this->documentRenderer->render($document, [
                'exception' => $exception,
                PimcoreContextListener::ATTRIBUTE_PIMCORE_CONTEXT_FORCE_RESOLVING => true,
            ]);
        } catch (\Exception $e) {
            // we are even not able to render the error page, so we send the client a unicorn
            $response = 'Page not found. 🦄';
            $this->logger->emergency('Unable to render error page, exception thrown');
            $this->logger->emergency($e);
        }

        $event->setResponse(new Response($response, $statusCode, $headers));
    }

    private function determineErrorDocument(Request $request): ?Document
    {
        $errorPath = null;

        if ($this->siteResolver->isSiteRequest($request)) {
            $site = $this->siteResolver->getSite($request);
            $path = $this->siteResolver->getSitePath($request);
            $localizedErrorDocumentsPaths = $site?->getLocalizedErrorDocuments() ?: [];
            $defaultErrorDocumentPath = $site?->getErrorDocument();
        } else {
            $path = urldecode($request->getPathInfo());
            $localizedErrorDocumentsPaths = $this->pimcoreConfig['documents']['error_pages']['localized'] ?: [];
            $defaultErrorDocumentPath = $this->pimcoreConfig['documents']['error_pages']['default'];
        }

        // Find the nearest document by path
        $document = $this->documentService->getNearestDocumentByPath(
            $path,
            false,
            ['page', 'snippet', 'hardlink', 'link', 'folder']
        );

        if ($document && $document->getFullPath() !== '/' && $document->getProperty('language')) {
            $locale = $document->getProperty('language');
        }

        if (!empty($locale) && array_key_exists($locale, $localizedErrorDocumentsPaths)) {
            $errorPath = $localizedErrorDocumentsPaths[$locale];
        } else {
            // If locale can't be determined check if error page is defined for any of user-agent preferences
            foreach ($request->getLanguages() as $requestLocale) {
                if (!empty($localizedErrorDocumentsPaths[$requestLocale])) {
                    $errorPath = $localizedErrorDocumentsPaths[$requestLocale];

                    break;
                }
            }

            if (empty($errorPath)) {
                foreach ($request->getLanguages() as $requestLocale) {
                    if (!empty($this->pimcoreConfig['documents']['error_pages']['localized'][$requestLocale])) {
                        $errorPath = $this->pimcoreConfig['documents']['error_pages']['localized'][$requestLocale];

                        break;
                    }
                }
            }
        }

        if (empty($errorPath)) {
            $errorPath = $defaultErrorDocumentPath;
        }

        if (!empty($errorPath)) {
            return Document::getByPath($errorPath);
        }

        return null;
    }

    private function enablePimcoreContext(): void
    {
        // Pimcore does not initialize context in exception context

        Document::setHideUnpublished(true);
        DataObject\AbstractObject::setHideUnpublished(true);
        DataObject\AbstractObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);
    }
}
