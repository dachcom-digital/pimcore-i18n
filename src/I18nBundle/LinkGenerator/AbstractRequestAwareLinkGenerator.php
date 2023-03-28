<?php

namespace I18nBundle\LinkGenerator;

use I18nBundle\Builder\RouteParameterBuilder;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractRequestAwareLinkGenerator
{
    protected UrlGeneratorInterface $urlGenerator;
    protected RequestStack $requestStack;

    protected int $urlReferenceType = UrlGeneratorInterface::ABSOLUTE_PATH;

    #[Required]
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    #[Required]
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @throws \Throwable
     */
    public function generate(Concrete $object, array $params = []): string
    {
        // Use this class, if you want to use object links in wysiwyg or link elements
        // Be aware, that this class relies on the main request!

        $routeParams = [];
        $routeContext = [];

        $mainRequest = $this->requestStack->getMainRequest();
        if (!$mainRequest instanceof Request) {
            return '#';
        }

        $routeParams['_locale'] = $mainRequest->getLocale();
        if ($mainRequest->attributes->has(SiteResolver::ATTRIBUTE_SITE)) {
            $routeContext['site'] = $mainRequest->attributes->get(SiteResolver::ATTRIBUTE_SITE);
        }

        try {
            return $this->urlGenerator->generate(
                '',
                RouteParameterBuilder::buildForEntity($object, $routeParams, $routeContext),
                $this->urlReferenceType
            );
        } catch (\Throwable $e) {

            // suppress exception, if we're in admin mode
            // there is no (possible) site context in some conditions (like link generation in wysiwyg)

            if (
                $mainRequest->attributes->has(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST) &&
                $mainRequest->attributes->get(RequestHelper::ATTRIBUTE_FRONTEND_REQUEST) === false
            ) {
                return sprintf('/i18n-suppressed-link-%d', $object->getId());
            }

            throw $e;
        }
    }
}

