<?php

namespace I18nBundle\Routing;

use I18nBundle\Modifier\RouteModifier;
use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
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
    protected RouteModifier $routeModifier;
    protected UrlGeneratorInterface $urlGenerator;
    protected ?RequestContext $contextBackup = null;

    public function __construct(
        RouterInterface $router,
        RouteModifier $routeModifier,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->router = $router;
        $this->routeModifier = $routeModifier;
        $this->urlGenerator = $urlGenerator;
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

        $i18nContext = $this->routeModifier->generateI18nContext($name, $parameters);

        unset($parameters[Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER]);

        if (!$i18nContext instanceof I18nContextInterface) {
            return $this->router->generate($name, $parameters, $referenceType);
        }

        if ($i18nContext->getRouteItem()->getType() === RouteItemInterface::STATIC_ROUTE) {
            return $this->generateStaticRoute($i18nContext, $referenceType);
        }

        if ($i18nContext->getRouteItem()->getType() === RouteItemInterface::SYMFONY_ROUTE) {
            return $this->generateSymfonyRoute($i18nContext, $referenceType);
        }

        if ($i18nContext->getRouteItem()->getType() === RouteItemInterface::DOCUMENT_ROUTE) {
            return $this->generateDocumentRoute($i18nContext, $referenceType);
        }

        throw new RouteNotFoundException(sprintf('None of the chained routers were able to generate i18n route: %s', $name));
    }

    protected function generateStaticRoute(I18nContextInterface $i18nContext, int $referenceType): string
    {
        $routeItemEntity = $i18nContext->getRouteItem()->getEntity();
        $routeItem = $i18nContext->getRouteItem();

        if ($routeItemEntity instanceof Concrete) {
            $routeItem = $this->routeModifier->buildLinkGeneratorRouteItem($routeItemEntity, $i18nContext);
        }

        $path = $this->generateContextAwarePath($i18nContext, $routeItem, $referenceType);

        return $this->routeModifier->modifyStaticRouteFragments($i18nContext, $path);
    }

    protected function generateSymfonyRoute(I18nContextInterface $i18nContext, int $referenceType): string
    {
        $locale = $i18nContext->getRouteItem()->getLocaleFragment();
        $zone = $i18nContext->getZone();

        if (!$zone instanceof ZoneInterface) {
            return $this->urlGenerator->generate($i18nContext->getRouteItem()->getRouteName(), $i18nContext->getRouteItem()->getRouteParameters(), $referenceType);
        }

        $this->routeModifier->modifySymfonyRouteParameterBag($i18nContext);

        $path = $this->generateContextAwarePath($i18nContext, $i18nContext->getRouteItem(), $referenceType);

        return $this->routeModifier->parseLocaleUrlMapping($zone, $path, $locale);
    }

    protected function generateDocumentRoute(I18nContextInterface $i18nContext, int $referenceType): string
    {
        return $this->routeModifier->buildDocumentPath($i18nContext, $referenceType);
    }

    protected function generateContextAwarePath(I18nContextInterface $i18nContext, RouteItemInterface $routeItem, int $referenceType): string
    {
        $this->buildRouteContext($i18nContext, $referenceType);

        $path = $this->urlGenerator->generate($routeItem->getRouteName(), $routeItem->getRouteParameters(), $referenceType);

        $this->restoreRouteContext();

        return $path;
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

        $currentSite = $i18nContext->getCurrentZoneSite();
        foreach ($allowedKeys as $allowedKey) {
            $contextGetter = sprintf('get%s', ucfirst($allowedKey));
            $contextValue = $currentSite->getSiteRequestContext()->$contextGetter($allowedKey);
            if (!empty($contextValue)) {
                $setter = sprintf('set%s', ucfirst($allowedKey));
                $this->getContext()->$setter($contextValue);
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
