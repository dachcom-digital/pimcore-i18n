<?php

namespace I18nBundle\Adapter\Redirector;

interface RedirectorInterface
{
    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function getName(): string;

    public function setName(string $name): void;

    public function setDecision(array $decision): void;

    public function getDecision(): array;

    public function lastRedirectorWasSuccessful(RedirectorBag $redirectorBag): bool;

    public function makeDecision(RedirectorBag $redirectorBag): void;
}
