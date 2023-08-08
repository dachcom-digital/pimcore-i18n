<?php

namespace I18nBundle\Resolver;

use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Registry\RedirectorRegistry;
use Pimcore\Config;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class RedirectResolver
{
    public function __construct(
        protected Configuration $configuration,
        protected RedirectorRegistry $redirectorRegistry
    ) {
    }

    public function resolve(Request $request, I18nContextInterface $i18nContext): ?RedirectResponse
    {
        $redirectUrl = null;

        $redirectorBag = new RedirectorBag([
            'i18nContext' => $i18nContext,
            'request'     => $request,
        ]);

        foreach ($this->redirectorRegistry->all() as $redirector) {
            $redirector->makeDecision($redirectorBag);
            $decision = $redirector->getDecision();

            if ($decision['valid'] === true) {
                $redirectUrl = $decision['url'];
            }

            $redirectorBag->addRedirectorDecisionToBag($redirector->getName(), $decision);
        }

        if ($redirectUrl === null) {
            return null;
        }

        $status = $this->configuration->getConfig('redirect_status_code');

        return new RedirectResponse($this->resolveRedirectUrl($redirectUrl), $status);
    }

    public function resolveRedirectUrl(string $path): string
    {
        $config = Config::getSystemConfiguration('documents');

        $endPath = rtrim($path, '/');

        if ($config['allow_trailing_slash'] !== 'no') {
            $endPath .= '/';
        }

        return $endPath;
    }
}
