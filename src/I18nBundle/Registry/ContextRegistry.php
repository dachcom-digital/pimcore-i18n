<?php

namespace I18nBundle\Registry;

class ContextRegistry
{
    /**
     * @var array
     */
    protected $adapter;

    /**
     * @var string
     */
    private $interface;

    /**
     * @param string $interface
     */
    public function __construct($interface)
    {
        $this->interface = $interface;
    }

    public function register($service, $alias)
    {
        if (!in_array($this->interface, class_implements($service), true)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to implement "%s", "%s" given.', get_class($service), $this->interface, implode(', ', class_implements($service)))
            );
        }

        $this->adapter[$alias] = $service;
    }

    public function has($alias)
    {
        return isset($this->adapter[$alias]);
    }

    public function get($alias)
    {
        if (!$this->has($alias)) {
            throw new \Exception('"' . $alias . '" Context Identifier does not exist');
        }

        return $this->adapter[$alias];
    }
}
