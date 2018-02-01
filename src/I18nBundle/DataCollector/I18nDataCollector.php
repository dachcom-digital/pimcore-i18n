<?php
/**
 * Pimcore
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace I18nBundle\DataCollector;

use I18nBundle\Manager\ZoneManager;
use Pimcore\Cache\Runtime;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class I18nDataCollector extends DataCollector
{
    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var ZoneManager
     */
    protected $isFrontend = TRUE;

    /**
     * I18nDataCollector constructor.
     *
     * @param ZoneManager   $zoneManager
     * @param RequestHelper $requestHelper
     */
    public function __construct(ZoneManager $zoneManager, RequestHelper $requestHelper)
    {
        $this->zoneManager = $zoneManager;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @inheritDoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = NULL)
    {
        //only track current valid routes.
        if($response->getStatusCode() !== 200) {
            return;
        }

        if ( $exception instanceof \RuntimeException
            || $this->requestHelper->isFrontendRequest($request) === FALSE
            || $this->requestHelper->isFrontendRequestByAdmin($request)
        ) {
            $this->data['isFrontend'] = FALSE;
            return;
        }

        $zoneId = $this->zoneManager->getCurrentZoneInfo('zoneId');
        $mode = $this->zoneManager->getCurrentZoneInfo('mode');

        $currentLanguage = '--';
        $currentCountry = '--';

        if (Runtime::isRegistered('i18n.countryIso')) {
            $currentCountry = Runtime::get('i18n.countryIso');
        }

        if (Runtime::isRegistered('i18n.languageIso')) {
            $currentLanguage = Runtime::get('i18n.languageIso');
        }

        $this->data = [
            'isFrontend'      => TRUE,
            'zoneId'          => empty($zoneId) ? 'none' : $zoneId,
            'i18nMode'        => $mode,
            'currentLanguage' => $currentLanguage,
            'currentCountry'  => $currentCountry
        ];
    }

    /**
     * @inheritDoc
     */
    public function isFrontend()
    {
        return $this->data['isFrontend'];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'i18n.data_collector';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    /**
     * @return string|null
     */
    public function getI18nMode()
    {
        return $this->data['i18nMode'];
    }

    /**
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->data['currentLanguage'];
    }

    /**
     * @return string|null
     */
    public function getCountry()
    {
        return $this->data['currentCountry'];
    }

    /**
     * @return string|null
     */
    public function getZoneId()
    {
        return $this->data['zoneId'];
    }
}
