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

namespace I18nBundle\Registry;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;

class LocaleProviderRegistry
{
    protected array $adapter = [];
    private string $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function register(mixed $service, string $alias): void
    {
        if (!in_array($this->interface, class_implements($service), true)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to implement "%s", "%s" given.', get_class($service), $this->interface, implode(', ', class_implements($service)))
            );
        }

        $this->adapter[$alias] = $service;
    }

    public function has(string $alias): bool
    {
        return isset($this->adapter[$alias]);
    }

    public function get(string $alias): LocaleProviderInterface
    {
        if (!$this->has($alias)) {
            throw new \Exception('"' . $alias . '" locale provider does not exist');
        }

        return $this->adapter[$alias];
    }
}
