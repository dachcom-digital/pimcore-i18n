<?php

namespace DachcomBundle\Test\Support\Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\Util\Debug;
use Dachcom\Codeception\Support\Helper\PimcoreBackend;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;

class I18n extends Module implements DependsOnModule
{
    protected PimcoreBackend $pimcoreBackend;

    public function _depends(): array
    {
        return [
            PimcoreBackend::class => 'I18n needs the PimcoreBackend module to work.'
        ];
    }

    public function _inject(PimcoreBackend $pimcoreBackend): void
    {
        $this->pimcoreBackend = $pimcoreBackend;
    }

    /**
     * Actor Function to create a FrontPage mapped Document
     */
    public function haveAFrontPageMappedDocument(Hardlink $hardlinkDocument): Page
    {
        $document = $this->pimcoreBackend->generatePageDocument('frontpage-mapped-' . $hardlinkDocument->getKey());
        $document->setParentId($hardlinkDocument->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[I18N ERROR] error while document page for frontpage mapping. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Page::class, Page::getById($document->getId()));

        $hardlinkDocument->setProperty('front_page_map', 'document', $document->getId(), false, false);

        try {
            $hardlinkDocument->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[I18N ERROR] error while document hardlink for frontpage mapping. message was: ' . $e->getMessage()));
        }

        return $document;
    }
}
