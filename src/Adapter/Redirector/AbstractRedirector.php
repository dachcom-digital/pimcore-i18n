<?php

namespace I18nBundle\Adapter\Redirector;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractRedirector implements RedirectorInterface
{
    protected array $config = [];
    protected ?string $name;
    protected array $decision = [];

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $configResolver = $this->getConfigResolver();
        if (null === $configResolver) {
            if (!empty($config)) {
                throw new \Exception(sprintf('redirector adapter "%s" has a config, but no config resolver was provided.', $this->getName()));
            }
        } else {
            $this->config = $configResolver->resolve($config);
        }
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
        $this->decision = $this->getDecisionResolver()->resolve($decision);
    }

    public function getDecision(): array
    {
        return $this->getDecisionResolver()->resolve($this->decision);
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

    protected function getConfigResolver(): ?OptionsResolver
    {
        return null;
    }

    private function getDecisionResolver(): OptionsResolver
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
