<?php

namespace I18nBundle\Adapter\Locale;

interface LocaleInterface
{
    public function setCurrentZoneConfig(?int $zoneId, ?array $zoneConfig): void;

    public function getActiveLocales(): array;

    public function getLocaleData(string $locale, ?string $field = null, string $keyIdentifier = 'locale'): mixed;

    public function getDefaultLocale(): ?string;

    public function getGlobalInfo(): array;
}
