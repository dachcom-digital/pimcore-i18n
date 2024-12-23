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

namespace I18nBundle\EventListener;

use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document\Hardlink\Wrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CanonicalListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $document = $this->pimcoreDocumentResolver->getDocument($request);
        if (!$document instanceof Wrapper\WrapperInterface) {
            return;
        }

        if (Staticroute::getCurrentRoute()) {
            return;
        }

        //only remove canonical link if hardlink source is the country wrapper
        if ($document->getHardLinkSource()->getPath() === '/') {
            $event->getResponse()->headers->remove('Link');
        }
    }
}
