<?php

namespace DachcomBundle\Test\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use DachcomBundle\Test\Util\FileGeneratorHelper;
use DachcomBundle\Test\Util\I18nHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Tests\Util\TestHelper;
use Symfony\Component\DependencyInjection\Container;

class PimcoreBackend extends Module
{
    /**
     * @param TestInterface $test
     */
    public function _before(TestInterface $test)
    {
        FileGeneratorHelper::preparePaths();
        parent::_before($test);
    }

    /**
     * @param TestInterface $test
     */
    public function _after(TestInterface $test)
    {
        I18nHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

        parent::_after($test);
    }

    /**
     * Actor Function to create a Page Document
     *
     * @param string $documentKey
     * @param string $locale
     *
     * @return Page
     */
    public function haveAPageDocument(
        $documentKey = 'test-document',
        $locale = null
    ) {
        $document = $this->generatePageDocument($documentKey, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while saving document page. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Page::class, Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Child Page Document
     *
     * @param Document $parent
     * @param string   $documentKey
     * @param string   $locale
     *
     * @return Page
     */
    public function haveASubPageDocument(
        Document $parent,
        $documentKey = 'test-sub-document',
        $locale = null
    ) {
        $document = $this->generatePageDocument($documentKey, $locale);
        $document->setParentId($parent->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while saving child document page. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Page::class, Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Hardlink
     *
     * @param Page   $source
     * @param string $hardlinkKey
     * @param string $locale
     *
     * @return Hardlink
     */
    public function haveAHardLink(
        Page $source,
        $hardlinkKey = 'test-document',
        $locale = null
    ) {
        $hardlink = $this->generateHardlink($source, $hardlinkKey, $locale);

        try {
            $hardlink->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while saving hardlink. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Hardlink::class, Hardlink::getById($hardlink->getId()));

        return $hardlink;
    }

    /**
     * Actor Function to create a child Hardlink
     *
     * @param Document $parent
     * @param Page     $source
     * @param string   $hardlinkKey
     * @param string   $locale
     *
     * @return Hardlink
     */
    public function haveASubHardLink(
        Document $parent,
        Page $source,
        $hardlinkKey = 'test-document',
        $locale = null
    ) {
        $hardlink = $this->generateHardlink($source, $hardlinkKey, $locale);
        $hardlink->setParent($parent);

        try {
            $hardlink->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while saving hardlink. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Hardlink::class, Hardlink::getById($hardlink->getId()));

        return $hardlink;
    }

    /**
     * Actor Function to create a Site Document
     *
     * @param string $siteKey
     * @param null   $locale
     *
     * @return Site
     */
    public function haveASite($siteKey, $locale = null)
    {
        $site = $this->generateSiteDocument($siteKey, $locale);

        try {
            $site->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while saving site. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Site::class, Site::getById($site->getId()));

        return $site;
    }

    /**
     * Actor Function to create a Document for a Site
     *
     * @param Site   $site
     * @param string $key
     * @param string $locale
     *
     * @return Page
     */
    public function haveAPageDocumentForSite(Site $site, $key = 'document-test', $locale = null)
    {
        $document = $this->generatePageDocument($key, $locale);
        $document->setParentId($site->getRootDocument()->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while document page for site. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Page::class, Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Hard Link for a Site
     *
     * @param Site   $site
     * @param Page   $document
     * @param string $key
     * @param string $locale
     *
     * @return Page
     */
    public function haveAHardlinkForSite(Site $site, Page $document, $key = 'hardlink-test', $locale = null)
    {
        $hardLink = $this->generateHardlink($document, $key, $locale);
        $hardLink->setParentId($site->getRootDocument()->getId());

        try {
            $hardLink->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while document page for site. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Hardlink::class, Hardlink::getById($hardLink->getId()));

        return $hardLink;
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
        $document = $this->generatePageDocument('frontpage-mapped-' . $hardlinkDocument->getKey());
        $document->setParentId($hardlinkDocument->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while document page for frontpage mapping. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Page::class, Page::getById($document->getId()));

        $hardlinkDocument->setProperty('front_page_map', 'document', $document->getId(), false, false);

        try {
            $hardlinkDocument->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while document hardlink for frontpage mapping. message was: ' . $e->getMessage()));
        }

        return $document;
    }

    /**
     * Actor Function to create a language connection
     *
     * @param Page $sourceDocument
     * @param Page $targetDocument
     *
     */
    public function haveTwoConnectedDocuments(Page $sourceDocument, Page $targetDocument)
    {
        $service = new Service();
        $service->addTranslation($sourceDocument, $targetDocument);
    }

    /**
     * Actor Function to disable a document
     *
     * @param Document $document
     *
     * @return Document
     */
    public function haveAUnPublishedDocument(Document $document)
    {
        $document->setPublished(false);

        try {
            $document->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while un-publishing document. message was: ' . $e->getMessage()));
        }

        return $document;
    }

    /**
     * Actor Function to generate a single static route.
     *
     * @param string $name
     * @param string $translationKey
     *
     * @return Staticroute
     */
    public function haveAStaticRoute(string $name, string $translationKey)
    {
        $data = [
            'id'               => 1,
            'name'             => $name,
            'pattern'          => '/([a-zA-Z0-9-_]*)\\/(?:news|beitrag|nouvelles|notizia|artikel)\\/(.*?)$/	',
            'reverse'          => '/{%_locale}/@' . $translationKey . '/%testProperty',
            'module'           => 'AppBundle',
            'controller'       => '@AppBundle\\Controller\\DefaultController',
            'action'           => 'default',
            'variables'        => '_locale,entry',
            'defaults'         => null,
            'siteId'           => [],
            'priority'         => 0,
            'legacy'           => false,
            'creationDate'     => 1545383519,
            'modificationDate' => 1545383619
        ];

        $route = new Staticroute();
        $route->setValues($data);
        $route->save();

        $this->assertInstanceOf(Staticroute::class, $route);

        return $route;
    }

    /**
     * API Function to create a page document
     *
     * @param string $key
     * @param string $locale
     *
     * @return Page
     */
    protected function generatePageDocument($key = 'document-test', $locale = null)
    {
        $document = TestHelper::createEmptyDocumentPage('', false);
        $document->setController('@AppBundle\Controller\DefaultController');
        $document->setAction('default');
        $document->setKey($key);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, true);
        }

        return $document;
    }

    /**
     * API Function to create a hardlink document
     *
     * @param Page   $source
     * @param string $key
     * @param string $locale
     *
     * @return Hardlink
     */
    protected function generateHardlink(Page $source, $key = 'hardlink-test', $locale = null)
    {
        $hardlink = new Hardlink();
        $hardlink->setKey($key);
        $hardlink->setParentId(1);
        $hardlink->setSourceId($source->getId());
        $hardlink->setPropertiesFromSource(true);
        $hardlink->setChildrenFromSource(true);

        if ($locale !== null) {
            $hardlink->setProperty('language', 'text', $locale, false, true);
        }

        return $hardlink;
    }

    /**
     * API Function to create a site document
     *
     * @param string $domain
     * @param string $locale
     *
     * @return Site
     */
    protected function generateSiteDocument($domain, $locale = null)
    {
        $document = TestHelper::createEmptyDocumentPage($domain, false);
        $document->setKey(str_replace('.', '-', $domain));

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, true);
        }

        try {
            $document->save();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error while saving document for site. message was: ' . $e->getMessage()));
        }

        $site = new Site();
        $site->setRootId((int) $document->getId());
        $site->setMainDomain($domain);

        return $site;
    }

    /**
     * @return Container
     * @throws \Codeception\Exception\ModuleException
     */
    protected function getContainer()
    {
        return $this->getModule('\\' . PimcoreCore::class)->getContainer();
    }
}
