<?php

namespace I18nBundle\Adapter\PathGenerator;

use Pimcore\Model\Document as PimcoreDocument;

interface PathGeneratorInterface
{
    public function getUrls(PimcoreDocument $currentDocument, bool $onlyShowRootLanguages = false): array;
}
