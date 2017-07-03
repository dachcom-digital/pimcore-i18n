<?php

namespace I18nBundle\PathResolver;

use I18nBundle\Document\Helper\DocumentRelationHelper;
use I18nBundle\PathResolver\Path\DocumentPath;
use I18nBundle\PathResolver\Path\AbstractPath;
use I18nBundle\PathResolver\Path\StaticRoutePath;
use Symfony\Component\HttpFoundation\RequestStack;

class PathResolver
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var AbstractPath
     */
    protected $staticRoutePath;

    /**
     * @var DocumentRelationHelper
     */
    protected $documentPath;

    /**
     * PathResolver constructor.
     *
     * @param RequestStack    $requestStack
     * @param StaticRoutePath $staticRoutePath
     * @param DocumentPath    $documentPath
     */
    public function __construct(RequestStack $requestStack, StaticRoutePath $staticRoutePath, DocumentPath $documentPath)
    {
        $this->requestStack = $requestStack;
        $this->staticRoutePath = $staticRoutePath;
        $this->documentPath = $documentPath;
    }

    /**
     *
     * @return Path\AbstractPath
     * @throws \Exception
     */
    public function load()
    {
        $staticRoute = $this->requestStack->getMasterRequest()->attributes->get('pimcore_request_source');
        return $staticRoute === 'staticroute' ? $this->staticRoutePath : $this->documentPath;

    }
}