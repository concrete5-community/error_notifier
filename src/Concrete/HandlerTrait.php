<?php

namespace Concrete\Package\TelegramErrors;

use Concrete\Core\Config\Repository\Repository;
use Exception;
use RuntimeException;
use Throwable;

defined('C5_EXECUTE') or die('Access denied.');

trait HandlerTrait
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * @param string|\Exception|\Throwable $message
     *
     * @return \Exception|\Throwable|null
     */
    protected function sendNotification($message)
    {
        $recipients = $this->getTelegramRecipients();
        if ($recipients === []) {
            return;
        }
        try {
            $notifier = $this->createNotifier();

            return $notifier->notify($recipients, $message);
        } catch (Exception $x) {
            return $x;
        } catch (Throwable $x) {
            return $x;
        }
    }

    /**
     * @return string[]
     */
    protected function getTelegramRecipients()
    {
        $config = $this->app->make(Repository::class);

        return preg_split('/\s+/', $config->get('telegram_errors::options.recipients', ''), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @return \Concrete\Package\TelegramErrors\ErrorNotifier
     */
    protected function createNotifier()
    {
        $config = $this->app->make(Repository::class);
        $tgToken = $config->get('telegram_errors::options.token');
        if (!is_string($tgToken) || ($tgToken = trim($tgToken)) === '') {
            throw new RuntimeException(t('The Telegram token is not configured'));
        }

        return $this->app->make(ErrorNotifier::class, ['tgToken' => $tgToken]);
    }
}
