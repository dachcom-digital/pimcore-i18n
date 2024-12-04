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

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Context\I18nContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectorBag
{
    protected array $decisionBag = [];
    protected I18nContextInterface $i18nContext;
    protected Request $request;

    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'i18nContext' => null,
            'request'     => null,
        ]);

        $resolver->setRequired(['i18nContext', 'request']);
        $resolver->setAllowedTypes('i18nContext', [I18nContextInterface::class]);
        $resolver->setAllowedTypes('request', [Request::class]);

        $options = $resolver->resolve($options);

        $this->i18nContext = $options['i18nContext'];
        $this->request = $options['request'];
    }

    public function addRedirectorDecisionToBag(string $name, array $decision): void
    {
        $this->decisionBag[] = [
            'name'     => $name,
            'decision' => $decision
        ];
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getI18nContext(): I18nContextInterface
    {
        return $this->i18nContext;
    }

    public function getLastRedirectorDecision(): ?array
    {
        return array_values(array_slice($this->decisionBag, -1))[0];
    }

    public function getLastValidRedirectorDecision(): ?array
    {
        $lastValidBag = null;
        foreach (array_reverse($this->decisionBag) as $bag) {
            if ($bag['decision']['valid'] === true) {
                $lastValidBag = $bag;

                break;
            }
        }

        return $lastValidBag;
    }

    public function getRedirectorDecisionBag(): array
    {
        return $this->decisionBag;
    }
}
