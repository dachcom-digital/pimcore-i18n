<?php

namespace I18nBundle\Session;

use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionConfigurator implements SessionConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(SessionInterface $session)
    {
        $bag = new NamespacedAttributeBag('_i18n_session');
        $bag->setName('i18n_session');
        $session->registerBag($bag);
    }
}
