<?php

namespace I18nBundle\Helper;

use I18nBundle\Definitions;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieHelper
{
    /**
     * @param Request $request
     * @param string  $key
     *
     * @return array|bool
     */
    public function get(Request $request, $key = Definitions::REDIRECT_COOKIE_NAME)
    {
        $cookie = $request->cookies->get($key);

        if (is_null($cookie)) {
            return false;
        }

        $cookieData = [];

        try {
            $data = json_decode(base64_decode($cookie), true);
            if (is_array($data)) {
                $cookieData = $data;
            }
        } catch (\Exception $e) {
            // fail silently.
        }

        return $cookieData;
    }

    /**
     * @param Response $response
     * @param array    $params
     *
     * @return Cookie
     */
    public function set(Response $response, $params)
    {
        $cookieData = base64_encode(json_encode($params));
        $cookie = new Cookie(Definitions::REDIRECT_COOKIE_NAME, $cookieData, strtotime('+1 year'));
        $response->headers->setCookie($cookie);

        return $cookie;
    }
}
