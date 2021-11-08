<?php

namespace I18nBundle\Registry;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;

class LocaleProviderRegistry
{
    protected array $adapter = [];
    private string $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function register(mixed $service, string $alias): void
    {
        if (!in_array($this->interface, class_implements($service), true)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to implement "%s", "%s" given.', get_class($service), $this->interface, implode(', ', class_implements($service)))
            );
        }

        $this->adapter[$alias] = $service;
    }

    public function has(string $alias): bool
    {
        return isset($this->adapter[$alias]);
    }

    public function get(string $alias): LocaleProviderInterface
    {
        if (!$this->has($alias)) {
            throw new \Exception('"' . $alias . '" locale provider does not exist');
        }

        return $this->adapter[$alias];
    }
}
