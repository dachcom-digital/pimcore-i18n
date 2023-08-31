<?php

namespace I18nBundle\Http;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Definitions;
use Symfony\Component\HttpFoundation\Request;

class I18nContextResolver implements I18nContextResolverInterface
{
    public function setContext(I18nContextInterface $i18nContext, Request $request): void
    {
        if ($this->hasContext($request)) {
            throw new \Exception('I18n context already has been resolved');
        }

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_ROUTE_ITEM, $i18nContext);
    }

    public function getContext(Request $request): ?I18nContextInterface
    {
        return $request->attributes->get(Definitions::ATTRIBUTE_I18N_ROUTE_ITEM);
    }

    public function hasContext(Request $request): bool
    {
        $i18nContext = $this->getContext($request);

        return $i18nContext instanceof I18nContextInterface;
    }
}
