<?php

namespace DachcomBundle\Test\Helper\Browser;

use Codeception\Module;
use DachcomBundle\Test\Helper\PimcoreCore;

class PhpBrowserRedirects extends Module
{
    /**
     * Actor Function to see if last request has a redirection code in status
     */
    public function seePreviousResponseCodeIsRedirection()
    {
        $response = 0;

        try {
            /** @var \DachcomBundle\Test\Helper\PimcoreCore $browser */
            $browser = $this->getModule('\\' . PimcoreCore::class);
            $response = $browser->client->getInternalResponse()->getStatus();
        } catch (\Exception $e) {
            \Codeception\Util\Debug::debug(sprintf('[I18N ERROR] error getting internal response from client. message was: ' . $e->getMessage()));
        }

        $this->assertGreaterThanOrEqual(200, $response);
        $this->assertLessThanOrEqual(299, $response);
    }

}