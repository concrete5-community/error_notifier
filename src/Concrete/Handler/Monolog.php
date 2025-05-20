<?php

namespace Concrete\Package\ErrorNotifier\Handler;

use Concrete\Core\Application\Application;
use Concrete\Package\ErrorNotifier\Options;
use Concrete\Package\ErrorNotifier\Service;
use Monolog\Handler\AbstractHandler;

defined('C5_EXECUTE') or die('Access Denied');

class Monolog extends AbstractHandler
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

    /**
     * @var \Concrete\Package\ErrorNotifier\Options
     */
    private $errorNotifierOptions;

    /**
     * @param int $level
     */
    public function __construct(Application $app, Options $options)
    {
        $this->app = $app;
        $this->errorNotifierOptions = $options;
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
            $this->app->make(Service::class)->notify($this->getFormatter()->format($record));
        }

        return false;
    }
}
