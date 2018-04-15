<?php

namespace I18nBundle\Adapter\Redirector;

interface RedirectorInterface
{
    /**
     * @return boolean
     */
    public function isEnabled();

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @param array $decision
     * @return mixed
     */
    public function setDecision(array $decision);

    /**
     * @return mixed
     */
    public function getDecision();

    /**
     * @param RedirectorBag $redirectorBag
     * @return mixed
     */
    public function lastRedirectorWasSuccessful(RedirectorBag $redirectorBag);

    /**
     * @param RedirectorBag $redirectorBag
     * @return mixed
     */
    public function makeDecision(RedirectorBag $redirectorBag);

}