<?php

namespace I18nBundle\Adapter\LocaleProvider;

use I18nBundle\Model\ZoneInterface;

interface LocaleProviderInterface
{
    public function getActiveLocales(ZoneInterface $zone): array;

    public function getDefaultLocale(ZoneInterface $zone): ?string;

    public function getGlobalInfo(ZoneInterface $zone): array;
}
