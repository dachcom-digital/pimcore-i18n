<?php

namespace I18nBundle\Registry;

class PathGeneratorRegistry
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

    /**
     * {@inheritdoc}
     */
    public function register($service, $alias)
    {
        if (!in_array($this->interface, class_implements($service), true)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to implement "%s", "%s" given.', get_class($service), $this->interface,
                    implode(', ', class_implements($service)))
            );
        }

        $this->adapter[$alias] = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function has($alias)
    {
        return isset($this->adapter[$alias]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias)
    {
        if (!$this->has($alias)) {
            throw new \Exception('"' . $alias . '" PathGenerator Identifier does not exist');
        }

        return $this->adapter[$alias];
    }

}
