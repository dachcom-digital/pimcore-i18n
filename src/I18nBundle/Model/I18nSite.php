<?php

namespace I18nBundle\Model;

class I18nSite implements I18nSiteInterface
{
    protected int $rootId;
    protected string $host;
    protected string $realHost;
    protected bool $isRootDomain;
    protected ?string $locale;
    protected ?string $countryIso;
    protected string $languageIso;
    protected string $hrefLang;
    protected ?string $localeUrlMapping;
    protected string $url;
    protected ?string $homeUrl;
    protected ?string $domainUrl;
    protected string $fullPath;
    protected ?string $type;
    protected array $subSites;

    public function __construct(
        int $rootId,
        string $host,
        string $realHost,
        bool $isRootDomain,
        ?string $locale,
        ?string $countryIso,
        string $languageIso,
        string $hrefLang,
        ?string $localeUrlMapping,
        string $url,
        ?string $homeUrl,
        ?string $domainUrl,
        string $fullPath,
        ?string $type,
        array $subSites = []
    ) {
        $this->rootId = $rootId;
        $this->host = $host;
        $this->realHost = $realHost;
        $this->isRootDomain = $isRootDomain;
        $this->locale = $locale;
        $this->countryIso = $countryIso;
        $this->languageIso = $languageIso;
        $this->hrefLang = $hrefLang;
        $this->localeUrlMapping = $localeUrlMapping;
        $this->url = $url;
        $this->homeUrl = $homeUrl;
        $this->domainUrl = $domainUrl;
        $this->fullPath = $fullPath;
        $this->type = $type;
        $this->subSites = $subSites;
    }

    public function getRootId(): int
    {
        return $this->rootId;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getRealHost(): string
    {
        return $this->realHost;
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

    public function getDomainUrl(): ?string
    {
        return $this->domainUrl;
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
