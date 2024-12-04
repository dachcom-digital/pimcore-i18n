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

interface ZoneInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function getDefaultLocale(): ?string;

    public function getActiveLocales(): array;

    public function getLocaleAdapterName(): string;

    public function getDomains(): array;

    public function getTranslations(): array;

    public function isActiveZone(): bool;

    public function getLocaleUrlMapping(): array;

    public function getGlobalInfo(): array;

    /**
     * @return array<int, ZoneSiteInterface>
     */
    public function getSites(bool $flatten = false): array;
}
