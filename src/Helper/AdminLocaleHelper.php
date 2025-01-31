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

use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\EngineInterface;

class AdminLocaleHelper
{
    public function __construct(
        protected RequestStack $requestStack,
        protected TokenStorageUserResolver $userResolver,
        protected EngineInterface $templating
    ) {
    }

    public function getCurrentAdminUserLocale(): string
    {
        if ($this->userResolver->getUser() instanceof User) {
            return $this->userResolver->getUser()->getLanguage();
        }

        if ($user = Admin::getCurrentUser()) {
            return $user->getLanguage();
        }

        if ($this->requestStack->getMainRequest() instanceof Request && $user = Authentication::authenticateSession($this->requestStack->getMainRequest())) {
            return $user->getLanguage();
        }

        return 'en';
    }
}
