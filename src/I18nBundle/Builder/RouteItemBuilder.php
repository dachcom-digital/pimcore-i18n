<?php

namespace I18nBundle\Builder;

use I18nBundle\Definitions;
use I18nBundle\Exception\RouteItemException;
use I18nBundle\Factory\RouteItemFactory;
use I18nBundle\LinkGenerator\I18nLinkGeneratorInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Tool\Frontend;
use Symfony\Bundle\FrameworkBundle\Routing\Router as FrameworkRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;

class RouteItemBuilder
{
    protected ?FrameworkRouter $frameworkRouter = null;
    protected RequestHelper $requestHelper;
    protected SiteResolver $siteResolver;
    protected EditmodeResolver $editModeResolver;
    protected RouteItemFactory $routeItemFactory;

    public function __construct(
        RequestHelper $requestHelper,
        SiteResolver $siteResolver,
        EditmodeResolver $editModeResolver,
        RouteItemFactory $routeItemFactory
    ) {
        $this->requestHelper = $requestHelper;
        $this->siteResolver = $siteResolver;
        $this->editModeResolver = $editModeResolver;
        $this->routeItemFactory = $routeItemFactory;
    }

    public function setFrameworkRouter(FrameworkRouter $router): void
    {
        $this->frameworkRouter = $router;
    }

    public function buildRouteItemByParameters(string $type, array $i18nRouteParameters): RouteItemInterface
    {
        $routeItem = $this->routeItemFactory->createFromArray($type, true, $i18nRouteParameters);

        if ($routeItem->getType() === RouteItemInterface::STATIC_ROUTE) {
            $this->assertStaticRouteItem($routeItem);
        } elseif ($routeItem->getType() === RouteItemInterface::SYMFONY_ROUTE) {
            $this->assertSymfonyRouteItem($routeItem);
        } elseif ($routeItem->getType() === RouteItemInterface::DOCUMENT_ROUTE) {
            $this->assertDocumentRouteItem($routeItem);
        }

        if (!$routeItem->hasLocaleFragment()) {
            throw new RouteItemException(
                sprintf(
                    'Cannot build route item for type "%s" because locale fragment is missing',
                    $routeItem->getType()
                )
            );
        }

        return $routeItem;
    }

    /**
     * @throws RouteItemException
     */
    public function buildRouteItemByRequest(Request $baseRequest, ?Document $baseDocument): ?RouteItemInterface
    {
        $site = null;
        $editMode = $this->editModeResolver->isEditmode($baseRequest);
        $isFrontendRequestByAdmin = $this->requestHelper->isFrontendRequestByAdmin($baseRequest);

        if ($editMode === false && $isFrontendRequestByAdmin === false) {
            if ($this->siteResolver->isSiteRequest($baseRequest)) {
                $site = $this->siteResolver->getSite();
            }
        } else {
            // in back end we don't have any site request, we need to fetch it via document
            $site = \Pimcore\Tool\Frontend::getSiteForDocument($baseDocument);
        }

        $pimcoreRequestSource = $baseRequest->attributes->get('pimcore_request_source');
        $routeParameters = $baseRequest->attributes->get('_route_params', []);
        $currentRouteName = $baseRequest->attributes->get('_route');

        $routeItem = null;
        if ($pimcoreRequestSource === 'staticroute') {
            $routeItem = $this->routeItemFactory->create(RouteItemInterface::STATIC_ROUTE, false);
            $routeItem->getRouteAttributesBag()->add($baseRequest->attributes->all());
        } elseif (str_starts_with($currentRouteName, 'document_')) {
            $routeItem = $this->routeItemFactory->create(RouteItemInterface::DOCUMENT_ROUTE, false);
            $routeItem->setEntity($baseDocument);
            $routeItem->getRouteParametersBag()->set('_locale', $baseDocument->getProperty('language'));
        } elseif ($baseRequest->attributes->has(Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER)) {
            $routeItem = $this->routeItemFactory->create(RouteItemInterface::SYMFONY_ROUTE, false);
            $routeItem->getRouteAttributesBag()->add($baseRequest->attributes->all());
        }

        if ($routeItem === null) {
            return null;
        }

        $routeItem->setRouteName($currentRouteName);
        $routeItem->getRouteContextBag()->set('site', $site);

        if (isset($routeParameters['_locale']) && !$routeItem->getRouteParametersBag()->has('_locale')) {
            $routeItem->getRouteParametersBag()->set('_locale', $baseRequest->getLocale());
        }

        if (!$routeItem->hasLocaleFragment()) {
            throw new RouteItemException(
                sprintf(
                    'Cannot build route item for type "%s" because locale fragment is missing',
                    $routeItem->getType()
                )
            );
        }

        return $routeItem;
    }

    protected function assertStaticRouteItem(RouteItemInterface $routeItem): void
    {
        if (!$routeItem->hasRouteName() && !$routeItem->hasEntity()) {
            throw new \Exception(sprintf('Cannot build static route item. Either route name or entity must be present'));
        }

        if ($routeItem->hasEntity()) {
            $this->assertValidLinkGenerator($routeItem);
        }
    }

    protected function assertSymfonyRouteItem(RouteItemInterface $routeItem): void
    {
        if ($routeItem->getRouteAttributesBag()->has(Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER)) {
            return;
        }

        $this->assertValidSymfonyRoute($routeItem);
    }

    protected function assertDocumentRouteItem(RouteItemInterface $routeItem): void
    {
        // this is for DX only
        if ($routeItem->getRouteContextBag()->get('site') !== null) {
            throw new \Exception('Forcing a site context if requesting a zone for a document is forbidden (Site can be resolved by given document)');
        }

        /** @var Document $document */
        $document = $routeItem->getEntity();

        $routeItem->getRouteParametersBag()->set('_locale', $document->getProperty('language'));
        $routeItem->getRouteContextBag()->set('site', Frontend::getSiteForDocument($document));
    }

    protected function assertValidLinkGenerator(RouteItemInterface $routeItem): void
    {
        $entity = $routeItem->getEntity();

        if (!$entity instanceof Concrete) {
            throw new \Exception(sprintf('I18n object zone generation error: Entity needs to be an instance of "%s", "%s" given.', Concrete::class, get_class($entity)));
        }

        $linkGenerator = $entity->getClass()?->getLinkGenerator();
        if (!$linkGenerator instanceof LinkGeneratorInterface) {
            throw new \Exception(
                sprintf(
                    'I18n object zone generation error: No link generator for entity "%s" found (If you have declared your link generator as service, make sure it is public)',
                    get_class($entity)
                )
            );
        }

        if (!$linkGenerator instanceof I18nLinkGeneratorInterface) {
            throw new \Exception(
                sprintf(
                    'I18n object zone generation error: Your link generator "%s" needs to be an instance of %s.',
                    get_class($linkGenerator),
                    I18nLinkGeneratorInterface::class
                )
            );
        }

        $routeItem->setRouteName($linkGenerator->getStaticRouteName($entity));
    }

    protected function assertValidSymfonyRoute(RouteItemInterface $routeItem): void
    {
        if ($this->frameworkRouter === null) {
            throw new \Exception('Symfony RouteItem error: Framework router not found. cannot assert symfony route');
        }

        $routeName = $routeItem->getRouteName();
        $locale = $routeItem->getLocaleFragment();

        /** @var CompiledUrlGenerator $generator */
        $generator = $this->frameworkRouter->getGenerator();

        if (!$generator instanceof CompiledUrlGenerator) {
            throw new \Exception(
                sprintf('Symfony RouteItem error: Url generator needs to be instance of "%s", "%s" given.',
                    CompiledUrlGenerator::class,
                    get_class($generator)
                )
            );
        }

        /**
         * Oh lawd. This is terrible.
         * Can we do that better instead of stealing private properties like this?
         */
        $compiledRoutes = \Closure::bind(static function & (CompiledUrlGenerator $generator) {
            return $generator->compiledRoutes;
        }, null, $generator)($generator);

        $symfonyRoute = null;
        if (isset($compiledRoutes[$routeName])) {
            $symfonyRoute = $compiledRoutes[$routeName];
        }

        if ($symfonyRoute === null && !empty($locale)) {
            $localizedRouteName = sprintf('%s.%s', $routeName, $locale);
            if (isset($compiledRoutes[$localizedRouteName])) {
                $symfonyRoute = $compiledRoutes[$localizedRouteName];
            }
        }

        if ($symfonyRoute === null) {
            throw new \Exception(sprintf('symfony route "%s" not found', $routeName));
        }

        $defaults = $symfonyRoute[1];

        if (!isset($defaults[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER])) {
            throw new \Exception(sprintf('"%s" symfony route is not configured as i18n route. please add defaults._i18n to your route configuration.', $routeName));
        }

        $i18nDefaults = $defaults[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER];

        if (!is_array($i18nDefaults)) {
            return;
        }

        if (isset($i18nDefaults['translation_keys'])) {
            $routeItem->getRouteAttributesBag()->set('_i18n_translation_keys', $i18nDefaults['translation_keys']);
        }
    }
}
