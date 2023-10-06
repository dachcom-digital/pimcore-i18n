<?php

namespace I18nBundle\Adapter\Redirector;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface RedirectorInterface
{
    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function getName(): string;

    public function setName(string $name): void;

    public function setDecision(array $decision): void;

    public function getDecision(): array;

    public function setConfig(array $config): void;

    public function getConfig(): array;
    
    public function lastRedirectorWasSuccessful(RedirectorBag $redirectorBag): bool;

    public function makeDecision(RedirectorBag $redirectorBag): void;
}
