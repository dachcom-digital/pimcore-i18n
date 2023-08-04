<?php

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\LinkGenerator\I18nLinkGeneratorInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\Request;

class StaticRouteModifier implements RouteItemModifierInterface
{
    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool
    {
        if ($type !== RouteItemInterface::STATIC_ROUTE) {
            return false;
        }

        return true;
    }

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool
    {
        if ($type !== RouteItemInterface::STATIC_ROUTE) {
            return false;
        }

        return true;
    }

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void
    {
        if (!$routeItem->hasRouteName() && !$routeItem->hasEntity()) {
            throw new \Exception(sprintf('Cannot build static route item. Either route name or entity must be present'));
        }

        if ($routeItem->hasEntity()) {
            $this->assertValidLinkGenerator($routeItem);
        }
    }

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void
    {
        $routeItem->getRouteAttributesBag()->add($request->attributes->all());
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
}
