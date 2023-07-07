<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Package\ErrorNotifier\Service;
use Whoops\Handler\Handler;

defined('C5_EXECUTE') or die('Access Denied');

class Whoops extends Handler
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Whoops\Handler\HandlerInterface::handle()
     */
    public function handle()
    {
        if ($this->app->make(Repository::class)->get('error_notifier::options.whoops')) {
            $exception = $this->getException();
            if ($exception && !$exception instanceof UserMessageException) {
                return $this->app->make(Service::class)->notify($exception);
            }
        }

        return Handler::DONE;
    }
}
