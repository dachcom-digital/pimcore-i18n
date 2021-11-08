<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Http\I18nContextResolverInterface;
use I18nBundle\Manager\I18nContextManager;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class I18nExtension extends AbstractExtension
{
    protected RequestStack $requestStack;
    protected I18nContextResolverInterface $i18nContextResolver;
    protected I18nContextManager $i18nContextManager;

    public function __construct(
        RequestStack $requestStack,
        I18nContextResolverInterface $i18nContextResolver,
        I18nContextManager $i18nContextManager
    ) {
        $this->requestStack = $requestStack;
        $this->i18nContextResolver = $i18nContextResolver;
        $this->i18nContextManager = $i18nContextManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_context', [$this, 'getI18nContext']),
            new TwigFunction('i18n_create_context_by_entity', [$this, 'createI18nContextByEntity']),
            new TwigFunction('i18n_create_context_by_static_route', [$this, 'createI18nContextByStaticRoute']),
            new TwigFunction('i18n_create_context_by_symfony_route', [$this, 'createI18nContextBySymfonyRoute']),
        ];
    }

    public function getI18nContext(): ?I18nContextInterface
    {
        return $this->i18nContextResolver->getContext($this->requestStack->getCurrentRequest());
    }

    public function createI18nContextByEntity(ElementInterface $entity, array $routeParameter = [], ?Site $site = null): I18nContextInterface
    {
        $routeItemParameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $entity,
            'context'         => [
                'site' => $site
            ]
        ];

        if ($entity instanceof Document) {
            $routeItemParameters['type'] = RouteItemInterface::DOCUMENT_ROUTE;
        } elseif ($entity instanceof AbstractObject) {
            $routeItemParameters['type'] = RouteItemInterface::STATIC_ROUTE;
        } else {
            throw new \Exception('Cannot build zone for entity "%"', get_class($entity));
        }

        return $this->i18nContextManager->buildContextByParameters($routeItemParameters, true);
    }

    public function createI18nContextByStaticRoute(string $route, array $routeParameter = [], ?Site $site = null): I18nContextInterface
    {
        $routeItemParameters = [
            'type'            => RouteItemInterface::STATIC_ROUTE,
            'routeParameters' => $routeParameter,
            'routeName'       => $route,
            'context'         => [
                'site' => $site
            ]
        ];

        return $this->i18nContextManager->buildContextByParameters($routeItemParameters, true);
    }

    public function createI18nContextBySymfonyRoute(string $route, array $routeParameter = [], ?Site $site = null): I18nContextInterface
    {
        $routeItemParameters = [
            'type'            => RouteItemInterface::SYMFONY_ROUTE,
            'routeParameters' => $routeParameter,
            'routeName'       => $route,
            'context'         => [
                'site' => $site
            ]
        ];

        return $this->i18nContextManager->buildContextByParameters($routeItemParameters, true);
    }
}
