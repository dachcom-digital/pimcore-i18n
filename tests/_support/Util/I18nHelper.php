<?php

namespace DachcomBundle\Test\Util;

use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Tests\Util\TestHelper;

class I18nHelper
{
    public static function cleanUp()
    {
        TestHelper::cleanUp();

        // also delete all sub documents.
        $docList = new Document\Listing();
        $docList->setCondition('id != 1');
        $docList->setUnpublished(true);

        foreach ($docList->getDocuments() as $document) {
            \Codeception\Util\Debug::debug('[I18N] Deleting document: ' . $document->getKey());
            $document->delete();
        }

        // remove all sites (pimcore < 5.6)
        $db = \Pimcore\Db::get();
        $availableSites = $db->fetchAll('SELECT * FROM sites');
        if (is_array($availableSites)) {
            foreach ($availableSites as $availableSite) {
                $db->delete('sites', ['id' => $availableSite['id']]);
            }
        }

        // remove all redirects
        $redirects = new Redirect\Listing();
        foreach ($redirects->load() as $redirect) {
            $redirect->delete();
        }
    }
}
