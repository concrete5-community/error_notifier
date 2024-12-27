<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\ErrorNotifier\Service;
use Monolog\Handler\AbstractHandler;

defined('C5_EXECUTE') or die('Access Denied');

class Monolog extends AbstractHandler
{
    /**
     * @param int $level
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $rawLevel = $this->app->make(Repository::class)->get('error_notifier::options.minExceptionsLogLevel');
        parent::__construct(is_numeric($rawLevel) ? (int) $rawLevel : \Monolog\Logger::NOTICE, true);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Monolog\Handler\HandlerInterface::handle()
     */
    public function handle(array $record)
    {
        if ($this->isHandling($record)) {
            if ($this->app->make(Repository::class)->get('error_notifier::options.interceptLogWrites')) {
                $this->app->make(Service::class)->notify($this->getFormatter()->format($record));
            }
        }

        return false;
    }
}
