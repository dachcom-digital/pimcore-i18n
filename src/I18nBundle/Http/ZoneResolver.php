<?php

namespace I18nBundle\Http;

use I18nBundle\Definitions;
use I18nBundle\Model\I18nZoneInterface;
use Symfony\Component\HttpFoundation\Request;

class ZoneResolver implements ZoneResolverInterface
{
    public function setZone(I18nZoneInterface $i18nZone, Request $request): void
    {
        if ($this->hasZone($request)) {
            throw new \Exception('I18n zone already has been resolved');
        }

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_ZONE, $i18nZone);
    }

    public function getZone(Request $request): ?I18nZoneInterface
    {
        return $request->attributes->get(Definitions::ATTRIBUTE_I18N_ZONE);
    }

    public function hasZone(Request $request): bool
    {
        $zone = $this->getZone($request);

        return $zone instanceof I18nZoneInterface;
    }
}
