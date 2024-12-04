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

use I18nBundle\Adapter\Redirector\RedirectorInterface;

class RedirectorRegistry
{
    protected array $adapter = [];
    private string $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function register(mixed $service, string $alias): void
    {
        if (empty($alias)) {
            throw new \InvalidArgumentException(sprintf('%s does not have a valid alias.', get_class($service)));
        }

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

    public function get(string $alias): RedirectorInterface
    {
        if (!$this->has($alias)) {
            throw new \Exception('"' . $alias . '" redirector identifier does not exist');
        }

        return $this->adapter[$alias];
    }

    /**
     * @return array<int, RedirectorInterface>
     */
    public function all(): array
    {
        return array_values($this->adapter);
    }
}
