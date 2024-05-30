<?php

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Block;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PimcoreInlineRendererAwareModifier implements RouteItemModifierInterface
{
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
        $this->modify($routeItem, $this->requestStack->getMainRequest());
    }

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void
    {
        $this->modify($routeItem, $request);
    }

    protected function modify(RouteItemInterface $routeItem, Request $request): void
    {
        $document = $this->determinateDocumentByRoute($request);

        $hasSiteContext = $routeItem->getRouteContextBag()->has('site') && $routeItem->getRouteContextBag()->get('site') !== null;
        $hasLocaleParameter = $routeItem->getRouteParametersBag()->has('_locale');

        if (!$document instanceof Document) {
            return;
        }

        if (!$hasSiteContext) {
            $site = Frontend::getSiteForDocument($document);
            if ($site instanceof Site) {
                $routeItem->getRouteContextBag()->set('site', $site);
            }
        }

        if (!$hasLocaleParameter && !empty($document->getProperty('language'))) {
            $routeItem->getRouteParametersBag()->set('_locale', $document->getProperty('language'));
        }
    }

    private function determinateDocumentByRoute(Request $request): ?Document
    {
        if (
            $request->attributes->get('_route') === 'pimcore_admin_document_document_add' &&
            $request->request->get('elementType') === 'document' &&
            !empty($request->request->get('parentId'))
        ) {
            return Document::getById($request->request->get('parentId'));
        }

        if (
            $request->attributes->get('_route') === 'pimcore_admin_document_page_save' &&
            !empty($request->request->get('id'))
        ) {
            return Document::getById($request->request->get('id'));
        }

        return null;
    }

    private function isValidRequest(): bool
    {
        $parentRequest = $this->requestStack->getParentRequest();

        if (!$parentRequest instanceof Request) {
            return false;
        }

        if (!$parentRequest->attributes->has(EditmodeResolver::ATTRIBUTE_EDITMODE) || $parentRequest->attributes->get(EditmodeResolver::ATTRIBUTE_EDITMODE) === false) {
            return false;
        }

        if (!$parentRequest->attributes->has(Block::ATTRIBUTE_IGNORE_EDITMODE_INDICES) || $parentRequest->attributes->get(Block::ATTRIBUTE_IGNORE_EDITMODE_INDICES) === false) {
            return false;
        }

        return true;
    }
}
