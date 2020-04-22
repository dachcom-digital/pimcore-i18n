<?php

namespace I18nBundle\Resolver;

use I18nBundle\Helper\RequestValidatorHelper;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

class PimcoreDocumentResolver implements PimcoreDocumentResolverInterface
{
    /**
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var RequestValidatorHelper
     */
    protected $requestHelper;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var Document\Service
     */
    protected $documentService;

    /**
     * @var array
     */
    protected $nearestDocumentTypes;

    /**
     * @var Document
     */
    protected $nearestDocument;

    /**
     * @param SiteResolver           $siteResolver
     * @param RequestValidatorHelper $requestHelper
     * @param DocumentResolver       $documentResolver
     * @param Document\Service       $documentService
     */
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

    /**
     * {@inheritDoc}
     */
    public function getDocument(Request $request)
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

    /**
     * @param Request $request
     *
     * @return Document|null
     */
    protected function findFallback(Request $request)
    {
        if (!$this->requestHelper->matchesDefaultPimcoreContext($request)) {
            return null;
        }

        $path = null;
        if ($this->siteResolver->isSiteRequest($request)) {
            $path = $this->siteResolver->getSitePath($request);
        } else {
            $path = urldecode($request->getPathInfo());
        }

        $this->nearestDocument = $this->documentService->getNearestDocumentByPath($path, false, $this->nearestDocumentTypes);

        return $this->nearestDocument;
    }
}
