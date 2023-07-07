<?php

namespace Concrete\Package\ErrorNotifier\Notifier;

use Concrete\Core\Application\Application;
use Concrete\Package\ErrorNotifier\Notifier;
use Exception;
use RuntimeException;
use Throwable;

defined('C5_EXECUTE') or die('Access denied.');

class Slack extends Notifier
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string[]
     */
    private $channels;

    /**
     * @param string $token
     * @param string[] $channels
     * @param bool $stripWebroot
     */
    public function __construct($token, array $channels, Application $app, $stripWebroot)
    {
        parent::__construct($app, $stripWebroot);
        $this->token = $token;
        $this->channels = $channels;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Notifier::notify()
     */
    public function notify($message)
    {
        $errors = [];
        try {
            if ($this->recipients !== []) {
                $formattedMessage = $this->formatMessage($message, 4000);
                if ($formattedMessage !== '') {
                    foreach ($this->channels as $channel) {
                        try {
                            $this->sendNotificationTo($channel, $formattedMessage);
                        } catch (Exception $x) {
                            $errors[] = $x;
                        } catch (Throwable $x) {
                            $errors[] = $x;
                        }
                    }
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
     * @param string $channel
     * @param string $formattedMessage
     *
     * @throws \Exception|\Throwable
     */
    protected function sendNotificationTo($channel, $formattedMessage)
    {
        if ($channel[0] !== '#') {
            $channel = '#' . $channel;
        }
        $url = 'https://slack.com/api/chat.postMessage';
        $data = [
            'channel' => $channel[0] === '#' ? $channel : "#{$channel}",
            'text' => $formattedMessage,
        ];
        $json = json_encode(
            $data,
            0
            | (defined('JSON_UNESCAPED_LINE_TERMINATORS') ? JSON_UNESCAPED_LINE_TERMINATORS : 0)
            | (defined('JSON_UNESCAPED_SLASHES') ? JSON_UNESCAPED_SLASHES : 0)
            | (defined('JSON_THROW_ON_ERROR') ? JSON_THROW_ON_ERROR : 0)
        );
        if ($json === false) {
            throw new RuntimeException(t('Failed to encode data'));
        }
        $response = $this->post($url, 'application/json; charset=utf-8', $json, "Bearer {$this->token}");
        if ($response[0] !== 200) {
            throw new RuntimeException(t('Server responded with a %s HTTP response code', $response[0]));
        }
        try {
            $decodedBody = json_decode($response[1], true);
        } catch (Exception $_) {
            $decodedBody = null;
        } catch (Throwable $_) {
            $decodedBody = null;
        }
        if (!is_array($decodedBody)) {
            throw new RuntimeException(t('Failed to decode the response'));
        }
        if (!empty($decodedBody['ok'])) {
            return;
        }
        $message = empty($decodedBody['error']) ? t('Failed to send the message to Slack') : $decodedBody['error'];
        switch ($message) {
            case 'not_authed':
                throw new RuntimeException(t('The Bot User OAuth Token is wrong'));
            case 'not_in_channel':
                throw new RuntimeException(t('You need to add the Slack bot to the channel %s', $channel));
        }

        throw new RuntimeException($message);
    }
}
