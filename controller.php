<?php

namespace Concrete\Package\TelegramErrors;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Package\Package;
use Whoops\Run;

/**
 * The package controller.
 *
 * Manages the package installation, update and start-up.
 */
class Controller extends Package implements ProviderAggregateInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.5.0';

    /**
     * The unique handle that identifies the package.
     *
     * @var string
     */
    protected $pkgHandle = 'telegram_errors';

    /**
     * The package version.
     *
     * @var string
     */
    protected $pkgVersion = '0.0.3';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Notify via Telegram');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Send errors and warnings to Telegram.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface::getEntityManagerProvider()
     */
    public function getEntityManagerProvider()
    {
        return new StandardPackageProvider($this->app, $this, []);
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
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installContentFile('config/install.xml');
    }

    public function on_start()
    {
        $config = $this->app->make(Repository::class);
        if ($config->get('telegram_errors::options.whoops')) {
            if ($this->app->bound(Run::class)) {
                $this->app->make(Run::class)->pushHandler($this->app->make(WhoopsErrorHandler::class));
            }
        }
        if ($config->get('telegram_errors::options.exceptionsLog')) {
            $minEeceptionsLogLevel = $config->get('telegram_errors::options.minExceptionsLogLevel');
            $this->app->extend('log/exceptions', static function ($logger, Application $app) use ($minEeceptionsLogLevel) {
                if ($logger instanceof \Monolog\Logger) {
                    $logger->pushHandler($app->make(MonologHandler::class, ['level' => (int) $minEeceptionsLogLevel]));
                }

                return $logger;
            });
        }
    }
}
