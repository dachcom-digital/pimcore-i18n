<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Pathfinder\Pathfinder;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class FrontendPathListener implements EventSubscriberInterface
{
    /**
     * @var Pathfinder
     */
    protected $pathfinder;

    /**
     * FrontendPathListener constructor.
     *
     * @param Pathfinder $pathfinder
     */
    public function __construct(Pathfinder $pathfinder)
    {
        $this->pathfinder = $pathfinder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FrontendEvents::DOCUMENT_PATH => ['onFrontendPathRequest']
        ];
    }

    /**
     * Valid Paths:
     *
     * /de/test
     * /global-de/test
     * /de-de/test
     *
     * @param GenericEvent $e
     *
     * @return void
     */
    public function onFrontendPathRequest(GenericEvent $e)
    {
        $frontEndPath = $e->getArgument('frontendPath');
        $i18nFrontEndPath = $this->pathfinder->checkPath($frontEndPath);

        if($i18nFrontEndPath !== FALSE) {
            $e->setArgument('frontendPath', $i18nFrontEndPath);
        }
    }
}