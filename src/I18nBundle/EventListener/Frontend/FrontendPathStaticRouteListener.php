<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Tool\System;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontendPathStaticRouteListener implements EventSubscriberInterface
{
    protected ZoneResolverInterface $zoneResolver;
    protected RequestStack $requestStack;

    public function __construct(
        RequestStack $requestStack,
        ZoneResolverInterface $zoneResolver
    ) {
        $this->zoneResolver = $zoneResolver;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FrontendEvents::STATICROUTE_PATH => ['onFrontendPathStaticRouteRequest']
        ];
    }

    public function onFrontendPathStaticRouteRequest(GenericEvent $event): void
    {
        $zone = null;
        $frontEndPath = $event->getArgument('frontendPath');
        $params = $event->getArgument('params');

        if (isset($params['_18n_zone'])) {
            // 1. if zone is available as parameter,
            // we'll always prefer it because it could be a sub zone url generation
            $zone = $params['_18n_zone'];
        } elseif ($this->requestStack->getMainRequest() instanceof Request) {
            // 2. check zone resolver from global request
            $zone = $this->zoneResolver->getZone($this->requestStack->getMainRequest());
        }

        if (!$zone instanceof I18nZoneInterface) {
            throw new \Exception('Could not resolve zone to build i18n aware static route');
        }

        if (!isset($params['_locale'])) {
            return;
        }

        $locale = $params['_locale'];
        $urlMapping = $zone->getLocaleUrlMapping();
        $zoneTranslations = $zone->getTranslations();

        $validLocaleIso = array_search($params['_locale'], $urlMapping, true);

        if ($validLocaleIso !== false) {
            $locale = $validLocaleIso;
        }

        $frontEndPath = preg_replace_callback(
            '/@((?:(?![\/|?]).)*)/',
            function ($matches) use ($locale, $zoneTranslations) {
                return $this->translateKey($matches[1], $locale, $zoneTranslations);
            },
            $frontEndPath
        );

        //transform locale style to given url mapping - if existing
        if (array_key_exists($params['_locale'], $urlMapping)) {
            $fragments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $frontEndPath)));
            if ($fragments[0] === $params['_locale']) {
                //replace first value in array!
                $fragments[0] = $urlMapping[$params['_locale']];
                $addSlash = substr($frontEndPath, 0, 1) === DIRECTORY_SEPARATOR;
                $frontEndPath = System::joinPath($fragments, $addSlash);
            }
        }

        $event->setArgument('frontendPath', $frontEndPath);
    }

    /**
     * @throws \Exception
     */
    private function translateKey(string $key, string $locale, array $zoneTranslations): string
    {
        $throw = false;
        $keyIndex = false;

        if (empty($zoneTranslations)) {
            $throw = true;
        } else {
            $keyIndex = array_search($key, array_column($zoneTranslations, 'key'), true);
            if ($keyIndex === false || !isset($zoneTranslations[$keyIndex]['values'][$locale])) {
                $throw = true;
            }
        }

        if ($throw === true) {
            if (\Pimcore\Tool::isFrontendRequestByAdmin()) {
                return $key;
            }

            throw new \Exception(sprintf(
                'i18n static route translation error: no valid translation key for "%s" in locale "%s" found. please add it to your i18n translation config',
                $key,
                $locale
            ));
        }

        return $zoneTranslations[$keyIndex]['values'][$locale];
    }
}
