<?php

namespace I18nBundle\Tool;

use Pimcore\Service\Request\EditmodeResolver;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;

class System
{
    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isEnvironment($type = 'Development')
    {
        $environment = \Pimcore\Config::getEnvironment();

        if (is_array($type) && in_array($environment, $type)) {
            return TRUE;
        }

        return $environment === $type;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public static function isInBackend(Request $request)
    {
        if (Tool::isFrontendRequestByAdmin($request)) {
            return TRUE;
        }

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
     *
     * @return string
     */
    public static function joinPath($fragments)
    {
        $f = [];
        foreach ($fragments as $fragment) {
            $f[] = trim($fragment, '/');
        }

        return join('/', $f);
    }

}