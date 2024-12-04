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

namespace I18nBundle\Model;

use Pimcore\Model\Site;

interface ZoneSiteInterface
{
    public function getSiteRequestContext(): SiteRequestContext;

    public function getPimcoreSite(): ?Site;

    public function hasPimcoreSite(): bool;

    public function getRootId(): int;

    public function isRootDomain(): bool;

    public function isActive(): bool;

    public function getLocale(): ?string;

    public function getCountryIso(): ?string;

    public function getLanguageIso(): string;

    public function getHrefLang(): string;

    public function getLocaleUrlMapping(): ?string;

    public function getUrl(): string;

    public function getHomeUrl(): ?string;

    public function getRootPath(): string;

    public function getFullPath(): string;

    public function getType(): ?string;

    public function getSubSites(): array;

    public function hasSubSites(): bool;
}
