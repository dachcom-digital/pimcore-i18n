<?php

namespace I18nBundle\PreviewGenerator;

use I18nBundle\Builder\RouteParameterBuilder;
use I18nBundle\Helper\AdminLocaleHelper;
use I18nBundle\Helper\AdminMessageRendererHelper;
use I18nBundle\I18nEvents;
use Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Locale;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ObjectPreviewGenerator implements PreviewGeneratorInterface
{
    protected array $sites = [];
    protected array $locales = [];

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected AdminMessageRendererHelper $adminMessageRendererHelper,
        protected AdminLocaleHelper $adminLocaleHelper,
        protected RequestStack $requestStack,
        protected UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function generatePreviewUrl(Concrete $object, array $params): string
    {
        $valid = true;
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;

        $queryParams = [];
        $routeParams = [];
        $routeContext = [
            'isFrontendRequestByAdmin' => true
        ];

        if (!array_key_exists('i18n_locale', $params)) {
            $valid = false;
        } else {
            $routeParams['_locale'] = $params['i18n_locale'];
            $queryParams['i18n_preview_locale'] = $params['i18n_locale'];
        }

        if ($this->hasSites()) {
            if (!array_key_exists('i18n_site', $params)) {
                $valid = false;
            } else {
                $routeContext['site'] = Site::getById($params['i18n_site']);
                $queryParams['i18n_preview_site'] = $params['i18n_site'];
            }
        }

        if ($valid === false) {
            $this->renderErrorMessage();

            return '#';
        }

        $event = new GenericEvent($object);
        $event->setArguments([
            'queryParams'   => $queryParams,
            'routeParams'   => $routeParams,
            'routeContext'  => $routeContext,
            'referenceType' => $referenceType,
        ]);

        $this->eventDispatcher->dispatch($event, I18nEvents::PREVIEW_URL_GENERATION);

        $routeItemParameters = RouteParameterBuilder::buildForEntity($object, $event->getArgument('routeParams'), $event->getArgument('routeContext'));

        try {
            $path = $this->urlGenerator->generate('', $routeItemParameters, $event->getArgument('referenceType'));
        } catch (\Throwable $e) {
            $this->renderErrorMessage($e->getMessage());

            return '#';
        }

        $path .= sprintf('%s%s', str_contains($path, '?') ? '&' : '?', http_build_query($event->getArgument('queryParams')));

        return $path;
    }

    public function getPreviewConfig(Concrete $object): array
    {
        $params = [];

        $params[] = [
            'name'         => 'i18n_locale',
            'label'        => 'Locale',
            'values'       => $this->getLocales(),
            'defaultValue' => $this->getDefaultLocale()
        ];

        if ($this->hasSites() === false) {
            return $params;
        }

        $params[] = [
            'name'         => 'i18n_site',
            'label'        => 'Site',
            'values'       => $this->getSites(),
            'defaultValue' => $this->getDefaultSite()
        ];

        $event = new GenericEvent();
        $event->setArgument('previewConfig', $params);

        $this->eventDispatcher->dispatch($event, I18nEvents::PREVIEW_CONFIG_GENERATION);

        return $event->getArgument('previewConfig');
    }

    protected function getLocales(): array
    {
        if (count($this->locales) > 0) {
            return $this->locales;
        }

        $adminDisplayLocale = $this->adminLocaleHelper->getCurrentAdminUserLocale();

        $locales = [];
        foreach (Tool::getValidLanguages() as $locale) {
            $locales[Locale::getDisplayName($locale, $adminDisplayLocale)] = $locale;
        }

        return $this->locales = $locales;
    }

    protected function getDefaultLocale(): string
    {
        $locales = $this->getLocales();
        $adminDisplayLocale = $this->adminLocaleHelper->getCurrentAdminUserLocale();

        if (false === $key = array_search($adminDisplayLocale, $locales, true)) {
            return reset($locales);
        }

        return $locales[$key];
    }

    protected function hasSites(): bool
    {
        return count($this->getSites()) > 0;
    }

    protected function getSites(): array
    {
        if (count($this->sites) > 0) {
            return $this->sites;
        }

        $sites = [];
        foreach ((new Site\Listing())->getSites() as $site) {
            $sites[$site->getMainDomain()] = $site->getId();
        }

        return $this->sites = $sites;
    }

    protected function getDefaultSite(): ?string
    {
        if (!$this->hasSites()) {
            return null;
        }

        if (!$this->requestStack->getMainRequest() instanceof Request) {
            return null;
        }

        $sites = $this->getSites();
        $currentHost = $this->requestStack->getMainRequest()->getHost();

        foreach ($this->getSites() as $mainDomain => $siteId) {
            $siteHost = parse_url(sprintf('https://%s', $mainDomain), PHP_URL_HOST);
            if ($siteHost === $currentHost) {
                return $mainDomain;
            }
        }

        return reset($sites);
    }

    protected function renderErrorMessage(?string $message = null): void
    {
        echo $this->adminMessageRendererHelper->render('preview_incomplete_message', ['message' => $message]);

        // yes. Otherwise, pimcore would throw an exception.
        exit;
    }
}