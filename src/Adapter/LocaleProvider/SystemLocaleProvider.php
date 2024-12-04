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

namespace I18nBundle\Adapter\LocaleProvider;

use I18nBundle\Definitions;
use I18nBundle\Model\ZoneInterface;
use Pimcore\Config;
use Pimcore\Tool;

class SystemLocaleProvider extends AbstractLocaleProvider
{
    public function getDefaultLocale(ZoneInterface $zone): ?string
    {
        $config = Config::getSystemConfiguration('general');

        return $config['default_language'];
    }

    public function getActiveLocales(ZoneInterface $zone): array
    {
        $validLocales = [];
        $systemLocales = Tool::getValidLanguages();

        foreach ($systemLocales as $id => $locale) {
            $validLocales[] = [
                'id'      => (int) $id,
                'locale'  => $locale,
                'isoCode' => $locale
            ];
        }

        return $validLocales;
    }

    public function getGlobalInfo(ZoneInterface $zone): array
    {
        return [
            'id'      => null,
            'locale'  => null,
            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE
        ];
    }
}
