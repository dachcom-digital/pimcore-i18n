<?php

namespace I18nBundle\Adapter\PathGenerator;

use Pimcore\Model\Document as PimcoreDocument;

interface PathGeneratorInterface
{
    /**
     * @param PimcoreDocument $currentDocument
     * @param bool            $onlyShowRootLanguages
     *
     * @return array
     */
    public function getUrls(PimcoreDocument $currentDocument, $onlyShowRootLanguages = false);
}
