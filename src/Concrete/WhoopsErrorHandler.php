<?php

namespace Concrete\Package\TelegramErrors;

use Concrete\Core\Application\Application;
use Concrete\Core\Error\UserMessageException;
use Whoops\Handler\Handler;

defined('C5_EXECUTE') or die('Access Denied');

class WhoopsErrorHandler extends Handler
{
    use HandlerTrait;

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
            $this->sendNotification($exception);
        }

        return Handler::DONE;
    }
}
