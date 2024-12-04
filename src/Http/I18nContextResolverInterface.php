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

namespace I18nBundle\Http;

use I18nBundle\Context\I18nContextInterface;
use Symfony\Component\HttpFoundation\Request;

interface I18nContextResolverInterface
{
    public function setContext(I18nContextInterface $i18nContext, Request $request);

    public function getContext(Request $request): ?I18nContextInterface;

    public function hasContext(Request $request): bool;
}
