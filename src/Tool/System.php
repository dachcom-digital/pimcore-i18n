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

namespace I18nBundle\Tool;

use Pimcore\Config;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;

class System
{
    public static function isInBackend(Request $request): bool
    {
        $editMode = $request->attributes->get(EditmodeResolver::ATTRIBUTE_EDITMODE);

        return Tool::isFrontend($request) === false || $editMode === true;
    }

    public static function isInCliMode(): bool
    {
        return PHP_SAPI === 'cli' && Config::getEnvironment() !== 'test';
    }

    public static function joinPath(array $fragments, bool $addStartSlash = false): string
    {
        $f = [];
        $addStartSlash = $addStartSlash === true || str_starts_with($fragments[0], DIRECTORY_SEPARATOR);
        foreach ($fragments as $fragment) {
            if (empty($fragment)) {
                continue;
            }
            $f[] = trim($fragment, DIRECTORY_SEPARATOR);
        }

        return ($addStartSlash ? DIRECTORY_SEPARATOR : '') . implode(DIRECTORY_SEPARATOR, $f);
    }
}
