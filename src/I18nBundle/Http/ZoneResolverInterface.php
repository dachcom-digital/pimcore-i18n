<?php

namespace I18nBundle\Http;

use I18nBundle\Model\I18nZoneInterface;
use Symfony\Component\HttpFoundation\Request;

interface ZoneResolverInterface
{
    public function setZone(I18nZoneInterface $i18nZone, Request $request);

    public function getZone(Request $request): ?I18nZoneInterface;

    public function hasZone(Request $request): bool;
}
