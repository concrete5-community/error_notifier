<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Error\UserMessageException;
use Concrete\Package\ErrorNotifier\Options;
use Concrete\Package\ErrorNotifier\Service;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied');

class ThrowableHandler
{
    /**
     * @var \Concrete\Package\ErrorNotifier\Options
     */
    private $errorNotifierOptions;

    /**
     * @var \Concrete\Package\ErrorNotifier\Service
     */
    private $errorNotifierService;

    public function __construct(Options $options, Service $service)
    {
        $this->errorNotifierOptions = $options;
        $this->errorNotifierService = $service;
    }

    public function __invoke(Throwable $throwable)
    {
        if (!$throwable instanceof UserMessageException) {
            if ($this->errorNotifierOptions->isInterceptExceptions()) {
                $this->errorNotifierService->notify($throwable);
            }
        }
    }
}
