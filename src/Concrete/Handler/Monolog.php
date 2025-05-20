<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Package\ErrorNotifier\Options;
use Concrete\Package\ErrorNotifier\Service;
use Monolog\Handler\AbstractHandler;

defined('C5_EXECUTE') or die('Access Denied');

class Monolog extends AbstractHandler
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
        parent::__construct($this->errorNotifierOptions->getMinExceptionsLogLevel(), true);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Monolog\Handler\HandlerInterface::handle()
     */
    public function handle(array $record)
    {
        if ($this->isHandling($record) && $this->errorNotifierOptions->isInterceptLogWrites()) {
            $this->errorNotifierService->notify($this->getFormatter()->format($record));
        }

        return false;
    }
}
