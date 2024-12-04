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

namespace I18nBundle\Helper;

use I18nBundle\Definitions;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieHelper
{
    public function __construct(protected array $config)
    {
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
        $path = $this->config['path'];
        $domain = $this->config['domain'];
        $secure = $this->config['secure'];
        $httpOnly = $this->config['http_only'];
        $sameSite = $this->config['same_site'];
        $expire = is_string($this->config['expire']) ? strtotime($this->config['expire']) : $this->config['expire'];

        $cookieData = base64_encode(json_encode($params));
        $cookie = new Cookie(Definitions::REDIRECT_COOKIE_NAME, $cookieData, $expire, $path, $domain, $secure, $httpOnly, false, $sameSite);
        $response->headers->setCookie($cookie);

        return $cookie;
    }
}
