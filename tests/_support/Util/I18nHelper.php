<?php

namespace DachcomBundle\Test\Util;

use Pimcore\Model\Document;
use Pimcore\Tests\Util\TestHelper;

class I18nHelper
{
    public static function cleanUp()
    {
        TestHelper::cleanUp();

        // also delete all sub documents.
        $docList = new Document\Listing();
        $docList->setCondition('id != 1');

        foreach ($docList->getDocuments() as $document) {
            \Codeception\Util\Debug::debug('[I18N] Deleting document: ' . $document->getKey());
            $document->delete();
        }
    }

}
