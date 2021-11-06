<?php

namespace I18nBundle\LinkGenerator;

use I18nBundle\Model\RouteItem\LinkGeneratorRouteItemInterface;
use Pimcore\Model\DataObject\Concrete;

interface I18nLinkGeneratorInterface
{
    public function getStaticRouteName(Concrete $object): string;

    public function generateRouteItem(Concrete $object, LinkGeneratorRouteItemInterface $linkGeneratorRouteItem): LinkGeneratorRouteItemInterface;
}
