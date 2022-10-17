<?php

namespace I18nBundle\Resolver;

use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;

interface PimcoreDocumentResolverInterface
{
    public function getDocument(Request $request): ?Document;

    public function isFallbackDocument(Document $document): bool;
}
