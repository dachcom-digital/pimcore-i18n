<?php

namespace I18nBundle\Routing;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use I18nBundle\LinkGenerator\I18nLinkGeneratorInterface;
use I18nBundle\Manager\I18nContextManager;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Tool\System;
use I18nBundle\Transformer\LinkGeneratorRouteItemTransformer;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class I18nRouter implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    protected RouterInterface $router;
    protected LinkGeneratorRouteItemTransformer $linkGeneratorRouteItemTransformer;
    protected Configuration $configuration;
    protected UrlGeneratorInterface $urlGenerator;
    protected I18nContextManager $i18nContextManager;
    protected ?RequestContext $contextBackup = null;

    public function __construct(
        RouterInterface $router,
        LinkGeneratorRouteItemTransformer $linkGeneratorRouteItemTransformer,
        Configuration $configuration,
        UrlGeneratorInterface $urlGenerator,
        I18nContextManager $i18nContextManager
    ) {
        $this->router = $router;
        $this->linkGeneratorRouteItemTransformer = $linkGeneratorRouteItemTransformer;
        $this->configuration = $configuration;
        $this->urlGenerator = $urlGenerator;
        $this->i18nContextManager = $i18nContextManager;
    }

    /**
     * @inheritdoc
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * @inheritdoc
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * @inheritdoc
     */
    public function matchRequest(Request $request)
    {
        if ($this->router instanceof RequestMatcherInterface) {
            return $this->router->matchRequest($request);
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function match($pathinfo)
    {
        return $this->router->match($pathinfo);
    }

    /**
     * @inheritdoc
     */
    public function warmUp(string $cacheDir)
    {
        if ($this->router instanceof WarmableInterface) {
            return $this->router->warmUp($cacheDir);
        }

        return [];
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (!array_key_exists(Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER, $parameters)) {
            return $this->router->generate($name, $parameters, $referenceType);
        }

        $i18nParameters = $parameters[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER];

        unset($parameters[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER]);

        if (!empty($name)) {
            $i18nParameters['routeName'] = $name;
        }

        $i18nContext = $this->i18nContextManager->buildContextByParameters($i18nParameters, false);

        if (!$i18nContext instanceof I18nContextInterface) {
            return $this->router->generate($name, $parameters, $referenceType);
        }

        if ($i18nContext->getRouteItem()->getType() === RouteItemInterface::STATIC_ROUTE) {
            return $this->generateStaticRoute($i18nContext, $referenceType);
        }

        if ($i18nContext->getRouteItem()->getType() === RouteItemInterface::SYMFONY_ROUTE) {
            return $this->generateSymfonyRoute($i18nContext, $referenceType);
        }

        throw new RouteNotFoundException(sprintf('None of the chained routers were able to generate route: %s', $name));
    }

    protected function generateStaticRoute(I18nContextInterface $i18nContext, int $referenceType): string
    {
        $routeItemEntity = $i18nContext->getRouteItem()->getEntity();
        $routeItem = $i18nContext->getRouteItem();

        if ($routeItemEntity instanceof Concrete) {
            $routeItem = $this->buildLinkGeneratorRouteItem($routeItemEntity, $i18nContext);
        }

        $this->buildRouteContext($i18nContext, $referenceType);

        $path = $this->urlGenerator->generate($routeItem->getRouteName(), $routeItem->getRouteParameters(), $referenceType);

        $this->restoreRouteContext();

        $zone = $i18nContext->getZone();
        $locale = $routeItem->getLocaleFragment();

        if (!$zone instanceof ZoneInterface) {
            return $path;
        }

        $urlMapping = $zone->getLocaleUrlMapping();
        $zoneTranslations = $zone->getTranslations();

        $validLocaleIso = array_search($locale, $urlMapping, true);

        if ($validLocaleIso !== false) {
            $locale = $validLocaleIso;
        }

        $path = preg_replace_callback(
            '/@((?:(?![\/|?]).)*)/',
            function ($matches) use ($locale, $zoneTranslations) {
                return $this->translateStaticRouteKey($matches[1], $locale, $zoneTranslations);
            },
            $path
        );

        $path = $this->parseLocaleUrlMapping($path, $locale, $urlMapping);

        if (str_ends_with($path, '?')) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    protected function generateSymfonyRoute(I18nContextInterface $i18nContext, int $referenceType): string
    {
        if (empty($i18nContext->getRouteItem()->hasLocaleFragment())) {
            return $this->urlGenerator->generate($i18nContext->getRouteItem()->getRouteName(), $i18nContext->getRouteItem()->getRouteParameters(), $referenceType);
        }

        $locale = $i18nContext->getRouteItem()->getLocaleFragment();
        $zone = $i18nContext->getZone();

        if (!$zone instanceof ZoneInterface) {
            return $this->urlGenerator->generate($i18nContext->getRouteItem()->getRouteName(), $i18nContext->getRouteItem()->getRouteParameters(), $referenceType);
        }

        $translationKeys = $i18nContext->getRouteItem()->getRouteAttributesBag()->get('_i18n_translation_keys', []);

        $urlMapping = $zone->getLocaleUrlMapping();
        $zoneTranslations = $zone->getTranslations();
        $routeParametersBag = $i18nContext->getRouteItem()->getRouteParametersBag();

        foreach ($translationKeys as $routeKey => $translationKey) {

            if ($routeParametersBag->has($translationKey)) {
                continue;
            }

            $translationIndex = array_search($translationKey, array_column($zoneTranslations, 'key'), true);

            if ($translationIndex === false) {
                // throw no route found exception?
                continue;
            }

            $translation = $zoneTranslations[$translationIndex]['values'];

            if (!isset($translation[$locale])) {
                // throw no route found exception?
                continue;
            }

            $routeParametersBag->set($routeKey, $translation[$locale]);
        }

        $this->buildRouteContext($i18nContext, $referenceType);

        $path = $this->urlGenerator->generate($i18nContext->getRouteItem()->getRouteName(), $routeParametersBag->all(), $referenceType);

        $this->restoreRouteContext();

        $validLocaleIso = array_search($locale, $urlMapping, true);

        if ($validLocaleIso !== false) {
            $locale = $validLocaleIso;
        }

        return $this->parseLocaleUrlMapping($path, $locale, $urlMapping);
    }

    protected function translateStaticRouteKey(string $key, string $locale, array $zoneTranslations): string
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

    protected function parseLocaleUrlMapping(string $path, string $locale, array $urlMapping): string
    {
        //transform locale style to given url mapping - if existing

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

    protected function buildLinkGeneratorRouteItem(Concrete $routeItemEntity, I18nContextInterface $i18nContext): RouteItemInterface
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

    protected function buildRouteContext(I18nContextInterface $i18nContext, int $referenceType): void
    {
        $allowedKeys = [
            'host',
            'scheme',
            'httpPort',
            'httpsPort'
        ];

        if ($referenceType !== UrlGeneratorInterface::ABSOLUTE_URL) {
            return;
        }

        $this->contextBackup = clone $this->getContext();

        foreach ($allowedKeys as $allowedKey) {
            if (!empty($i18nContext->getRouteItem()->getRouteContextBag()->get($allowedKey))) {
                $setter = sprintf('set%s', ucfirst($allowedKey));
                $this->getContext()->$setter($i18nContext->getRouteItem()->getRouteContextBag()->get($allowedKey));
            }
        }
    }

    protected function restoreRouteContext(): void
    {
        if (!$this->contextBackup instanceof RequestContext) {
            return;
        }

        $this->setContext(clone $this->contextBackup);
        $this->contextBackup = null;
    }

    /**
     * Forwards all unknown methods calls to inner router
     */
    public function __call(string $name, array $arguments): mixed
    {
        return \call_user_func_array([$this->router, $name], $arguments);
    }
}
