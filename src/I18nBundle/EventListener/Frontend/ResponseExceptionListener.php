<?php

namespace I18nBundle\EventListener\Frontend;

use I18nBundle\Definitions;
use I18nBundle\Manager\ContextManager;
use I18nBundle\Manager\PathGeneratorManager;
use I18nBundle\Manager\ZoneManager;
use Pimcore\Cache;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\DataObject;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Document;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Templating\Renderer\ActionRenderer;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseExceptionListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

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
     * @var SiteResolver
     */
    protected $siteResolver;

    /**
     * @var Document\Service
     */
    protected $documentService;

    /**
     * @var array
     */
    protected $pimcoreConfig;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @param ActionRenderer       $actionRenderer
     * @param ZoneManager          $zoneManager
     * @param ContextManager       $contextManager
     * @param PathGeneratorManager $pathGeneratorManager
     * @param SiteResolver         $siteResolver
     * @param Document\Service     $documentService
     * @param ConnectionInterface  $db
     * @param array                $pimcoreConfig
     */
    public function __construct(
        ActionRenderer $actionRenderer,
        ZoneManager $zoneManager,
        ContextManager $contextManager,
        PathGeneratorManager $pathGeneratorManager,
        SiteResolver $siteResolver,
        Document\Service $documentService,
        ConnectionInterface $db,
        array $pimcoreConfig
    ) {
        $this->actionRenderer = $actionRenderer;
        $this->zoneManager = $zoneManager;
        $this->contextManager = $contextManager;
        $this->pathGeneratorManager = $pathGeneratorManager;
        $this->siteResolver = $siteResolver;
        $this->documentService = $documentService;
        $this->db = $db;
        $this->pimcoreConfig = $pimcoreConfig;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10]
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $renderErrorPage = $this->pimcoreConfig['error_handling']['render_error_document'];

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());

            return;
        }

        $request = $event->getRequest();
        if ($renderErrorPage === true && $this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            $this->handleErrorPage($event);
        }
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Exception
     */
    protected function handleErrorPage(GetResponseForExceptionEvent $event)
    {
        if (\Pimcore::inDebugMode()) {
            return;
        }

        // re-init zones since we're in a kernelException.
        $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);

        $exception = $event->getException();
        $document = $this->detectDocument($event, $zoneDomains);

        $this->setRuntime($event->getRequest(), $document->getProperty('language'));
        $this->contextManager->getContext()->setDocument($document);

        try {
            $response = $this->actionRenderer->render($document);
        } catch (\Exception $e) {
            // we are even not able to render the error page, so we send the client a unicorn
            $response = 'Page not found (' . $e->getMessage() . ') 🦄';
        }

        $statusCode = 500;
        $headers = [];

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        }

        $this->logToHttpErrorLog($event->getRequest(), $statusCode);

        $event->setResponse(new Response($response, $statusCode, $headers));
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @param array                        $zoneDomains
     *
     * @return Document
     */
    protected function detectDocument(GetResponseForExceptionEvent $event, array $zoneDomains)
    {
        $defaultErrorDocument = null;
        $localizedErrorDocument = null;
        $nearestDocumentLocale = null;
        $document = null;

        $host = preg_replace('/^www./', '', $event->getRequest()->getHost());

        // 1. get default system error page ($defaultErrorPath)
        $defaultErrorPath = $this->pimcoreConfig['documents']['error_pages']['default'];

        if ($this->siteResolver->isSiteRequest($event->getRequest())) {
            $path = $this->siteResolver->getSitePath($event->getRequest());
            // 2. get site error page
            $siteErrorPath = $this->siteResolver->getSite()->getErrorDocument();
            if (!empty($siteErrorPath)) {
                $defaultErrorPath = $siteErrorPath;
            }
        } else {
            $path = urldecode($event->getRequest()->getPathInfo());
        }

        if (!empty($defaultErrorPath)) {
            $defaultErrorDocument = Document::getByPath($defaultErrorPath);
        }

        $nearestDocument = $this->documentService->getNearestDocumentByPath($path, true, ['page', 'hardlink']);

        // 3. find localized error from current path
        if ($nearestDocument instanceof Document) {
            $nearestDocumentLocale = $nearestDocument->getProperty('language');

            $validElements = array_keys(array_filter(
                $zoneDomains,
                function ($v) use ($host, $nearestDocumentLocale) {
                    return $v['realHost'] === $host && $v['locale'] === $nearestDocumentLocale;
                }
            ));

            //if we have a default error page, try to use same name.
            $guessedErrorPath = 'error';
            if ($defaultErrorDocument instanceof Document) {
                $guessedErrorPath = $defaultErrorDocument->getKey();
            }

            if (!empty($validElements)) {
                $validElement = $validElements[0];
                $localizedPath = $zoneDomains[$validElement]['fullPath'] . DIRECTORY_SEPARATOR . $guessedErrorPath;
                if (Document\Service::pathExists($localizedPath)) {
                    $localizedErrorDocument = Document::getByPath($localizedPath);
                }
            }

            // 4. find localized error page from source if path is in hard link context
            if (!$localizedErrorDocument instanceof Document) {
                if ($nearestDocument instanceof Document\Hardlink) {
                    $nearestSourceDocument = $nearestDocument->getSourceDocument();
                    $nearestSourceDocumentLocale = $nearestSourceDocument->getProperty('language');

                    $validSourceElements = array_keys(array_filter(
                        $zoneDomains,
                        function ($v) use ($host, $nearestSourceDocumentLocale) {
                            return $v['realHost'] === $host && $v['locale'] === $nearestSourceDocumentLocale;
                        }
                    ));

                    if (!empty($validSourceElements)) {
                        $validSourceElement = $validSourceElements[0];
                        $localizedSourcePath = $zoneDomains[$validSourceElement]['fullPath'] . DIRECTORY_SEPARATOR . $guessedErrorPath;
                        if (Document\Service::pathExists($localizedSourcePath)) {
                            $localizedErrorDocument = Document::getByPath($localizedSourcePath);
                        }
                    }
                }
            }
        }

        if ($localizedErrorDocument instanceof Document) {
            $document = $localizedErrorDocument;
        } elseif ($defaultErrorDocument instanceof Document) {
            $document = $defaultErrorDocument;
        } else {
            $document = Document::getById(1);
        }

        $locale = $document->getProperty('language');

        if (!empty($nearestDocumentLocale)) {
            $locale = $nearestDocumentLocale;
        }

        if (empty($locale)) {
            $locale = 'en';
        }

        $document->setProperty('language', 'text', $locale);

        return $document;
    }

    /**
     * @param Request $request
     * @param string  $locale
     */
    private function setRuntime(Request $request, string $locale)
    {
        // Pimcore does not initialize context in exception.
        Document::setHideUnpublished(true);
        DataObject\AbstractObject::setHideUnpublished(true);
        DataObject\AbstractObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);

        $request->attributes->set('_locale', $locale);
        $request->setLocale($locale);
        $request->setDefaultLocale($locale);

        $docLang = explode('_', $locale);
        $countryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE;

        if (count($docLang) > 1) {
            $countryIso = $docLang[1];
        }

        //fix i18n language / country context.
        Cache\Runtime::set('i18n.locale', $locale);
        Cache\Runtime::set('i18n.languageIso', strtolower($docLang[0]));
        Cache\Runtime::set('i18n.countryIso', $countryIso);
    }

    /**
     * @param Request $request
     * @param int     $statusCode
     */
    protected function logToHttpErrorLog(Request $request, $statusCode)
    {
        $uri = $request->getUri();
        $exists = $this->db->fetchOne('SELECT `date` FROM http_error_log WHERE uri = ?', $uri);

        if ($exists !== false) {
            $this->db->executeQuery('UPDATE http_error_log SET `count` = `count` + 1, `date` = ? WHERE uri = ?', [time(), $uri]);
            return;
        }

        $this->db->insert('http_error_log', [
            'uri'            => $uri,
            'code'           => (int) $statusCode,
            'parametersGet'  => serialize($request->query->all()),
            'parametersPost' => serialize($request->request->all()),
            'cookies'        => serialize($request->cookies->all()),
            'serverVars'     => serialize($request->server->all()),
            'date'           => time(),
            'count'          => 1
        ]);
    }
}
