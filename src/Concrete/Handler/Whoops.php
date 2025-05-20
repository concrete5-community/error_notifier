<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Application\Application;
use Concrete\Core\Error\UserMessageException;
use Concrete\Package\ErrorNotifier\Options;
use Concrete\Package\ErrorNotifier\Service;
use Whoops\Handler\Handler;

defined('C5_EXECUTE') or die('Access Denied');

class Whoops extends Handler
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

    /**
     * @param int $level
     */
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
        $exception = $this->getException();
        if ($exception && !$exception instanceof UserMessageException) {
            if ($this->app->make(Options::class)->isInterceptExceptions()) {
                $this->app->make(Service::class)->notify($exception);
            }
        }

        return Handler::DONE;
    }
}
