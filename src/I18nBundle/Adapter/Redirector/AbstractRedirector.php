<?php

namespace I18nBundle\Adapter\Redirector;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractRedirector implements RedirectorInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var array
     */
    protected $decision = [];

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param array $decision
     */
    public function setDecision(array $decision)
    {
        $this->decision = $this->getResolver()->resolve($decision);
    }

    /**
     * @return array
     */
    public function getDecision()
    {
        return $this->getResolver()->resolve($this->decision);
    }

    /**
     * @param RedirectorBag $redirectorBag
     *
     * @return bool|mixed
     */
    public function lastRedirectorWasSuccessful(RedirectorBag $redirectorBag)
    {
        $lastDecisionBag = $redirectorBag->getLastValidRedirectorDecision();
        if (!is_null($lastDecisionBag) && $lastDecisionBag['decision']['valid'] === true) {
            $this->setDecision(['valid' => false]);
            return true;
        }

        return false;

    }

    /**
     * @return OptionsResolver
     */
    private function getResolver()
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