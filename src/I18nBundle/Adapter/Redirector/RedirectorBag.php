<?php

namespace I18nBundle\Adapter\Redirector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectorBag
{
    /**
     * @var array
     */
    protected $decisionBag = [];

    /**
     * @var string
     */
    protected $i18nMode;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \Pimcore\Model\Document
     */
    protected $document;

    /**
     * @var string
     */
    protected $documentLocale;

    /**
     * @var string
     */
    protected $documentCountry;


    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * RedirectorBag constructor.
     *
     * @param array $options
     */
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

    /**
     * @param string $name
     * @param array  $decision
     */
    public function addRedirectorDecisionToBag(string $name, array $decision)
    {
        $this->decisionBag[] = [
            'name'     => $name,
            'decision' => $decision
        ];
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getI18nMode()
    {
        return $this->i18nMode;
    }

    /**
     * @return null|array
     */
    public function getLastRedirectorDecision()
    {
        $last = array_values(array_slice($this->decisionBag, -1))[0];
        return $last;
    }

    /**
     * @return null|array
     */
    public function getLastValidRedirectorDecision()
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

    /**
     * @return array
     */
    public function getRedirectorDecisionBag()
    {
        return $this->decisionBag;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }
}