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

namespace I18nBundle\Adapter\Redirector;

interface RedirectorInterface
{
    public function getName(): string;

    public function setName(string $name): void;

    public function setDecision(array $decision): void;

    public function getDecision(): array;

    public function setConfig(array $config): void;

    public function getConfig(): array;

    public function lastRedirectorWasSuccessful(RedirectorBag $redirectorBag): bool;

    public function makeDecision(RedirectorBag $redirectorBag): void;
}
