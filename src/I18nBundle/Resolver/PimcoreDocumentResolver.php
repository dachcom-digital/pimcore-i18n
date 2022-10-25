<?php

namespace I18nBundle\Resolver;

use I18nBundle\Helper\RequestValidatorHelper;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

class PimcoreDocumentResolver implements PimcoreDocumentResolverInterface
{
    protected SiteResolver $siteResolver;
    protected RequestValidatorHelper $requestHelper;
    protected DocumentResolver $documentResolver;
    protected Document\Service $documentService;
    protected ?Document $nearestDocument = null;
    protected array $nearestDocumentTypes;

    public function __construct(
        SiteResolver $siteResolver,
        RequestValidatorHelper $requestHelper,
        DocumentResolver $documentResolver,
        Document\Service $documentService
    ) {
        $this->siteResolver = $siteResolver;
        $this->requestHelper = $requestHelper;
        $this->documentResolver = $documentResolver;
        $this->documentService = $documentService;
        $this->nearestDocumentTypes = ['page', 'snippet', 'hardlink', 'link', 'folder'];
    }

    public function getDocument(Request $request): ?Document
    {
        $document = $this->documentResolver->getDocument($request);

        if ($document instanceof Document) {
            return $document;
        }

        if ($this->nearestDocument instanceof Document) {
            return $this->nearestDocument;
        }

        return $this->findFallback($request);
    }

    public function isFallbackDocument(Document $document): bool
    {
        if (!$this->nearestDocument instanceof Document) {
            return false;
        }

        return $this->nearestDocument->getId() === $document->getId();
    }

    protected function findFallback(Request $request): ?Document
    {
        if (!$this->requestHelper->matchesDefaultPimcoreContext($request)) {
            return null;
        }

        if ($this->siteResolver->isSiteRequest($request)) {
            $path = $this->siteResolver->getSitePath($request);
        } else {
            $path = urldecode($request->getPathInfo());
        }

        $this->nearestDocument = $this->documentService->getNearestDocumentByPath($path, false, $this->nearestDocumentTypes);

        return $this->nearestDocument;
    }
}
