<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Http\I18nContextResolverInterface;
use I18nBundle\Manager\I18nContextManager;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Builder\RouteParameterBuilder;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class I18nExtension extends AbstractExtension
{
    protected RequestStack $requestStack;
    protected UrlGeneratorInterface $urlGenerator;
    protected I18nContextResolverInterface $i18nContextResolver;
    protected I18nContextManager $i18nContextManager;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        I18nContextResolverInterface $i18nContextResolver,
        I18nContextManager $i18nContextManager
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->i18nContextResolver = $i18nContextResolver;
        $this->i18nContextManager = $i18nContextManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_current_context', [$this, 'getI18nContext']),
            new TwigFunction('i18n_create_context_by_entity', [$this, 'createI18nContextByEntity']),
            new TwigFunction('i18n_create_context_by_static_route', [$this, 'createI18nContextByStaticRoute']),
            new TwigFunction('i18n_create_context_by_symfony_route', [$this, 'createI18nContextBySymfonyRoute']),
            new TwigFunction('i18n_entity_route', [$this, 'createI18nEntityRoute']),
            new TwigFunction('i18n_static_route', [$this, 'createI18nStaticRoute']),
            new TwigFunction('i18n_symfony_route', [$this, 'createI18nSymfonyRoute']),
        ];
    }

    public function getI18nContext(): ?I18nContextInterface
    {
        return $this->i18nContextResolver->getContext($this->requestStack->getCurrentRequest());
    }

    public function createI18nContextByEntity(ElementInterface $entity, array $routeParameter = [], ?Site $site = null): ?I18nContextInterface
    {
        $routeItemParameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $entity,
            'context'         => [
                'site' => $site
            ]
        ];

        if ($entity instanceof Document) {
            $type = RouteItemInterface::DOCUMENT_ROUTE;
        } elseif ($entity instanceof AbstractObject) {
            $type = RouteItemInterface::STATIC_ROUTE;
        } else {
            throw new \Exception('Cannot build zone for entity "%"', get_class($entity));
        }

        return $this->i18nContextManager->buildContextByParameters($type, $routeItemParameters, true);
    }

    public function createI18nContextByStaticRoute(string $route, array $routeParameter = [], ?Site $site = null): ?I18nContextInterface
    {
        $routeItemParameters = [
            'routeParameters' => $routeParameter,
            'routeName'       => $route,
            'context'         => [
                'site' => $site
            ]
        ];

        return $this->i18nContextManager->buildContextByParameters(RouteItemInterface::STATIC_ROUTE, $routeItemParameters, true);
    }

    public function createI18nContextBySymfonyRoute(string $route, array $routeParameter = [], ?Site $site = null): ?I18nContextInterface
    {
        $routeItemParameters = [
            'routeParameters' => $routeParameter,
            'routeName'       => $route,
            'context'         => [
                'site' => $site
            ]
        ];

        return $this->i18nContextManager->buildContextByParameters(RouteItemInterface::SYMFONY_ROUTE, $routeItemParameters, true);
    }

    public function createI18nEntityRoute(ElementInterface $entity, array $routeParameter = [], bool $absoluteUrl = false): string
    {
        $referenceType = $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        $routeItemParameters = RouteParameterBuilder::buildForEntityWithRequest(
            $entity,
            $routeParameter,
            $this->requestStack->getCurrentRequest(),
            $this->getRequestContext()
        );

        return $this->urlGenerator->generate('', $routeItemParameters, $referenceType);
    }

    public function createI18nStaticRoute(string $route, array $routeParameter = [], bool $absoluteUrl = false): string
    {
        $referenceType = $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        $routeItemParameters = RouteParameterBuilder::buildForStaticRouteWithRequest(
            $routeParameter,
            $this->requestStack->getCurrentRequest(),
            $this->getRequestContext()
        );

        return $this->urlGenerator->generate($route, $routeItemParameters, $referenceType);
    }

    public function createI18nSymfonyRoute(string $route, array $routeParameter = [], bool $absoluteUrl = false): string
    {
        $referenceType = $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        $routeItemParameters = RouteParameterBuilder::buildForSymfonyRouteWithRequest(
            $routeParameter,
            $this->requestStack->getCurrentRequest(),
            $this->getRequestContext()
        );

        return $this->urlGenerator->generate($route, $routeItemParameters, $referenceType);
    }

    private function getRequestContext(): array
    {
        $mainRequest = $this->requestStack->getMainRequest();
        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof Request && $request->attributes->has(SiteResolver::ATTRIBUTE_SITE)) {
            return ['site' => $request->attributes->get(SiteResolver::ATTRIBUTE_SITE)];
        }

        if ($mainRequest instanceof Request && $mainRequest->attributes->has(SiteResolver::ATTRIBUTE_SITE)) {
            return ['site' => $mainRequest->attributes->get(SiteResolver::ATTRIBUTE_SITE)];
        }

        return [];
    }
}
