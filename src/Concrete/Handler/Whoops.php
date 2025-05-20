<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Error\UserMessageException;
use Concrete\Package\ErrorNotifier\Options;
use Concrete\Package\ErrorNotifier\Service;
use Whoops\Handler\Handler;

defined('C5_EXECUTE') or die('Access Denied');

class Whoops extends Handler
{
    /**
     * @var \Concrete\Package\ErrorNotifier\Options
     */
    private $errorNotifierOptions;

    /**
     * @var \Concrete\Package\ErrorNotifier\Service
     */
    private $errorNotifierService;

    /**
     * @param int $level
     */
    public function __construct(Options $options, Service $service)
    {
        $this->errorNotifierOptions = $options;
        $this->errorNotifierService = $service;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Whoops\Handler\HandlerInterface::handle()
     */
    public function handle()
    {
        if ($this->errorNotifierOptions->isInterceptExceptions()) {
            $exception = $this->getException();
            if ($exception && !$exception instanceof UserMessageException) {
                $this->errorNotifierService->notify($exception);
            }
        }

        return Handler::DONE;
    }
}
