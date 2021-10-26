<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Exception\ContextNotDefinedException;
use I18nBundle\Http\ZoneResolverInterface;
use I18nBundle\Manager\ZoneManager;
use I18nBundle\Model\I18nZoneInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class I18nExtension extends AbstractExtension
{
    protected RequestStack $requestStack;
    protected ZoneResolverInterface $zoneResolver;
    protected ZoneManager $zoneManager;

    public function __construct(
        RequestStack $requestStack,
        ZoneResolverInterface $zoneResolver,
        ZoneManager $zoneManager
    ) {
        $this->requestStack = $requestStack;
        $this->zoneResolver = $zoneResolver;
        $this->zoneManager = $zoneManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_zone', [$this, 'getI18nZone']),
            new TwigFunction('i18n_create_zone_by_entity', [$this, 'createI18nZoneByEntity'], ['needs_context' => true]),
        ];
    }

    public function getI18nZone(): ?I18nZoneInterface
    {
        return $this->zoneResolver->getZone($this->requestStack->getCurrentRequest());
    }

    public function createI18nZoneByEntity(array $context, ElementInterface $entity, string $locale, array $routeParams = [], ?string $mappedDomain = null)
    {
        $baseDocument = $entity instanceof AbstractObject ? $context['document'] : $entity;

        if (!$baseDocument instanceof PageSnippet) {
            throw new \Exception('Entity context requires a valid document');
        }

        return $this->zoneManager->buildZoneByEntity($entity, $locale, $routeParams, $mappedDomain);
    }
}
