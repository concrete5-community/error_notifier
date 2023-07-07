<?php

namespace Concrete\Package\ErrorNotifier\Notifier;

use Concrete\Core\Application\Application;
use Concrete\Package\ErrorNotifier\Notifier;
use Exception;
use RuntimeException;
use Throwable;

defined('C5_EXECUTE') or die('Access denied.');

class Telegram extends Notifier
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string[]
     */
    private $recipients;

    /**
     * @param string $token
     * @param string[] $recipients
     * @param bool $stripWebroot
     */
    public function __construct($token, array $recipients, Application $app, $stripWebroot)
    {
        parent::__construct($app, $stripWebroot);
        $this->token = $token;
        $this->recipients = $recipients;
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
                $formattedMessage = $this->formatMessage($message, 4096);
                if ($formattedMessage !== '') {
                    foreach ($this->recipients as $recipient) {
                        try {
                            $this->sendNotificationTo($recipient, $formattedMessage);
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
     * @param string $recipient
     * @param string $formattedMessage
     *
     * @throws \Exception|\Throwable
     */
    protected function sendNotificationTo($recipient, $formattedMessage)
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $recipient,
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
        $response = $this->post($url, 'application/json', $json);
        if ($response[0] >= 200 && $response[0] < 300) {
            return;
        }
        if ($response[0] === 401 || $response[0] === 404) {
            throw new RuntimeException(t('The Telegram token is wrong'));
        }
        $error = null;
        try {
            $decodedBody = json_decode($response[1], true);
            if (is_array($decodedBody) && !empty($decodedBody['description'])) {
                $error = new RuntimeException($decodedBody['description']);
            }
        } catch (Exception $_) {
        } catch (Throwable $_) {
        }
        throw $error ?: new RuntimeException(t('Server responded with a %s HTTP response code', $response[0]));
    }
}
