<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Context\AbstractContext;
use I18nBundle\Adapter\Context\ContextInterface;
use I18nBundle\Adapter\Context\Country;
use I18nBundle\Adapter\Context\Language;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Registry\ContextRegistry;

class ContextManager
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * Stores the current Context info
     * @var AbstractContext
     */
    protected $currentContext;

    /**
     * {@inheritdoc}
     */
    public function __construct(Configuration $configuration, ContextRegistry $contextRegistry)
    {
        $this->configuration = $configuration;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * @param $contextIdentifier
     * @throws \Exception
     * @return void
     */
    public function initContext($contextIdentifier)
    {
        $contextId = $contextIdentifier;

        if (!empty($this->currentContext)) {
            //throw new \Exception('context already defined');
            return;
        }

        if(!$this->contextRegistry->has($contextId)) {
            throw new \Exception(sprintf('context adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.', $contextId, 'i18n.adapter.context', $contextId));
        }

        $this->currentContext = $this->contextRegistry->get($contextId);
    }

    public function setDocumentToCurrentContext($document)
    {
        $this->currentContext->setDocument($document);
    }

    /**
     * @return AbstractContext
     * @throws \Exception
     */
    public function getContext()
    {
        if (empty($this->currentContext)) {
            throw new \Exception('context is not defined');
        }

        return $this->currentContext;
    }

    /**
     * This is just an alias and a annotation helper
     * @return ContextInterface|Language
     */
    public function getLanguageContext()
    {
        return $this->getContext();
    }

    /**
     * This is just an alias and a annotation helper
     * @return ContextInterface|Country
     */
    public function getCountryContext()
    {
        return $this->getContext();
    }
}