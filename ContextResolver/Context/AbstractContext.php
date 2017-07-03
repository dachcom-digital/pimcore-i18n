<?php

namespace I18nBundle\ContextResolver\Context;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Document\Helper\DocumentRelationHelper;
use I18nBundle\PathResolver\PathResolver;
use I18nBundle\User\I18nGuesser;

class AbstractContext
{
    /**
     * @var string
     */
    protected $context;

    /**
     * @var PathResolver
     */
    protected $pathResolver;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var DocumentRelationHelper
     */
    protected $relationHelper;

    /**
     * @var I18nGuesser
     */
    protected $guesser;

    /**
     * @var
     */
    protected $document;

    /**
     * @param $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param $context
     */
    public function setCurrentContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getCurrentContext()
    {
        return $this->context;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param DocumentRelationHelper $relationHelper
     */
    public function setRelationHelper(DocumentRelationHelper $relationHelper)
    {
        $this->relationHelper = $relationHelper;
    }

    public function setGuesser(I18nGuesser $guesser)
    {
        $this->guesser = $guesser;
    }

    /**
     * @param PathResolver $pathResolver
     */
    public function setPathResolver( $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function getPathResolver()
    {
        return $this->pathResolver->load();
    }
}