<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Manager\ZoneManager;
use I18nBundle\Manager\ContextManager;

class I18nExtension extends \Twig_Extension
{
    /**
     * @var ZoneManager
     */
    var $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * CategoriesExtension constructor.
     *
     * @param ZoneManager $zoneManager
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
            new \Twig_SimpleFunction('i18n_context', [$this, 'getI18Context'], ['needs_context' => true]),
            new \Twig_SimpleFunction('i18n_zone_info', [$this, 'getI18nZoneInfo'])
        ];
    }

    /**
     * @param array $context
     * @param string $method
     * @param array  $options
     *
     * @return mixed
     */
    public function getI18Context($context, $method = '', $options = [])
    {
        $document = $context['document'];
        $this->contextManager->setDocumentToCurrentContext($document);
        return call_user_func_array([$this->contextManager->getContext(), $method], $options);
    }

    /**
     * @param string $slot
     * @return mixed
     */
    public function getI18nZoneInfo($slot = '')
    {
        return $this->zoneManager->getCurrentZoneInfo($slot);
    }
}