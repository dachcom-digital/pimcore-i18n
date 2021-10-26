<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Model\I18nZoneInterface;

abstract class AbstractPathGenerator implements PathGeneratorInterface
{
    protected I18nZoneInterface $zone;

    /**
     * @internal
     */
    public function setZone(I18nZoneInterface $zone): void
    {
        $this->zone = $zone;
    }
}
