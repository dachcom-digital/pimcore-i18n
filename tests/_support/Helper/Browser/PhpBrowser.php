<?php

namespace DachcomBundle\Test\Helper\Browser;

use Codeception\Module;
use Codeception\Lib;
use Codeception\Exception\ModuleException;
use DachcomBundle\Test\Helper\PimcoreCore;
use DachcomBundle\Test\Helper\PimcoreUser;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

class PhpBrowser extends Module implements Lib\Interfaces\DependsOnModule
{
    const PIMCORE_ADMIN_CSRF_TOKEN_NAME = 'MOCK_CSRF_TOKEN';

    /**
     * @var Cookie
     */
    protected $sessionSnapShot;

    /**
     * @var PimcoreCore
     */
    protected $pimcoreCore;

    /**
     * @return array|mixed
     */
    public function _depends()
    {
        return [
            'Codeception\Module\Symfony' => 'PhpBrowser needs the pimcore core framework to work.'
        ];
    }

    /**
     * @param PimcoreCore $pimcoreCore
     */
    public function _inject($pimcoreCore)
    {
        $this->pimcoreCore = $pimcoreCore;
    }

    /**
     * @inheritDoc
     */
    public function _initialize()
    {
        $this->sessionSnapShot = [];

        parent::_initialize();
    }

    /**
     * Actor Function to see a page with enabled edit-mode
     *
     * @param string $page
     */
    public function amOnPageInEditMode(string $page)
    {
        $this->pimcoreCore->amOnPage(sprintf('%s?pimcore_editmode=true', $page));
    }

    /**
     * Actor Function to login into Pimcore Backend
     *
     * @param $username
     */
    public function amLoggedInAs($username)
    {
        $firewallName = 'admin';

        try {
            /** @var PimcoreUser $userModule */
            $userModule = $this->getModule('\\' . PimcoreUser::class);
        } catch (ModuleException $pimcoreModule) {
            $this->debug('[PIMCORE BUNDLE MODULE] could not load pimcore user module');
            return;
        }

        $pimcoreUser = $userModule->getUser($username);

        if (!$pimcoreUser instanceof User) {
            $this->debug(sprintf('[PIMCORE BUNDLE MODULE] could not fetch user %s.', $username));
            return;
        }

        /** @var Session $session */
        $session = $this->pimcoreCore->getContainer()->get('session');

        $user = new \Pimcore\Bundle\AdminBundle\Security\User\User($pimcoreUser);
        $token = new UsernamePasswordToken($user, null, $firewallName, $pimcoreUser->getRoles());
        $this->pimcoreCore->getContainer()->get('security.token_storage')->setToken($token);

        \Pimcore\Tool\Session::useSession(function (AttributeBagInterface $adminSession) use ($pimcoreUser, $session) {
            $session->setId(\Pimcore\Tool\Session::getSessionId());
            $adminSession->set('user', $pimcoreUser);
            $adminSession->set('csrfToken', self::PIMCORE_ADMIN_CSRF_TOKEN_NAME);
        });

        // allow re-usage of session in same cest.
        if (!empty($this->sessionSnapShot)) {
            $cookie = $this->sessionSnapShot;
        } else {
            $cookie = new Cookie($session->getName(), $session->getId());
            $this->sessionSnapShot = $cookie;
        }

        $this->pimcoreCore->client->getCookieJar()->clear();
        $this->pimcoreCore->client->getCookieJar()->set($cookie);

    }

    /**
     * Actor Function to send tokenized ajax request in backend
     *
     * @param string $url
     * @param array  $params
     */
    public function sendTokenAjaxPostRequest(string $url, array $params = [])
    {
        $params['csrfToken'] = self::PIMCORE_ADMIN_CSRF_TOKEN_NAME;
        $this->pimcoreCore->sendAjaxPostRequest($url, $params);
    }

    /**
     * Actor Function to see if last executed request is in given path
     *
     * @param string $expectedPath
     */
    public function seeLastRequestIsInPath(string $expectedPath)
    {
        $requestUri = $this->pimcoreCore->client->getInternalRequest()->getUri();
        $requestServer = $this->pimcoreCore->client->getInternalRequest()->getServer();

        $expectedUri = sprintf('http://%s%s', $requestServer['HTTP_HOST'], $expectedPath);

        $this->assertEquals($expectedUri, $requestUri);
    }

    /**
     * Actor Function to check if last _fragment request has given properties in request attributes.
     *
     * @param array $properties
     */
    public function seePropertiesInLastFragmentRequest(array $properties = [])
    {
        /** @var Profiler $profiler */
        $profiler = $this->pimcoreCore->_getContainer()->get('profiler');

        $tokens = $profiler->find('', '_fragment', 1, 'GET', '', '');
        if (count($tokens) === 0) {
            throw new \RuntimeException('No profile found. Is the profiler data collector enabled?');
        }

        $token = $tokens[0]['token'];
        /** @var \Symfony\Component\HttpKernel\Profiler\Profile $profile */
        $profile = $profiler->loadProfile($token);

        if (!$profile instanceof Profile) {
            throw new \RuntimeException(sprintf('Profile with token "%s" not found.', $token));
        }

        /** @var RequestDataCollector $requestCollector */
        $requestCollector = $profile->getCollector('request');

        foreach ($properties as $property) {
            $this->assertTrue($requestCollector->getRequestAttributes()->has($property), sprintf('"%s" not found in request collector.', $property));
        }
    }
}
