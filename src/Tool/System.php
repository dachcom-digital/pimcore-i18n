<?php

namespace I18nBundle\Tool;

use Pimcore\Config;
use Pimcore\Tool;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
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
