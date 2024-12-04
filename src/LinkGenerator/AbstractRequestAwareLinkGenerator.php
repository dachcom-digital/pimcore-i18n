<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\LinkGenerator;

use I18nBundle\Builder\RouteParameterBuilder;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractRequestAwareLinkGenerator
{
    protected UrlGeneratorInterface $urlGenerator;
    protected RequestStack $requestStack;
    protected RequestHelper $requestHelper;
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

    #[Required]
    public function setRequestHelper(RequestHelper $requestHelper): void
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @throws \Throwable
     */
    public function generate(object $object, array $params = []): string
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

            if ($this->requestHelper->isFrontendRequest($mainRequest) === false || $this->requestHelper->isFrontendRequestByAdmin() === true) {
                return sprintf('/i18n-suppressed-link-%d', $object->getId());
            }

            throw $e;
        }
    }
}
