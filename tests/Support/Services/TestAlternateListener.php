<?php

namespace DachcomBundle\Test\Support\Services;

use I18nBundle\Event\AlternateDynamicRouteEvent;
use I18nBundle\I18nEvents;
use Pimcore\Model\DataObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestAlternateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            I18nEvents::PATH_ALTERNATE_STATIC_ROUTE  => 'checkStaticRouteAlternate',
            I18nEvents::PATH_ALTERNATE_SYMFONY_ROUTE => 'checkSymfonyRouteAlternate',
        ];
    }

    public function checkStaticRouteAlternate(AlternateDynamicRouteEvent $event): void
    {
        $attributes = $event->isCurrentRouteHeadless() ? $event->getCurrentRouteParameters() : $event->getCurrentRouteAttributes();

        // depending on given route, you may want to build different alternate route items
        if ($event->getCurrentRouteName() !== 'test_route') {
            return;
        }

        $objectId = $attributes->get('object_id');
        $object = DataObject\TestClass::getById($objectId);

        if (!$object instanceof DataObject) {
            return;
        }

        foreach ($event->getAlternateRouteItems() as $alternateRouteItem) {
            $alternateRouteItem->setEntity($object);
        }
    }

    public function checkSymfonyRouteAlternate(AlternateDynamicRouteEvent $event): void
    {
        $attributes = $event->isCurrentRouteHeadless() ? $event->getCurrentRouteParameters() : $event->getCurrentRouteAttributes();

        $route = $event->getCurrentRouteName();

        foreach ($event->getAlternateRouteItems() as $alternateRouteItem) {
            $alternateRouteItem->setRouteName($route);
        }
    }
}
