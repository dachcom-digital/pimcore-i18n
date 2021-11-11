<?php

namespace I18nBundle\Model;

class ZoneSite implements ZoneSiteInterface
{
    protected SiteRequestContext $siteRequestContext;
    protected int $rootId;
    protected bool $isRootDomain;
    protected ?string $locale;
    protected ?string $countryIso;
    protected string $languageIso;
    protected string $hrefLang;
    protected ?string $localeUrlMapping;
    protected string $url;
    protected ?string $homeUrl;
    protected string $rootPath;
    protected string $fullPath;
    protected ?string $type;
    protected array $subSites;

    public function __construct(
        SiteRequestContext $siteRequestContext,
        int $rootId,
        bool $isRootDomain,
        ?string $locale,
        ?string $countryIso,
        string $languageIso,
        string $hrefLang,
        ?string $localeUrlMapping,
        string $url,
        ?string $homeUrl,
        string $rootPath,
        string $fullPath,
        ?string $type,
        array $subSites = []
    ) {
        $this->siteRequestContext = $siteRequestContext;
        $this->rootId = $rootId;
        $this->isRootDomain = $isRootDomain;
        $this->locale = $locale;
        $this->countryIso = $countryIso;
        $this->languageIso = $languageIso;
        $this->hrefLang = $hrefLang;
        $this->localeUrlMapping = $localeUrlMapping;
        $this->url = $url;
        $this->homeUrl = $homeUrl;
        $this->rootPath = $rootPath;
        $this->fullPath = $fullPath;
        $this->type = $type;
        $this->subSites = $subSites;
    }

    public function getSiteRequestContext(): SiteRequestContext
    {
        return $this->siteRequestContext;
    }

    public function getRootId(): int
    {
        return $this->rootId;
    }

    public function isRootDomain(): bool
    {
        return $this->isRootDomain;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getCountryIso(): ?string
    {
        return $this->countryIso;
    }

    public function getLanguageIso(): string
    {
        return $this->languageIso;
    }

    public function getHrefLang(): string
    {
        return $this->hrefLang;
    }

    public function getLocaleUrlMapping(): ?string
    {
        return $this->localeUrlMapping;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHomeUrl(): ?string
    {
        return $this->homeUrl;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getSubSites(): array
    {
        return $this->subSites;
    }

    public function hasSubSites(): bool
    {
        return count($this->subSites) > 0;
    }
}
