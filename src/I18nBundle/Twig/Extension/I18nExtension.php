<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Exception\ContextNotDefinedException;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Manager\ContextManager;

class I18nExtension extends \Twig_Extension
{
    /**
     * @var ZoneManager
     */
    public $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * CategoriesExtension constructor.
     *
     * @param ZoneManager    $zoneManager
     * @param ContextManager $contextManager
     */
    public function __construct(ZoneManager $zoneManager, ContextManager $contextManager)
    {
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('i18n_context', [$this, 'getI18Context']),
            new \Twig_SimpleFunction('i18n_zone_info', [$this, 'getI18nZoneInfo'])
        ];
    }

    /**
     * @param string $method
     * @param array  $options
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getI18Context($method = '', $options = [])
    {
        try {
            $context = $this->contextManager->getContext();
        } catch (ContextNotDefinedException $e) {
            return null;
        }

        return call_user_func_array([$context, $method], $options);
    }

    /**
     * @param string $slot
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getI18nZoneInfo($slot = '')
    {
        return $this->zoneManager->getCurrentZoneInfo($slot);
    }
}
