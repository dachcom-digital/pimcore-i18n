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

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FrontPageMapperListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(protected DocumentResolver $documentResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -255] //after ElementListener
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($request->attributes->get('_route') === 'fos_js_routing_js') {
            return;
        }

        // use original document resolver to allow using document override!
        $document = $this->documentResolver->getDocument($request);
        if (!$document instanceof Document) {
            return;
        }

        if (!$document instanceof Hardlink\Wrapper\WrapperInterface) {
            return;
        }

        if ($document->getHardLinkSource()->getFullPath() !== $document->getFullPath()) {
            return;
        }

        $mapDocument = $document->getHardLinkSource()->getProperty('front_page_map');
        if (!$mapDocument instanceof Document) {
            return;
        }

        // this comes after I18nStartupListener
        // we just need to flip the document
        $request->attributes->set(Definitions::FRONT_PAGE_MAP, ['id' => $document->getId(), 'key' => $document->getKey()]);
        $this->documentResolver->setDocument($request, $mapDocument);
    }
}
