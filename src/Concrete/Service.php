<?php

namespace Concrete\Package\ErrorNotifier;

use Concrete\Core\Application\Application;
use Exception;
use RuntimeException;
use Throwable;

class Service
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

    /**
     * @var \Concrete\Package\ErrorNotifier\Options|null
     */
    private $options;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param \Exception|\Throwable|string $message
     *
     * @return \Exception[]|\Throwable[]
     */
    public function notify($message)
    {
        $errors = [];
        try {
            foreach ($this->buildNotifiers($errors) as $notifier) {
                try {
                    $errors = array_merge($errors, $notifier->notify($message));
                } catch (Exception $x) {
                    $errors[] = $x;
                } catch (Throwable $x) {
                    $errors[] = $x;
                }
            }
        } catch (Exception $x) {
            $errors[] = $x;
        } catch (Throwable $x) {
            $errors[] = $x;
        }

        return $errors;
    }

    /**
     * @return \Concrete\Package\ErrorNotifier\Options
     */
    private function getOptions()
    {
        if (!$this->options) {
            $this->options = $this->app->make(Options::class);
        }

        return $this->options;
    }

    /**
     * @return \Generator|\Concrete\Package\ErrorNotifier\Notifier[]
     */
    private function buildNotifiers(array &$errors)
    {
        $options = $this->getOptions();
        if ($options->isTelegramEnabled()) {
            try {
                yield $this->buildTelegramNotifier();
            } catch (Exception $x) {
                $errors[] = $x;
            } catch (Throwable $x) {
                $errors[] = $x;
            }
        }
        if ($options->isSlackEnabled()) {
            try {
                yield $this->buildSlackNotifier();
            } catch (Exception $x) {
                $errors[] = $x;
            } catch (Throwable $x) {
                $errors[] = $x;
            }
        }
    }

    /**
     * @param bool $stripWebroot
     *
     * @return \Concrete\Package\ErrorNotifier\Notifier\Telegram
     */
    private function buildTelegramNotifier()
    {
        $options = $this->getOptions();
        $token = $options->getTelegramToken();
        if (!is_string($token) || ($token = trim($token)) === '') {
            throw new RuntimeException(t('Missing Telegram token'));
        }
        $recipients = $options->getTelegramRecipients();
        if (!is_array($recipients) || $recipients === []) {
            throw new RuntimeException(t('Missing Telegram recipients'));
        }

        return $this->app->make(Notifier\Telegram::class, [
            'token' => $token,
            'recipients' => $recipients,
            'stripWebroot' => $options->isStripWebroot(),
        ]);
    }

    /**
     * @param bool $stripWebroot
     *
     * @return \Concrete\Package\ErrorNotifier\Notifier\Slack
     */
    private function buildSlackNotifier()
    {
        $options = $this->getOptions();
        $token = $options->getSlackToken();
        if (!is_string($token) || ($token = trim($token)) === '') {
            throw new RuntimeException(t('Missing Slack token'));
        }
        $channels = $options->getSlackChannels();
        if (!is_array($channels) || $channels === []) {
            throw new RuntimeException(t('Missing Slack channels'));
        }

        return $this->app->make(Notifier\Slack::class, [
            'token' => $token,
            'channels' => $channels,
            'stripWebroot' => $options->isStripWebroot(),
        ]);
    }
}
