<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Exception\ContextNotDefinedException;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Manager\ContextManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class I18nExtension extends AbstractExtension
{
    protected ZoneManager $zoneManager;
    protected ContextManager $contextManager;

    public function __construct(ZoneManager $zoneManager, ContextManager $contextManager)
    {
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_context', [$this, 'getI18Context']),
            new TwigFunction('i18n_zone_info', [$this, 'getI18nZoneInfo'])
        ];
    }

    public function getI18Context(string $method = '', array $options = []): mixed
    {
        try {
            $context = $this->contextManager->getContext();
        } catch (ContextNotDefinedException $e) {
            return null;
        }

        return call_user_func_array([$context, $method], $options);
    }

    public function getI18nZoneInfo(string $slot = ''): mixed
    {
        return $this->zoneManager->getCurrentZoneInfo($slot);
    }
}
