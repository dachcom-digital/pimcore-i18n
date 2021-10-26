<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Event\AlternateDynamicRouteEvent;
use I18nBundle\I18nEvents;
use I18nBundle\Tool\System;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StaticRoute extends AbstractPathGenerator
{
    protected array $options;
    protected UrlGeneratorInterface $urlGenerator;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options
            ->setDefaults(['attributes'])
            ->setRequired(['attributes'])
            ->setAllowedTypes('attributes', ['array']);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getUrls(bool $onlyShowRootLanguages = false): array
    {
        $i18nList = [];
        $routes = [];

        if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
            throw new \Exception('PathGenerator StaticRoute needs a valid UrlGeneratorInterface to work.');
        }

        //create custom list for event ($i18nList) - do not include all the zone config stuff.
        foreach ($this->zone->getSites(true) as $zoneSite) {
            if (!empty($zoneSite->getLanguageIso())) {
                $i18nList[] = [
                    'locale'           => $zoneSite->getLocale(),
                    'languageIso'      => $zoneSite->getLanguageIso(),
                    'countryIso'       => $zoneSite->getCountryIso(),
                    'hrefLang'         => $zoneSite->getHrefLang(),
                    'localeUrlMapping' => $zoneSite->getLocaleUrlMapping(),
                    'url'              => $zoneSite->getUrl(),
                    'domainUrl'        => $zoneSite->getDomainUrl()
                ];
            }
        }

        $event = new AlternateDynamicRouteEvent('static_route', [
            'i18nList'      => $i18nList,
            'currentLocale' => $this->zone->getContext()->getLocale(),
            'attributes'    => $this->options['attributes']
        ]);

        $this->eventDispatcher->dispatch($event, I18nEvents::PATH_ALTERNATE_STATIC_ROUTE);

        $routeData = $event->getRoutes();

        if (empty($routeData)) {
            return $routes;
        }

        foreach ($i18nList as $key => $routeInfo) {

            if (!isset($routeData[$key])) {
                continue;
            }

            $link = $this->generateLink($routeData[$key]);

            if ($link === null) {
                continue;
            }

            // use domainUrl element since $link already comes with the locale part!
            $url = str_contains($link, 'http') ? $link : System::joinPath([$routeInfo['domainUrl'], $link]);

            $routes[] = [
                'languageIso'      => $routeInfo['languageIso'],
                'countryIso'       => $routeInfo['countryIso'],
                'locale'           => $routeInfo['locale'],
                'hrefLang'         => $routeInfo['hrefLang'],
                'localeUrlMapping' => $routeInfo['localeUrlMapping'],
                'url'              => $url
            ];
        }

        return $routes;
    }

    protected function generateLink(array $staticRouteData): ?string
    {
        $staticRouteParams = $staticRouteData['params'];

        if (!is_array($staticRouteParams)) {
            $staticRouteParams = [];
        }

        if (isset($staticRouteData['name']) && is_string($staticRouteData['name'])) {
            return $this->urlGenerator->generate($staticRouteData['name'], $staticRouteParams);
        }

        if (!isset($staticRouteData['object']) || !$staticRouteData['object'] instanceof Concrete) {
            return null;
        }

        $object = $staticRouteData['object'];
        $linkGenerator = $object->getClass()?->getLinkGenerator();

        if (!$linkGenerator instanceof LinkGeneratorInterface) {
            return null;
        }

        return $linkGenerator->generate($object, $staticRouteParams);
    }
}
