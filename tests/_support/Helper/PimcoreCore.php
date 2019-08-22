<?php

namespace DachcomBundle\Test\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Lib\Connector\Symfony as SymfonyConnector;
use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Config;
use Pimcore\Event\TestEvents;
use Pimcore\Tests\Helper\Pimcore as PimcoreCoreModule;
use Symfony\Component\Filesystem\Filesystem;

class PimcoreCore extends PimcoreCoreModule
{
    /**
     * @var bool
     */
    protected $kernelHasCustomConfig = false;

    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            // set specific configuration file for suite
            'configuration_file' => null
        ]);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @inheritDoc
     */
    public function _after(\Codeception\TestInterface $test)
    {
        parent::_after($test);

        $this->restoreSystemConfig();

        // config has changed, we need to restore default config before starting a new test!
        if ($this->kernelHasCustomConfig === true) {
            $this->clearCache();
            $this->bootKernelWithConfiguration(null);
            $this->kernelHasCustomConfig = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function _afterSuite()
    {
        \Pimcore::collectGarbage();
        $this->clearCache();
        parent::_afterSuite();
    }

    /**
     * @inheritdoc
     */
    public function _initialize()
    {
        $this->setPimcoreEnvironment($this->config['environment']);
        $this->initializeKernel();
        $this->setupDbConnection();
        $this->setPimcoreCacheAvailability('disabled');
    }

    /**
     * @inheritdoc
     */
    protected function initializeKernel()
    {
        $maxNestingLevel = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        $configFile = null;
        if ($this->config['configuration_file'] !== null) {
            $configFile = $this->config['configuration_file'];
        }

        $this->bootKernelWithConfiguration($configFile);
        $this->setupPimcoreDirectories();
    }

    /**
     * @param $configuration
     */
    protected function bootKernelWithConfiguration($configuration)
    {
        if ($configuration === null) {
            $configuration = 'config_default.yml';
        }

        putenv('DACHCOM_BUNDLE_CONFIG_FILE=' . $configuration);

        $this->kernel = require __DIR__ . '/../_boot/kernelBuilder.php';
        $this->getKernel()->boot();

        $this->client = new SymfonyConnector($this->kernel, $this->persistentServices, $this->config['rebootable_client']);

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // dispatch kernel booted event - will be used from services which need to reset state between tests
        $this->kernel->getContainer()->get('event_dispatcher')->dispatch(TestEvents::KERNEL_BOOTED);
    }

    /**
     * @param bool $force
     */
    protected function clearCache($force = true)
    {
        \Codeception\Util\Debug::debug('[PIMCORE] Clear Cache!');

        $fileSystem = new Filesystem();

        try {
            $fileSystem->remove(PIMCORE_PROJECT_ROOT . '/var/cache');
            $fileSystem->mkdir(PIMCORE_PROJECT_ROOT . '/var/cache');
        } catch (\Exception $e) {
            //try again later if "directory not empty" error occurs.
            if ($force === true) {
                sleep(1);
                $this->clearCache(false);
            }
        }
    }

    /**
     * @param $env
     */
    protected function setPimcoreEnvironment($env)
    {
        Config::setEnvironment($env);
    }

    /**
     * @param string $state
     */
    protected function setPimcoreCacheAvailability($state = 'disabled')
    {
        if ($state === 'disabled') {
            Cache::disable();
        } else {
            Cache::enable();
        }
    }

    /**
     * Actor Function to boot symfony with a specific bundle configuration
     *
     * @param string $configuration
     */
    public function haveABootedSymfonyConfiguration(string $configuration)
    {
        $this->kernelHasCustomConfig = true;
        $this->clearCache();
        $this->bootKernelWithConfiguration($configuration);
    }

    /**
     * Actor Function to enabled full page cache
     * @throws \Exception
     */
    public function haveRuntimeFullPageCacheEnabled()
    {
        if (!Runtime::isRegistered('pimcore_config_system')) {
            return;
        }

        $rawConfig = Runtime::get('pimcore_config_system');

        $rawConfigArray = $rawConfig->toArray();
        $rawConfigArray['cache']['enabled'] = true;

        $newConfig = new \Pimcore\Config\Config($rawConfigArray);

        Runtime::set('pimcore_config_system', $newConfig);
        Runtime::set('pimcore_config_system_backup', $rawConfig);
    }

    /**
     * @param string   $exception
     * @param string   $message
     * @param \Closure $callback
     */
    public function seeException($exception, $message, \Closure $callback)
    {
        $function = function () use ($callback, $exception, $message) {
            try {

                $callback();
                return false;

            } catch (\Exception $e) {

                if (get_class($e) === $exception or get_parent_class($e) === $exception) {

                    if (empty($message)) {
                        return true;
                    }

                    return $message === $e->getMessage();
                }

                return false;
            }
        };

        $this->assertTrue($function());
    }


    /**
     * @throws \Exception
     */
    protected function restoreSystemConfig()
    {
        if (!Runtime::isRegistered('pimcore_config_system_backup')) {
            return;
        }

        $backupConfig = Runtime::get('pimcore_config_system_backup');

        if($backupConfig === null) {
            return;
        }

        codecept_debug('restore system config');

        Runtime::set('pimcore_config_system', $backupConfig);
        Runtime::set('pimcore_config_system_backup', null);

    }

    /**
     * Override symfony internal Domains check.
     *
     * We're able to allow different hosts via pimcore sites.
     *
     * @return array
     */
    protected function getInternalDomains()
    {
        $internalDomains = [
            '/test-domain1.test/',
            '/test-domain2.test/',
            '/test-domain3.test/',
            '/test-domain4.test/',
            '/test-domain5.test/',
            '/test-domain6.test/',
            '/test-domain7.test/',
        ];

        return array_unique($internalDomains);
    }
}

