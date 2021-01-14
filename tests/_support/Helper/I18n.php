<?php

namespace DachcomBundle\Test\Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\Util\Debug;
use Dachcom\Codeception\Helper\PimcoreBackend;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;

class I18n extends Module implements DependsOnModule
{
    /**
     * @var PimcoreBackend
     */
    protected $pimcoreBackend;

    /**
     * @return array|mixed
     */
    public function _depends()
    {
        return [
            'Dachcom\Codeception\Helper\PimcoreBackend' => 'Members needs the PimcoreBackend module to work.'
        ];
    }

    /**
     * @param PimcoreBackend $connection
     */
    public function _inject(PimcoreBackend $connection)
    {
        $this->pimcoreBackend = $connection;
    }

    /**
     * Actor Function to create a FrontPage mapped Document
     *
     * @param Hardlink $hardlinkDocument
     *
     * @return Page
     */
    public function haveAFrontPageMappedDocument(Hardlink $hardlinkDocument)
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
