<?php

namespace I18nBundle\EventListener;

use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Service\Request\PimcoreContextResolver;
use Pimcore\Templating\Renderer\ActionRenderer;
use Pimcore\Bundle\CoreBundle\EventListener\AbstractContextAwareListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener extends AbstractContextAwareListener implements EventSubscriberInterface
{
    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var ContextManager
     */
    protected $contextManager;

    /**
     * @var PathGeneratorManager
     */
    protected $pathGeneratorManager;

    /**
     * @var bool
     */
    protected $renderErrorPage = TRUE;

    /**
     * @param ActionRenderer       $actionRenderer
     * @param ZoneManager          $zoneManager
     * @param ContextManager       $contextManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param bool                 $renderErrorPage
     */
    public function __construct(
        ActionRenderer $actionRenderer,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        $renderErrorPage = TRUE
    ) {
        $this->actionRenderer = $actionRenderer;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;

        $this->renderErrorPage = (bool)$renderErrorPage;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10]
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());
            return;
        }

        $request = $event->getRequest();
        if ($this->renderErrorPage && $this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            $this->handleErrorPage($event);
        }
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    protected function handleErrorPage(GetResponseForExceptionEvent $event)
    {
        if (\Pimcore::inDebugMode() || PIMCORE_DEVMODE) {
            return;
        }

        //re-init zones since we're in a kernelException.
        $zoneDomains = $this->zoneManager->getCurrentZoneDomains(TRUE);
        $exception = $event->getException();

        $statusCode = 500;
        $headers = [];

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        }

        $host = $event->getRequest()->getHost();

        $pathInfo = array_values(array_filter(explode('/', $event->getRequest()->getPathInfo())));
        $possibleLocaleSlug = $pathInfo[0];

        //get host page.
        $hostIndex = array_keys(array_filter($zoneDomains,
            function ($v) use ($host, $possibleLocaleSlug) {
                return $v['realHost'] === $host;
            }));

        //maybe there is a localized host page.
        $languageIndex = FALSE;
        if (!empty($possibleLocaleSlug)) {
            $languageIndex = array_keys(array_filter($zoneDomains,
                function ($v) use ($host, $possibleLocaleSlug) {
                    return $v['realHost'] === $host && $v['hrefLang'] === $possibleLocaleSlug;
                }));
        }

        if ($languageIndex !== FALSE) {
            $hostIndex = $languageIndex[0];
        } else if ($hostIndex !== FALSE) {
            $hostIndex = $hostIndex[0];
        }

        $errorPath = NULL;

        $defaultErrorPath = Config::getSystemConfig()->documents->error_pages->default;
        if (Site::isSiteRequest()) {

            $site = Site::getCurrentSite();
            $siteErrorPath = $site->getErrorDocument();
            if (!empty($siteErrorPath)) {
                $defaultErrorPath = $siteErrorPath;
            }
        }

        if ($hostIndex !== FALSE) {

            $guessedErrorPath = 'error';

            //if we have a default error page, try to use same name.
            if(!empty($defaultErrorPath)) {
                $defaultErrorPathFragments = array_filter(explode('/', $defaultErrorPath));
                if(!empty($defaultErrorPathFragments)) {
                    $guessedErrorPath = end($defaultErrorPathFragments);
                }
            }

            $path = $zoneDomains[$hostIndex]['fullPath'] . '/' . $guessedErrorPath;
            if (Document\Service::pathExists($path)) {
                $errorPath = $path;
            };
        }

        //no custom error paths found, use system error path.
        if (empty($errorPath)) {
            $errorPath = $defaultErrorPath;
        }

        if (empty($errorPath)) {
            $errorPath = '/';
        }

        $document = Document::getByPath($errorPath);

        if (!$document instanceof Document\Page) {
            // default is home
            $document = Document::getById(1);
        }

        //fix i18n language / country context.
        $docLang = explode('_', $document->getProperty('language'));
        Cache\Runtime::set('i18n.languageIso', strtolower($docLang[0]));
        Cache\Runtime::set('i18n.countryIso', $document->getProperty('country') ? $document->getProperty('country') : Definitions::INTERNATIONAL_COUNTRY_NAMESPACE);

        try {
            $response = $this->actionRenderer->render($document);
        } catch (\Exception $e) {
            // we are even not able to render the error page, so we send the client a unicorn
            $response = 'Page not found (' . $e->getLine() . ') 🦄';
        }

        $event->setResponse(new Response($response, $statusCode, $headers));
    }
}
