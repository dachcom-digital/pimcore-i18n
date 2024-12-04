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

use Symfony\Component\Templating\EngineInterface;

class AdminMessageRendererHelper
{
    public function __construct(
        protected AdminLocaleHelper $adminLocaleHelper,
        protected EngineInterface $templating
    ) {
    }

    public function render(string $messageTemplate, array $params = []): string
    {
        $translationLocale = $this->adminLocaleHelper->getCurrentAdminUserLocale();

        return $this->templating->render(
            sprintf('@I18n/%s.html.twig', $messageTemplate),
            array_merge($params, ['adminLocale' => $translationLocale])
        );
    }
}
