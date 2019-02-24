<?php

namespace I18nBundle\Tool;

use Pimcore\Config;
use Pimcore\Tool;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Symfony\Component\HttpFoundation\Request;

class System
{
    /**
     * @param Request $request
     *
     * @return bool
     */
    public static function isInBackend(Request $request)
    {
        $editMode = $request->attributes->get(EditmodeResolver::ATTRIBUTE_EDITMODE);

        return Tool::isFrontend($request) === false || $editMode === true;
    }

    /**
     * @return bool
     */
    public static function isInCliMode()
    {
        return php_sapi_name() === 'cli' && Config::getEnvironment() !== 'test';
    }

    /**
     * @param array $fragments
     * @param bool  $addStartSlash
     *
     * @return string
     */
    public static function joinPath($fragments, $addStartSlash = false)
    {
        $f = [];
        $addStartSlash = $addStartSlash === true || substr($fragments[0], 0, 1) === DIRECTORY_SEPARATOR;
        foreach ($fragments as $fragment) {
            if (empty($fragment)) {
                continue;
            }
            $f[] = trim($fragment, DIRECTORY_SEPARATOR);
        }

        return ($addStartSlash ? DIRECTORY_SEPARATOR : '') . join(DIRECTORY_SEPARATOR, $f);
    }
}
