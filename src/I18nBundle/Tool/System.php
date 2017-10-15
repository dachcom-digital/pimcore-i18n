<?php

namespace I18nBundle\Tool;

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
        return Tool::isFrontend($request) === FALSE || $editMode === TRUE;
    }

    /**
     * @return bool
     */
    public static function isInCliMode()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * @param $fragments
     * @param $addStartSlash
     *
     * @return string
     */
    public static function joinPath($fragments, $addStartSlash = FALSE)
    {
        $f = [];
        $addStartSlash = $addStartSlash === TRUE || substr($fragments[0], 0, 1) === DIRECTORY_SEPARATOR;
        foreach ($fragments as $fragment) {
            if(empty($fragment)) {
                continue;
            }
            $f[] = trim($fragment, DIRECTORY_SEPARATOR);
        }

        return ($addStartSlash ? DIRECTORY_SEPARATOR : '') . join(DIRECTORY_SEPARATOR, $f);
    }

}