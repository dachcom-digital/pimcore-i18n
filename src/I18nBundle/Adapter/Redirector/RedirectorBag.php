<?php

namespace I18nBundle\Adapter\Redirector;

use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectorBag
{
    protected array $decisionBag = [];
    protected string $i18nMode;
    protected Request $request;
    protected Document $document;
    protected ?string $documentLocale;
    protected ?string $documentCountry;
    protected ?string $defaultLocale;

    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'i18nType'        => null,
            'request'         => null,
            'document'        => null,
            'documentLocale'  => null,
            'documentCountry' => null,
            'defaultLocale'   => null,
        ]);

        $resolver->setRequired(['i18nType', 'request', 'document']);

        $options = $resolver->resolve($options);

        $this->i18nMode = $options['i18nType'];
        $this->request = $options['request'];
        $this->document = $options['document'];
        $this->documentLocale = $options['documentLocale'];
        $this->documentCountry = $options['documentCountry'];
        $this->defaultLocale = $options['defaultLocale'];
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

    public function getI18nMode(): string
    {
        return $this->i18nMode;
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

    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }
}
