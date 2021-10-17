<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\Context\ContextInterface;
use I18nBundle\Adapter\Context\Country;
use I18nBundle\Adapter\Context\Language;
use I18nBundle\Exception\ContextNotDefinedException;
use I18nBundle\Registry\ContextRegistry;
use Pimcore\Model\Document;

class ContextManager
{
    protected ContextRegistry $contextRegistry;
    protected ContextInterface $currentContext;

    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    public function initContext(string $contextIdentifier, ?Document $document = null): void
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

    public function getContext() :ContextInterface
    {
        if (empty($this->currentContext)) {
            throw new ContextNotDefinedException();
        }

        return $this->currentContext;
    }

    /**
     * This is just an alias and an annotation helper.
     *
     * @throws \Exception
     */
    public function getLanguageContext(): Language|ContextInterface
    {
        return $this->getContext();
    }

    /**
     * This is just an alias and an annotation helper.
     *
     * @throws \Exception
     */
    public function getCountryContext(): Country|ContextInterface
    {
        return $this->getContext();
    }
}
