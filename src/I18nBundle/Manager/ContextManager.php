<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Context\AbstractContext;
use I18nBundle\Adapter\Context\ContextInterface;
use I18nBundle\Adapter\Context\Country;
use I18nBundle\Adapter\Context\Language;
use I18nBundle\Registry\ContextRegistry;
use Pimcore\Model\Document;

class ContextManager
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * Stores the current Context info.
     *
     * @var AbstractContext
     */
    protected $currentContext;

    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * @param string   $contextIdentifier
     * @param Document $document
     *
     * @throws \Exception
     */
    public function initContext($contextIdentifier, $document = null)
    {
        $contextId = $contextIdentifier;

        if (!empty($this->currentContext)) {
            return;
        }

        if (!$this->contextRegistry->has($contextId)) {
            throw new \Exception(sprintf(
                'context adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                $contextId,
                'i18n.adapter.context',
                $contextId
            ));
        }

        $this->currentContext = $this->contextRegistry->get($contextId);

        if ($document instanceof Document) {
            $this->currentContext->setDocument($document);
        }
    }

    /**
     * @return ContextInterface
     *
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
     * This is just an alias and a annotation helper.
     *
     * @return Language|ContextInterface
     *
     * @throws \Exception
     */
    public function getLanguageContext()
    {
        return $this->getContext();
    }

    /**
     * This is just an alias and a annotation helper.
     *
     * @return Country|ContextInterface
     *
     * @throws \Exception
     */
    public function getCountryContext()
    {
        return $this->getContext();
    }
}
