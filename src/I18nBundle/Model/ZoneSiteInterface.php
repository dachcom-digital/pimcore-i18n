<?php

namespace I18nBundle\Model;

interface ZoneSiteInterface
{
    public function getSiteRequestContext(): SiteRequestContext;

    public function getRootId(): int;

    public function isRootDomain(): bool;

    public function isActive(): bool;

    public function getLocale(): ?string;

    public function getCountryIso(): ?string;

    public function getLanguageIso(): string;

    public function getHrefLang(): string;

    public function getLocaleUrlMapping(): ?string;

    public function getUrl(): string;

    public function getHomeUrl(): ?string;

    public function getRootPath(): string;

    public function getFullPath(): string;

    public function getType(): ?string;

    public function getSubSites(): array;

    public function hasSubSites(): bool;
}
