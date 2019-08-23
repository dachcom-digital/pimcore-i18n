<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Helper\DocumentHelper;
use I18nBundle\Manager\ZoneManager;

abstract class AbstractPathGenerator implements PathGeneratorInterface
{
    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var DocumentHelper
     */
    protected $documentHelper;

    /**
     * @param ZoneManager    $zoneManager
     * @param DocumentHelper $documentHelper
     */
    public function __construct(ZoneManager $zoneManager, DocumentHelper $documentHelper)
    {
        $this->zoneManager = $zoneManager;
        $this->documentHelper = $documentHelper;
    }
}
