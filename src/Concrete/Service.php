<?php

namespace Concrete\Package\ErrorNotifier;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Exception;
use RuntimeException;
use Throwable;

class Service
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

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
     * @return \Generator|\Concrete\Package\ErrorNotifier\Notifier[]
     */
    private function buildNotifiers(array &$errors)
    {
        $config = $this->app->make(Repository::class);
        if ($config->get('error_notifier::options.telegram.enabled')) {
            try {
                yield $this->buildTelegramNotifier($config->get('error_notifier::options.telegram'));
            } catch (Exception $x) {
                $errors[] = $x;
            } catch (Throwable $x) {
                $errors[] = $x;
            }
        }
        if ($config->get('error_notifier::options.slack.enabled')) {
            try {
                yield $this->buildSlackNotifier($config->get('error_notifier::options.slack'));
            } catch (Exception $x) {
                $errors[] = $x;
            } catch (Throwable $x) {
                $errors[] = $x;
            }
        }
    }

    private function buildTelegramNotifier(array $options)
    {
        $token = isset($options['token']) ? $options['token'] : null;
        if (!is_string($token) || ($token = trim($token)) === '') {
            throw new RuntimeException(t('Missing Telegram token'));
        }
        $serializedRecipients = isset($options['recipients']) ? $options['recipients'] : null;
        $recipients = is_string($serializedRecipients) ? preg_split('/\s+/', $serializedRecipients, -1, PREG_SPLIT_NO_EMPTY) : [];
        if ($recipients === []) {
            throw new RuntimeException(t('Missing Telegram recipients'));
        }

        return $this->app->make(Notifier\Telegram::class, [
            'token' => $token,
            'recipients' => $recipients,
            'stripWebroot' => !empty($options['stripWebroot']),
        ]);
    }

    private function buildSlackNotifier(array $options)
    {
        $token = isset($options['token']) ? $options['token'] : null;
        if (!is_string($token) || ($token = trim($token)) === '') {
            throw new RuntimeException(t('Missing Slack token'));
        }
        $serializedChannels = isset($options['channels']) ? $options['channels'] : null;
        $channels = is_string($serializedChannels) ? preg_split('/\s+/', $serializedChannels, -1, PREG_SPLIT_NO_EMPTY) : [];
        if ($channels === []) {
            throw new RuntimeException(t('Missing Slack channels'));
        }

        return $this->app->make(Notifier\Slack::class, [
            'token' => $token,
            'channels' => $channels,
            'stripWebroot' => !empty($options['stripWebroot']),
        ]);
    }
}
