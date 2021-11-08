<?php

namespace I18nBundle\Http;

use I18nBundle\Context\I18nContextInterface;
use Symfony\Component\HttpFoundation\Request;

interface I18nContextResolverInterface
{
    public function setContext(I18nContextInterface $i18nContext, Request $request);

    public function getContext(Request $request): ?I18nContextInterface;

    public function hasContext(Request $request): bool;
}
