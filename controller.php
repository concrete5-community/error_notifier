<?php

namespace Concrete\Package\ErrorNotifier;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\EntityManager\Provider\ProviderInterface;
use Concrete\Core\Logging\Channels;
use Concrete\Core\Package\Package;

/**
 * The package controller.
 *
 * Manages the package installation, update and start-up.
 */
class Controller extends Package implements ProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.5.2';

    /**
     * The unique handle that identifies the package.
     *
     * @var string
     */
    protected $pkgHandle = 'error_notifier';

    /**
     * The package version.
     *
     * @var string
     */
    protected $pkgVersion = '1.2.3';

    /**
     * @var string
     */
    private $upgradingFrom = '';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Error Notifier');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Send errors and warnings to Slack and Telegram.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderInterface::getDrivers()
     */
    public function getDrivers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        parent::install();
        $this->installContentFile('config/install.xml');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgradeCoreData()
     */
    public function upgradeCoreData()
    {
        $entity = $this->getPackageEntity();
        $this->upgradingFrom = $entity ? (string) $entity->getPackageVersion() : '';
        parent::upgradeCoreData();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installContentFile('config/install.xml');
        if ($this->upgradingFrom && version_compare($this->upgradingFrom, '1.0.1') <= 0) {
            $config = $this->app->make(Repository::class);
            $config->save('error_notifier::options.interceptExceptions', (bool) $config->get('error_notifier::options.whoops'));
            $config->save('error_notifier::options.interceptLogWrites', (bool) $config->get('error_notifier::options.exceptionsLog'));
        }
    }

    public function on_start()
    {
        $this->app->bindIf(Options::class, static function() { return app(Options\Config::class); }, true);
        if ($this->app->bound('Whoops\Run')) {
            $this->app->make('Whoops\Run')->pushHandler($this->app->make(Handler\Whoops::class));
        } elseif ($this->app->bound('Concrete\Core\Error\Handling\ErrorHandler')) {
            $this->app->make('Concrete\Core\Error\Handling\ErrorHandler')->addExceptionListener(function(\Throwable $x) {
                $handler = $this->app->make(Handler\ThrowableHandler::class);
                return $handler($x);
            });
        }
        if (!class_exists('Concrete\Core\Logging\LoggerFactory')) {
            $this->app->extend('log/exceptions', static function ($logger, Application $app) {
                if ($logger instanceof \Monolog\Logger) {
                    $logger->pushHandler($app->make(Handler\Monolog::class));
                }

                return $logger;
            });
        } else {
            $director = $this->app->make('director');
            $dispatcher =  method_exists($director, 'getEventDispatcher') ? $director->getEventDispatcher() : $director;
            $dispatcher->addListener('on_logger_create', function($event) {
                $logger = method_exists($event, 'getLogger') ? $event->getLogger() : null;
                if ($logger instanceof \Monolog\Logger && $logger->getName() === Channels::CHANNEL_EXCEPTIONS) {
                    /** @var \Monolog\Logger $logger */
                    $logger->pushHandler($this->app->make(Handler\Monolog::class));
                }
            });
        }
    }
}
