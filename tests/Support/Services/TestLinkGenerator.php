<?php

namespace DachcomBundle\Test\Services;

use I18nBundle\LinkGenerator\I18nLinkGeneratorInterface;
use I18nBundle\Model\RouteItem\LinkGeneratorRouteItemInterface;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;

class TestLinkGenerator implements LinkGeneratorInterface, I18nLinkGeneratorInterface
{
    public function getStaticRouteName(Concrete $object): string
    {
        return 'test_route';
    }

    public function generateRouteItem(Concrete $object, LinkGeneratorRouteItemInterface $linkGeneratorRouteItem): LinkGeneratorRouteItemInterface
    {
        $linkGeneratorRouteItem->getRouteParametersBag()->set('object_id', $object->getId());

        return $linkGeneratorRouteItem;
    }

    public function generate(object $object, array $params = []): string
    {
        return '';
    }
}