<?php

namespace I18nBundle\Registry;

class RedirectorRegistry
{
    protected array $adapter = [];
    private string $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function register(mixed $service, string $alias): void
    {
        if (empty($alias)) {
            throw new \InvalidArgumentException(sprintf('%s does not have a valid alias.', get_class($service)));
        }

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

    public function get(string $alias)
    {
        if (!$this->has($alias)) {
            throw new \Exception('"' . $alias . '" redirector identifier does not exist');
        }

        return $this->adapter[$alias];
    }

    public function all(): array
    {
        $list = [];
        foreach ($this->adapter as $adapter) {
            if (!$adapter->isEnabled()) {
                continue;
            }
            $list[] = $adapter;
        }

        return $list;
    }
}
