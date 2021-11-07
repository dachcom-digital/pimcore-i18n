<?php

namespace I18nBundle\Model;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;

class Zone implements ZoneInterface
{
    protected ?int $id;
    protected ?string $name;
    protected ?string $zoneDefaultLocale;
    protected string $localeAdapterName;
    protected string $mode;
    protected array $translations;
    protected array $domains;
    protected array $activeLocales = [];
    protected array $globalInfo = [];
    protected ?string $providerDefaultLocale = null;
    protected bool $providerLocalesDispatched = false;
    protected array $sites = [];
    protected array $localeUrlMapping;

    public function __construct(
        ?int $id,
        ?string $name,
        ?string $zoneDefaultLocale,
        string $localeAdapterName,
        string $mode,
        array $translations,
        array $domains
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->zoneDefaultLocale = $zoneDefaultLocale;
        $this->localeAdapterName = $localeAdapterName;
        $this->mode = $mode;
        $this->translations = $translations;
        $this->domains = $domains;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDefaultLocale(): ?string
    {
        if (!is_null($this->zoneDefaultLocale)) {
            return $this->zoneDefaultLocale;
        }

        return $this->providerDefaultLocale;
    }

    public function getActiveLocales(): array
    {
        return $this->activeLocales;
    }

    public function getLocaleAdapterName(): string
    {
        return $this->localeAdapterName;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function isActiveZone(): bool
    {
        return $this->id !== null;
    }

    public function getLocaleUrlMapping(): array
    {
        return $this->localeUrlMapping;
    }

    public function getGlobalInfo(): array
    {
        return $this->globalInfo;
    }

    public function getSites(bool $flatten = false): array
    {
        return $flatten ? $this->flattenSites($this->sites) : $this->sites;
    }

    public function setSites(array $sites): void
    {
        if (count($this->sites) > 0) {
            throw new \Exception(sprintf('Sites for zone %d already have been declared', $this->getId()));
        }

        $this->sites = $sites;
        $this->localeUrlMapping = $this->buildLocaleUrlMappingForCurrentZone();
    }

    public function processProviderLocales(LocaleProviderInterface $provider): void {

        if($this->providerLocalesDispatched === true) {
            throw new \Exception(sprintf('Provider locales zone %d already have been declared', $this->getId()));
        }

        $this->providerLocalesDispatched = true;

        $this->activeLocales = $provider->getActiveLocales($this);
        $this->providerDefaultLocale = $provider->getDefaultLocale($this);
        $this->globalInfo = $provider->getGlobalInfo($this);
    }

    protected function buildLocaleUrlMappingForCurrentZone(): array
    {
        $localeUrlMapping = [];

        foreach ($this->getSites(true) as $i18nZoneSite) {
            if (!empty($i18nZoneSite->getLocale())) {
                $localeUrlMapping[$i18nZoneSite->getLocale()] = $i18nZoneSite->getLocaleUrlMapping();
            }
        }

        return $localeUrlMapping;
    }

    /**
     * @return array<int, ZoneSiteInterface>
     */
    protected function flattenSites(array $i18nZoneSites): array
    {
        $elements = [];
        /** @var ZoneSiteInterface $i18nZoneSite */
        foreach ($i18nZoneSites as $i18nZoneSite) {

            if (!empty($i18nZoneSite->getCountryIso()) || !empty($i18nZoneSite->getLanguageIso())) {
                $elements[] = $i18nZoneSite;
            }

            if ($i18nZoneSite->hasSubSites()) {
                foreach ($i18nZoneSite->getSubSites() as $subSite) {
                    $elements[] = $subSite;
                }
            }

            $elements[] = $i18nZoneSite;
        }

        return $elements;
    }
}
