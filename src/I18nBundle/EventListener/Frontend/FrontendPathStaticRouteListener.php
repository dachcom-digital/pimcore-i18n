<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Manager\ZoneManager;
use I18nBundle\Tool\System;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class FrontendPathStaticRouteListener implements EventSubscriberInterface
{
    protected ZoneManager $zoneManager;

    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FrontendEvents::STATICROUTE_PATH => ['onFrontendPathStaticRouteRequest']
        ];
    }

    public function onFrontendPathStaticRouteRequest(GenericEvent $event): void
    {
        $frontEndPath = $event->getArgument('frontendPath');
        $params = $event->getArgument('params');

        if (!isset($params['_locale'])) {
            return;
        }

        $locale = $params['_locale'];
        $urlMapping = $this->zoneManager->getCurrentZoneInfo('locale_url_mapping');
        $validLocaleIso = array_search($params['_locale'], $urlMapping, true);
        if ($validLocaleIso !== false) {
            $locale = $validLocaleIso;
        }

        $frontEndPath = preg_replace_callback(
            '/@((?:(?![\/|?]).)*)/',
            function ($matches) use ($locale) {
                return $this->translateKey($matches[1], $locale);
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
    private function translateKey(string $key, string $locale): string
    {
        $translationConfig = $this->zoneManager->getCurrentZoneInfo('translations');
        $throw = false;
        $keyIndex = false;

        if (empty($translationConfig)) {
            $throw = true;
        } else {
            $keyIndex = array_search($key, array_column($translationConfig, 'key'));
            if ($keyIndex === false || !isset($translationConfig[$keyIndex]['values'][$locale])) {
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

        return $translationConfig[$keyIndex]['values'][$locale];
    }
}
