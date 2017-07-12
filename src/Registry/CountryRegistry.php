<?php

namespace I18nBundle\Registry;

class CountryRegistry
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
    public function register($identifier, $service)
    {
        if (!in_array($this->interface, class_implements($service), TRUE)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to implement "%s", "%s" given.', get_class($service), $this->interface, implode(', ', class_implements($service)))
            );
        }

        $this->adapter[$identifier] = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function has($identifier)
    {
        return isset($this->adapter[$identifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($identifier)
    {
        if (!$this->has($identifier)) {
            throw new \Exception('"' . $identifier . '" Country Identifier does not exist');
        }

        return $this->adapter[$identifier];
    }

}
