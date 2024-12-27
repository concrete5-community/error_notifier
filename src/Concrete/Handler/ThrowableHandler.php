<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Package\ErrorNotifier\Service;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied');

class ThrowableHandler
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function __invoke(Throwable $throwable)
    {
        if (!$throwable instanceof UserMessageException) {
            if ($this->app->make(Repository::class)->get('error_notifier::options.interceptExceptions')) {
                $this->app->make(Service::class)->notify($throwable);
            }
        }
    }
}
