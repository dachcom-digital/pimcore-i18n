<?php

namespace I18nBundle\Resolver;

use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

interface PimcoreDocumentResolverInterface
{
    /**
     * @param Request $request
     *
     * @return Document|null
     */
    public function getDocument(Request $request);
}
