<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Manager\ZoneManager;
use I18nBundle\Tool\System;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class FrontendPathStaticRouteListener implements EventSubscriberInterface
{
    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * FrontendPathStaticRouteListener constructor.
     *
     * @param ZoneManager $zoneManager
     */
    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FrontendEvents::STATICROUTE_PATH => ['onFrontendPathStaticRouteRequest']
        ];
    }

    /**
     * @param GenericEvent $event
     */
    public function onFrontendPathStaticRouteRequest(GenericEvent $event)
    {
        $frontEndPath = $event->getArgument('frontendPath');
        $params = $event->getArgument('params');

        if (!isset($params['_locale'])) {
            return;
        }

        $locale = $params['_locale'];
        $urlMapping = $this->zoneManager->getCurrentZoneInfo('locale_url_mapping');
        $validLocaleIso = array_search($params['_locale'], $urlMapping);
        if($validLocaleIso !== FALSE) {
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
     * @param      $key
     * @param      $locale
     *
     * @return mixed
     * @throws \Exception
     */
    private function translateKey($key, $locale)
    {
        $translationConfig = $this->zoneManager->getCurrentZoneInfo('translations');
        $throw = FALSE;
        $keyIndex = FALSE;

        if (empty($translationConfig)) {
            $throw = TRUE;
        } else {
            $keyIndex = array_search($key, array_column($translationConfig, 'key'));
            if ($keyIndex === FALSE || !isset($translationConfig[$keyIndex]['values'][$locale])) {
                $throw = TRUE;
            }
        }

        if($throw === TRUE) {
            throw new \Exception(sprintf(
                'i18n static route translation error: no valid translation key for "%s" in locale "%s" found. please add it to your i18n translation config',
                $key, $locale));
        }

        return $translationConfig[$keyIndex]['values'][$locale];
    }
}