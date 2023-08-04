<?php

namespace I18nBundle\Model;

interface ZoneInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function getDefaultLocale(): ?string;

    public function getActiveLocales(): array;

    public function getLocaleAdapterName(): string;

    public function getDomains(): array;

    public function getMode(): string;

    public function getTranslations(): array;

    public function isActiveZone(): bool;

    public function getLocaleUrlMapping(): array;

    public function getGlobalInfo(): array;

    /**
     * @return array<int, ZoneSiteInterface>
     */
    public function getSites(bool $flatten = false): array;

}
