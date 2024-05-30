<?php

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PimcoreExportDataAwareModifier implements RouteItemModifierInterface
{
    private const EXPORT_AWARE_ROUTES = [
        'pimcore_admin_translation_wordexport',
        'pimcore_admin_translation_xliffexport'
    ];

    public function __construct(protected RequestStack $requestStack)
    {
    }

    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool
    {
        return $this->isValidRequest();
    }

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool
    {
        return $this->isValidRequest();
    }

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void
    {
        if (!$this->requestStack->getMainRequest() instanceof Request) {
            return;
        }

        $this->modify($routeItem, $this->requestStack->getMainRequest());
    }

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void
    {
        $this->modify($routeItem, $request);
    }

    protected function modify(RouteItemInterface $routeItem, Request $request): void
    {
        $hasSiteContext = $routeItem->getRouteContextBag()->has('site') && $routeItem->getRouteContextBag()->get('site') !== null;
        $hasLocaleParameter = $routeItem->getRouteParametersBag()->has('_locale');

        $exportData = $request->request->has('data') ? json_decode($request->request->get('data'), true) : [];

        if (count($exportData) === 0) {
            return;
        }

        if (!array_key_exists('id', $exportData[0]) || !array_key_exists('type', $exportData[0])) {
            return;
        }

        $elementId = $exportData[0]['id'];
        $elementType = $exportData[0]['type'];

        if ($elementType !== 'document') {
            return;
        }

        $element = Document::getById($elementId);
        if (!$element instanceof Document) {
            return;
        }

        if (!$hasSiteContext) {
            $site = Frontend::getSiteForDocument($element);
            if ($site instanceof Site) {
                $routeItem->getRouteContextBag()->set('site', $site);
            }
        }

        if (!$hasLocaleParameter) {
            $routeItem->getRouteParametersBag()->set('_locale', $element->getProperty('language'));
        }
    }

    private function isValidRequest(): bool
    {
        if (!$this->requestStack->getMainRequest() instanceof Request) {
            return false;
        }

        return in_array($this->requestStack->getMainRequest()->attributes->get('_route'), self::EXPORT_AWARE_ROUTES, true);
    }
}
