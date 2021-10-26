<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Model\I18nZoneInterface;
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
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    protected ZoneManager $zoneManager;
    protected ZoneResolverInterface $zoneResolver;
    protected SiteResolver $siteResolver;
    protected Document\Service $documentService;
    protected Config $pimcoreConfig;

    public function __construct(
        ZoneManager $zoneManager,
        ZoneResolverInterface $zoneResolver,
        SiteResolver $siteResolver,
        Document\Service $documentService,
        Config $pimcoreConfig
    ) {
        $this->zoneManager = $zoneManager;
        $this->zoneResolver = $zoneResolver;
        $this->siteResolver = $siteResolver;
        $this->documentService = $documentService;
        $this->pimcoreConfig = $pimcoreConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10]
        ];
    }

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

    protected function handleErrorPage(ExceptionEvent $event): void
    {
        if (\Pimcore::inDebugMode()) {
            return;
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

        $request->attributes->set('pimcore_request_source', sprintf('document_%d', $document->getId()));

        $zone = $this->zoneManager->buildZoneByRequest($request, $document);

        if (!$zone instanceof I18nZoneInterface) {
            return;
        }

        $this->zoneResolver->setZone($zone, $request);

        $this->enablePimcoreContext();
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
                    $errorPath = $this->pimcoreConfig['documents']['error_pages']['localized'][$requestLocale];

                    break;
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
