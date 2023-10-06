<?php

namespace I18nBundle\Helper;

use I18nBundle\Configuration\Configuration;
use I18nBundle\Definitions;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieHelper
{
    private Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function get(Request $request, string $key = Definitions::REDIRECT_COOKIE_NAME): ?array
    {
        $cookie = $request->cookies->get($key);

        if (is_null($cookie)) {
            return null;
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

    public function set(Response $response, array $params): Cookie
    {
        $cookieConfig = $this->configuration->getConfig('cookie');

        $path = $cookieConfig['path'];
        $domain = $cookieConfig['domain'];
        $secure = $cookieConfig['secure'];
        $httpOnly = $cookieConfig['httpOnly'];
        $sameSite = $cookieConfig['sameSite'];
        $expire = is_string($cookieConfig['expire']) ? strtotime($cookieConfig['expire']) : $cookieConfig['expire'];

        $cookieData = base64_encode(json_encode($params));
        $cookie = new Cookie(Definitions::REDIRECT_COOKIE_NAME, $cookieData, $expire, $path, $domain, $secure, $httpOnly, false, $sameSite);
        $response->headers->setCookie($cookie);

        return $cookie;
    }
}
