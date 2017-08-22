<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Finder\PathFinder;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class FrontendPathStaticRouteListener implements EventSubscriberInterface
{
    /**
     * @var PathFinder
     */
    protected $pathfinder;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * FrontendPathListener constructor.
     *
     * @param PathFinder $pathfinder
     * @param ZoneManager $zoneManager
     */
    public function __construct(PathFinder $pathfinder, ZoneManager $zoneManager)
    {
        $this->pathfinder = $pathfinder;
        $this->zoneManager = $zoneManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FrontendEvents::STATICROUTE_PATH => ['onFrontendPathStaticRouteRequest']
        ];
    }

    /**
     * @todo: check global prefix, remove it.
     * @param GenericEvent $e
     */
    public function onFrontendPathStaticRouteRequest(GenericEvent $e)
    {
        $frontEndPath = $e->getArgument('frontendPath');
        $params = $e->getArgument('params');
        $reset = $e->getArgument('reset');
        $encode = $e->getArgument('encode');

    }
}