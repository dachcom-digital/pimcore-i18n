<?php

namespace I18nBundle\Adapter\Redirector;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractRedirector implements RedirectorInterface
{
    protected bool $enabled = true;
    protected ?string $name;
    protected array $decision = [];

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function setDecision(array $decision): void
    {
        $this->decision = $this->getResolver()->resolve($decision);
    }

    public function getDecision(): array
    {
        return $this->getResolver()->resolve($this->decision);
    }

    public function lastRedirectorWasSuccessful(RedirectorBag $redirectorBag): bool
    {
        $lastDecisionBag = $redirectorBag->getLastValidRedirectorDecision();
        if (!is_null($lastDecisionBag) && $lastDecisionBag['decision']['valid'] === true) {
            $this->setDecision(['valid' => false]);

            return true;
        }

        return false;
    }

    private function getResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'redirectorOptions' => [],
            'valid'             => null,
            'locale'            => null,
            'country'           => null,
            'language'          => null,
            'url'               => null,
        ]);

        $resolver->setRequired(['locale', 'url']);
        $resolver->setAllowedTypes('valid', 'boolean');
        $resolver->setAllowedTypes('locale', ['null', 'string']);
        $resolver->setAllowedTypes('url', ['null', 'string']);
        $resolver->setAllowedTypes('language', ['null', 'string']);
        $resolver->setAllowedTypes('country', ['null', 'string']);
        $resolver->setAllowedTypes('redirectorOptions', ['array']);

        return $resolver;
    }
}
