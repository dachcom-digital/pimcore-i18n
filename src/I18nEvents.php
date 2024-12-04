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

namespace I18nBundle;

final class I18nEvents
{
    public const CONTEXT_SWITCH = 'i18n.switch';
    public const PATH_ALTERNATE_STATIC_ROUTE = 'i18n.path.static_route.alternate';
    public const PATH_ALTERNATE_SYMFONY_ROUTE = 'i18n.path.symfony_route.alternate';
    public const PREVIEW_CONFIG_GENERATION = 'i18n.preview.config_generation';
    public const PREVIEW_URL_GENERATION = 'i18n.preview.url_generation';
}
