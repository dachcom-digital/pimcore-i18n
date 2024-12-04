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

namespace I18nBundle\Factory;

use I18nBundle\Model\RouteItem\RouteItem;
use I18nBundle\Model\RouteItem\RouteItemInterface;

class RouteItemFactory
{
    public function create(string $type, bool $headless): RouteItemInterface
    {
        return new RouteItem($type, $headless);
    }

    public function createFromArray(string $type, bool $headless, array $params): RouteItemInterface
    {
        $routeItem = new RouteItem($type, $headless);

        foreach ($params as $key => $value) {
            if ($key === 'routeParameters') {
                if (is_array($value)) {
                    foreach ($value as $rpKey => $rpValue) {
                        $routeItem->getRouteParametersBag()->set($rpKey, $rpValue);
                    }
                }

                continue;
            }

            if ($key === 'routeAttributes') {
                if (is_array($value)) {
                    foreach ($value as $raKey => $raValue) {
                        $routeItem->getRouteAttributesBag()->set($raKey, $raValue);
                    }
                }

                continue;
            }

            if ($key === 'context') {
                if (is_array($value)) {
                    foreach ($value as $rpKey => $rpValue) {
                        $routeItem->getRouteContextBag()->set($rpKey, $rpValue);
                    }
                }

                continue;
            }

            $setter = sprintf('set%s', ucfirst($key));
            if (method_exists($routeItem, $setter)) {
                $routeItem->$setter($value);
            } else {
                throw new \Exception(sprintf('Method "%s" does not exist', $setter));
            }
        }

        return $routeItem;
    }
}
