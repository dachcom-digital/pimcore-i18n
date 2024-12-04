<?php

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\Definitions;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;

class SymfonyRouteModifier implements RouteItemModifierInterface
{
    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool
    {
        if ($type !== RouteItemInterface::SYMFONY_ROUTE) {
            return false;
        }

        if ($routeItem->getRouteAttributesBag()->has(Definitions::ATTRIBUTE_I18N_ROUTE_TRANSLATION_KEYS_VALIDATED)) {
            return false;
        }

        return true;
    }

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool
    {
        if ($type !== RouteItemInterface::SYMFONY_ROUTE) {
            return false;
        }

        return true;
    }

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void
    {
        $this->assertValidSymfonyRoute($routeItem, $context);

        $routeItem->getRouteAttributesBag()->set(Definitions::ATTRIBUTE_I18N_ROUTE_TRANSLATION_KEYS_VALIDATED, true);
    }

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void
    {
        $routeItem->getRouteAttributesBag()->add($request->attributes->all());

        $routeParameters = $request->attributes->get('_route_params', []);
        if (isset($routeParameters['_locale']) && !$routeItem->getRouteParametersBag()->has('_locale')) {
            $routeItem->getRouteParametersBag()->set('_locale', $request->getLocale());
        }
    }

    protected function assertValidSymfonyRoute(RouteItemInterface $routeItem, array $context): void
    {
        $router = $context['router'];

        if ($router === null) {
            throw new \Exception('Symfony RouteItem error: Framework router not found. cannot assert symfony route');
        }

        $routeName = $routeItem->getRouteName();
        $locale = $routeItem->getLocaleFragment();

        $generator = $router->getGenerator();

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
