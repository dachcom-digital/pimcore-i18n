<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Registry\PathGeneratorRegistry;

class PathGeneratorManager
{
    protected Configuration $configuration;
    protected PathGeneratorRegistry $pathGeneratorRegistry;
    protected PathGeneratorInterface $currentPathGenerator;

    public function __construct(Configuration $configuration, PathGeneratorRegistry $pathGeneratorRegistry)
    {
        $this->configuration = $configuration;
        $this->pathGeneratorRegistry = $pathGeneratorRegistry;
    }

    public function initPathGenerator(?string $contextIdentifier): void
    {
        if ($contextIdentifier === 'staticroute') {
            $contextId = 'static_route';
        } else {
            $contextId = 'document';
        }

        if (!empty($this->currentContext)) {
            throw new \Exception('context already defined');
        }

        if (!$this->pathGeneratorRegistry->has($contextId)) {
            throw new \Exception(sprintf('path.generator adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.', $contextId, 'i18n.adapter.path.generator', $contextId));
        }

        $this->currentPathGenerator = $this->pathGeneratorRegistry->get($contextId);
    }

    /**
     * @throws \Exception
     */
    public function getPathGenerator(): PathGeneratorInterface
    {
        if (empty($this->currentPathGenerator)) {
            throw new \Exception('path generator is not configured');
        }

        return $this->currentPathGenerator;
    }
}
