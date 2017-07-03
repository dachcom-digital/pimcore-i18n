<?php

namespace I18nBundle\PathResolver\Path;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Document\Helper\DocumentRelationHelper;
use Pimcore\Model\Document as PimcoreDocument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AbstractPath
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var DocumentRelationHelper
     */
    protected $documentRelationHelper;

    /**
     * AbstractPath constructor.
     *
     * @param UrlGeneratorInterface  $generator
     * @param Configuration          $configuration
     * @param DocumentRelationHelper $documentRelationHelper
     */
    public function __construct(
        UrlGeneratorInterface $generator,
        Configuration $configuration,
        DocumentRelationHelper $documentRelationHelper
    ) {
        $this->generator = $generator;
        $this->configuration = $configuration;
        $this->documentRelationHelper = $documentRelationHelper;
    }

    public function getUrls(PimcoreDocument $currentDocument, $validCountries = [])
    {
        return [];
    }
}