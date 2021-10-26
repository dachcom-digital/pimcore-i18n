<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Model\I18nZoneInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectorBag
{
    protected array $decisionBag = [];
    protected I18nZoneInterface $zone;
    protected Request $request;

    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'zone'    => null,
            'request' => null,
        ]);

        $resolver->setRequired(['zone', 'request']);
        $resolver->setAllowedTypes('zone', [I18nZoneInterface::class]);
        $resolver->setAllowedTypes('request', [Request::class]);

        $options = $resolver->resolve($options);

        $this->zone = $options['zone'];
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

    public function getZone(): I18nZoneInterface
    {
        return $this->zone;
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
