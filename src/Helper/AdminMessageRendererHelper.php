<?php

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
