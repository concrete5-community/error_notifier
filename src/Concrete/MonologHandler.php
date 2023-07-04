<?php

namespace Concrete\Package\TelegramErrors;

use Concrete\Core\Application\Application;
use Monolog\Handler\AbstractHandler;

defined('C5_EXECUTE') or die('Access Denied');

class MonologHandler extends AbstractHandler
{
    use HandlerTrait;

    /**
     * @param int $level
     */
    public function __construct(Application $app, $level)
    {
        parent::__construct($level, true);
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Monolog\Handler\HandlerInterface::handle()
     */
    public function handle(array $record)
    {
        if ($this->isHandling($record)) {
            $this->sendNotification($this->getFormatter()->format($record));
        }

        return false;
    }
}
