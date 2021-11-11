<?php

namespace I18nBundle\Modifier;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use I18nBundle\Exception\MissingTranslationRouteSlugException;
use I18nBundle\Exception\VirtualProxyPathException;
use I18nBundle\LinkGenerator\I18nLinkGeneratorInterface;
use I18nBundle\Manager\I18nContextManager;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\ZoneSiteInterface;
use I18nBundle\Tool\System;
use I18nBundle\Transformer\LinkGeneratorRouteItemTransformer;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouteModifier
{
    protected LinkGeneratorRouteItemTransformer $linkGeneratorRouteItemTransformer;
    protected I18nContextManager $i18nContextManager;

    public function __construct(
        LinkGeneratorRouteItemTransformer $linkGeneratorRouteItemTransformer,
        I18nContextManager $i18nContextManager
    ) {
        $this->linkGeneratorRouteItemTransformer = $linkGeneratorRouteItemTransformer;
        $this->i18nContextManager = $i18nContextManager;
    }

    public function generateI18nContext(string $name, $parameters = []): I18nContextInterface
    {
        $i18nParameters = $parameters[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER];
        $i18nType = $i18nParameters['type'] ?? '';
        $i18nParameters = $this->validateRouteParameters($name, $i18nType, $i18nParameters);

        unset($parameters[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER]);

        return $this->i18nContextManager->buildContextByParameters($i18nType, $i18nParameters, false);
    }

    public function modifyStaticRouteFragments(I18nContextInterface $i18nContext, string $originalPath)
    {
        $zone = $i18nContext->getZone();
        $routeItem = $i18nContext->getRouteItem();
        $locale = $routeItem->getLocaleFragment();

        if (!$zone instanceof ZoneInterface) {
            return $originalPath;
        }

        $path = preg_replace_callback(
            '/@((?:(?![\/|?]).)*)/',
            function ($matches) use ($zone, $locale) {
                return $this->translateDynamicRouteKey($zone, $matches[1], $locale);
            },
            $originalPath
        );

        $path = $this->parseLocaleUrlMapping($zone, $path, $locale);

        if (str_ends_with($path, '?')) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    public function modifySymfonyRouteParameterBag(I18nContextInterface $i18nContext): void
    {
        $zone = $i18nContext->getZone();
        $locale = $i18nContext->getRouteItem()->getLocaleFragment();
        $translationKeys = $i18nContext->getRouteItem()->getRouteAttributesBag()->get('_i18n_translation_keys', []);

        foreach ($translationKeys as $routeKey => $translationKey) {

            if ($i18nContext->getRouteItem()->getRouteParametersBag()->has($translationKey)) {
                continue;
            }

            $i18nContext->getRouteItem()->getRouteParametersBag()->set($routeKey, $this->translateDynamicRouteKey($zone, $translationKey, $locale));
        }
    }

    public function parseLocaleUrlMapping(ZoneInterface $zone, string $path, string $locale): string
    {
        //transform locale style to given url mapping - if existing
        $urlMapping = $zone->getLocaleUrlMapping();

        if (!array_key_exists($locale, $urlMapping)) {
            return $path;
        }

        $urlFragments = parse_url($path);
        $pathFragment = $urlFragments['path'] ?? '';
        $fragments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $pathFragment)));

        if ($fragments[0] === $locale) {
            //replace first value in array!
            $fragments[0] = $urlMapping[$locale];
            $addSlash = str_starts_with($pathFragment, DIRECTORY_SEPARATOR);
            $freshPath = System::joinPath($fragments, $addSlash);
            $path = str_replace($pathFragment, $freshPath, $path);
        }

        return $path;
    }

    public function buildLinkGeneratorRouteItem(Concrete $routeItemEntity, I18nContextInterface $i18nContext): RouteItemInterface
    {
        $linkGenerator = $routeItemEntity->getClass()?->getLinkGenerator();
        if (!$linkGenerator instanceof I18nLinkGeneratorInterface) {
            throw new \Exception(
                sprintf(
                    'I18n link generator error: Your link generator "%s" needs to be an instance of %s.',
                    get_class($linkGenerator),
                    I18nLinkGeneratorInterface::class
                )
            );
        }

        $parsedLinkGeneratorRouteItem = $linkGenerator->generateRouteItem(
            $routeItemEntity,
            $this->linkGeneratorRouteItemTransformer->transform(
                $i18nContext->getRouteItem(),
                ['staticRouteName' => $linkGenerator->getStaticRouteName($routeItemEntity)]
            )
        );

        return $this->linkGeneratorRouteItemTransformer->reverseTransform($parsedLinkGeneratorRouteItem);
    }

    public function buildDocumentPath(I18nContextInterface $i18nContext, int $referenceType): string
    {
        $routeItem = $i18nContext->getRouteItem();
        $document = $routeItem->getEntity();
        $contextBag = $routeItem->getRouteContextBag();
        $zoneSite = $i18nContext->getCurrentZoneSite();

        $documentPath = '';
        $documentDebug = '--';

        $prettyUrlSet = false;
        if ($document instanceof Document\Page && !empty($document->getPrettyUrl())) {
            $prettyUrlSet = true;
            $documentPath = $document->getPrettyUrl();
            $documentDebug = sprintf('id %d with pretty url "%s"', $document->getId(), $documentPath);
        } elseif ($document instanceof Document) {
            $documentPath = $document->getRealFullPath();
            $documentDebug = sprintf('id %d with path "%s"', $document->getId(), $documentPath);
        }

        if ($documentPath === '') {
            throw new RouteNotFoundException(
                sprintf('cannot generate document route [%s]', $documentDebug)
            );
        }

        if ($contextBag->has('virtualProxyZoneSite')) {

            if ($prettyUrlSet) {
                throw new VirtualProxyPathException(sprintf('Virtual path generation for document with pretty URLs ("%s") cannot be processed', $documentPath));
            }

            /** @var ZoneSiteInterface $zoneSite */
            $zoneSite = $contextBag->get('virtualProxyZoneSite');
            $relativePath = preg_replace('/^' . preg_quote($i18nContext->getCurrentZoneSite()->getFullPath(), '/') . '/', '', $documentPath);
            $documentPath = System::joinPath([$zoneSite->getFullPath(), $relativePath]);

            if (Document\Service::pathExists($documentPath)) {
                throw new VirtualProxyPathException(sprintf(
                    'Virtual proxy path  "%s" for document "%s" should not exists but was found in zone path %s',
                    $documentPath,
                    $document->getRealPath(),
                    $zoneSite->getFullPath()
                ));
            }
        }

        if (!$prettyUrlSet) {
            // strip site prefix from path
            $documentPath = substr($documentPath, strlen($zoneSite->getRootPath()));
        }

        if ($referenceType !== UrlGeneratorInterface::ABSOLUTE_URL) {
            return $documentPath;
        }

        $scheme = $zoneSite->getSiteRequestContext()->getScheme();
        $host = $zoneSite->getSiteRequestContext()->getHost();
        $httpPort = $zoneSite->getSiteRequestContext()->getHttpPort();
        $httpsPort = $zoneSite->getSiteRequestContext()->getHttpsPort();

        $port = '';
        if ($scheme === 'http' && $httpPort !== 80) {
            $port = ':' . $httpPort;
        } elseif ($scheme === 'https' && $httpsPort !== 443) {
            $port = ':' . $httpsPort;
        }

        if (!empty($documentPath)) {
            $documentPath = '/' . ltrim($documentPath, '/');
        }

        return sprintf('%s://%s%s%s', $scheme, $host, $port, $documentPath);
    }

    protected function translateDynamicRouteKey(ZoneInterface $zone, string $key, string $locale): string
    {
        $zoneTranslations = $zone->getTranslations();
        $zoneIdentifier = $zone->getId() ?? 0;

        $exceptionMessage = null;
        $routeKey = null;

        if (empty($zoneTranslations)) {
            $exceptionMessage = sprintf('No translations for zone [Id: %d] found', $zoneIdentifier);
        } else {

            $translationIndex = array_search($key, array_column($zoneTranslations, 'key'), true);

            if ($translationIndex === false) {
                $exceptionMessage = sprintf('No translation key for "%s" in zone [Id: %d] found', $key, $zoneIdentifier);
            }

            $translation = $zoneTranslations[$translationIndex]['values'];

            if (!isset($translation[$locale])) {
                $exceptionMessage = sprintf('No translation key for "%s" with locale "%s" in zone [Id: %d] found', $key, $locale, $zoneIdentifier);
            }

            $routeKey = $translation[$locale];
        }

        if ($routeKey !== null) {
            return $routeKey;
        }

        if (\Pimcore\Tool::isFrontendRequestByAdmin()) {
            return $key;
        }

        throw new MissingTranslationRouteSlugException($exceptionMessage);
    }

    protected function validateRouteParameters(string $name, string $routeType, array $parameters): array
    {
        unset($parameters['type']);

        if (!empty($name)) {
            $parameters['routeName'] = $name;
        }

        if ($name === '') {
            $entity = $parameters['entity'] ?? null;
            if ($routeType === RouteItemInterface::DOCUMENT_ROUTE && !$entity instanceof Document) {
                throw new \Exception('I18n document route without route name requires a valid Document in "entity" parameter');
            } elseif ($routeType === RouteItemInterface::STATIC_ROUTE && !$entity instanceof DataObject) {
                throw new \Exception('I18n static route without route name requires a valid DataObject in "entity" parameter');
            }
        }

        return $parameters;
    }
}
