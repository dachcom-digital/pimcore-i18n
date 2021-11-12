<?php

namespace I18nBundle\Manager;

use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Builder\RouteItemBuilder;
use I18nBundle\Builder\ZoneBuilder;
use I18nBundle\Builder\ZoneSitesBuilder;
use I18nBundle\Context\I18nContext;
use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use I18nBundle\Exception\RouteItemException;
use I18nBundle\Exception\ZoneSiteNotFoundException;
use I18nBundle\Model\LocaleDefinition;
use I18nBundle\Model\LocaleDefinitionInterface;
use I18nBundle\Model\Zone;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Registry\LocaleProviderRegistry;
use I18nBundle\Registry\PathGeneratorRegistry;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class I18nContextManager
{
    protected RequestHelper $requestHelper;
    protected ZoneBuilder $zoneBuilder;
    protected ZoneSitesBuilder $zoneSitesBuilder;
    protected RouteItemBuilder $routeItemBuilder;
    protected LocaleProviderRegistry $localeProviderRegistry;
    protected PathGeneratorRegistry $pathGeneratorRegistry;

    public function __construct(
        RequestHelper $requestHelper,
        ZoneBuilder $zoneBuilder,
        ZoneSitesBuilder $zoneSitesBuilder,
        RouteItemBuilder $routeItemBuilder,
        LocaleProviderRegistry $localeProviderRegistry,
        PathGeneratorRegistry $pathGeneratorRegistry,
    ) {
        $this->requestHelper = $requestHelper;
        $this->zoneBuilder = $zoneBuilder;
        $this->zoneSitesBuilder = $zoneSitesBuilder;
        $this->routeItemBuilder = $routeItemBuilder;
        $this->localeProviderRegistry = $localeProviderRegistry;
        $this->pathGeneratorRegistry = $pathGeneratorRegistry;
    }

    /**
     * @throws ZoneSiteNotFoundException
     * @throws RouteItemException
     */
    public function buildContextByParameters(string $type, array $i18nRouteParameters, bool $fullBootstrap = false): ?I18nContextInterface
    {
        $routeItem = $this->routeItemBuilder->buildRouteItemByParameters($type, $i18nRouteParameters);

        if (!$routeItem instanceof RouteItemInterface) {
            return null;
        }

        $zone = $this->setupZone($routeItem, false, $fullBootstrap);
        $pathGenerator = $this->setupPathGenerator($routeItem, $fullBootstrap);
        $localeDefinition = $this->buildLocaleDefinition($routeItem);

        return new I18nContext($routeItem, $zone, $localeDefinition, $pathGenerator);
    }

    /**
     * @throws ZoneSiteNotFoundException
     * @throws RouteItemException
     */
    public function buildContextByRequest(Request $baseRequest, ?Document $baseDocument, bool $fullBootstrap = false): ?I18nContextInterface
    {
        $routeItem = $this->routeItemBuilder->buildRouteItemByRequest($baseRequest, $baseDocument);

        if (!$routeItem instanceof RouteItemInterface) {
            return null;
        }

        $zone = $this->setupZone($routeItem, $this->requestHelper->isFrontendRequestByAdmin($baseRequest), $fullBootstrap);
        $pathGenerator = $this->setupPathGenerator($routeItem, $fullBootstrap);
        $localeDefinition = $this->buildLocaleDefinition($routeItem);

        return new I18nContext($routeItem, $zone, $localeDefinition, $pathGenerator);
    }

    protected function setupPathGenerator(RouteItemInterface $routeItem, bool $fullBootstrap = false): ?PathGeneratorInterface
    {
        if ($fullBootstrap === false) {
            return null;
        }

        $pathGeneratorOptionsResolver = new OptionsResolver();
        $pathGeneratorOptionsResolver->setDefined(array_keys($routeItem->getRouteAttributes()));
        $pathGeneratorOptionsResolver->resolve($routeItem->getRouteAttributes());

        $pathGenerator = $this->buildPathGenerator($routeItem->getType());
        $pathGenerator->configureOptions($pathGeneratorOptionsResolver);

        return $pathGenerator;
    }

    protected function setupZone(RouteItemInterface $routeItem, bool $isFrontendRequestByAdmin = false, bool $fullBootstrap = false): ZoneInterface
    {
        $zone = $this->zoneBuilder->buildZone($routeItem);

        // we don't want to add those two methods to the interface
        // since they are kind of internal!
        if ($zone instanceof Zone) {
            $zone->processProviderLocales($this->localeProviderRegistry->get($zone->getLocaleAdapterName()));
            $zone->setSites($this->zoneSitesBuilder->buildZoneSites($zone, $routeItem, $fullBootstrap, $isFrontendRequestByAdmin));
        }

        return $zone;
    }

    protected function buildLocaleDefinition(RouteItemInterface $routeItem): LocaleDefinitionInterface
    {
        $baseLocale = $routeItem->getLocaleFragment();

        $locale = $baseLocale === '' ? null : $baseLocale;
        $languageIso = $locale;
        $countryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;

        if (str_contains($baseLocale, '_')) {
            $parts = explode('_', $baseLocale);
            $languageIso = strtolower($parts[0]);
            if (isset($parts[1]) && !empty($parts[1])) {
                $countryIso = strtoupper($parts[1]);
            }
        }

        return new LocaleDefinition(
            $locale,
            $languageIso,
            $countryIso
        );
    }

    public function buildPathGenerator(?string $pathGeneratorIdentifier): PathGeneratorInterface
    {
        if (!$this->pathGeneratorRegistry->has($pathGeneratorIdentifier)) {
            throw new \Exception(
                sprintf('path.generator adapter "%s" is not available. please use "%s" tag to register new adapter and add "%s" as a alias.',
                    $pathGeneratorIdentifier,
                    'i18n.adapter.path.generator',
                    $pathGeneratorIdentifier
                )
            );
        }

        return $this->pathGeneratorRegistry->get($pathGeneratorIdentifier);
    }

}
