<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Registry\PathGeneratorRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class PathGeneratorManager
{
    /**
     * @var RequestStack
     */
    protected $configuration;

    /**
     * @var PathGeneratorRegistry
     */
    protected $pathGeneratorRegistry;

    /**
     * @var
     */
    protected $currentPathGenerator;

    /**
     * {@inheritdoc}
     */
    public function __construct(Configuration $configuration, PathGeneratorRegistry $pathGeneratorRegistry)
    {
        $this->configuration = $configuration;
        $this->pathGeneratorRegistry = $pathGeneratorRegistry;
    }

    /**
     * @param $contextIdentifier
     *
     * @throws \Exception
     */
    public function initPathGenerator($contextIdentifier)
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
     * @return PathGeneratorInterface
     * @throws \Exception
     */
    public function getPathGenerator()
    {
        if (empty($this->currentPathGenerator)) {
            throw new \Exception('path generator is not configured');
        }

        return $this->currentPathGenerator;
    }
}