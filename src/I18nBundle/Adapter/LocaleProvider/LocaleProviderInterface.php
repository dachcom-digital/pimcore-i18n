<?php

namespace I18nBundle\Adapter\LocaleProvider;

interface LocaleProviderInterface
{
    public function getActiveLocales(array $zoneDefinition): array;

    public function getLocaleData(array $zoneDefinition, string $locale, string $field, string $keyIdentifier = 'locale'): mixed;

    public function getDefaultLocale(array $zoneDefinition): ?string;

    public function getGlobalInfo(array $zoneDefinition): array;
}
