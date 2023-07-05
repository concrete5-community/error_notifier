<?php

namespace Concrete\Package\TelegramErrors;

use Concrete\Core\Application\Application;
use Concrete\Core\Http\Client\Client;
use Concrete\Core\Http\Request;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use Throwable;
use Zend\Http\Client as ZendClient;
use Zend\Http\Request as ZendRequest;

defined('C5_EXECUTE') or die('Access denied.');

class ErrorNotifier
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $tgToken;

    /**
     * @var bool
     */
    protected $stripWebroot;

    /**
     * @param string $tgToken
     * @param bool $stripWebroot
     */
    public function __construct(Application $app, $tgToken, $stripWebroot)
    {
        $this->app = $app;
        $this->tgToken = $tgToken;
        $this->stripWebroot = $stripWebroot;
    }

    /**
     * @param string[] $recipients
     * @param \Exception|\Throwable|string $message
     * @param bool $ignoreExceptions
     *
     * @return \Exception|\Throwable|null if an error occurs and $ignoreExceptions is true
     */
    public function notify(array $recipients, $message, $ignoreExceptions = true)
    {
        try {
            $this->sendNotification($recipients, $message);
        } catch (Exception $x) {
            if ($ignoreExceptions) {
                return $x;
            }
            throw $x;
        } catch (Throwable $x) {
            if ($ignoreExceptions) {
                return $x;
            }
            throw $x;
        }
    }

    /**
     * @param string[] $recipients
     * @param \Exception|\Throwable|string $message
     *
     * @throws \Exception|\Throwable
     */
    protected function sendNotification(array $recipients, $message)
    {
        if (array_filter($recipients) === []) {
            return;
        }
        $formattedMessage = $this->formatMessage($message);
        if ($formattedMessage === '') {
            return;
        }
        if ($this->stripWebroot) {
            $formattedMessage = $this->removeWebrootFromMessage($formattedMessage);
        }
        $client = $this->app->make(Client::class);
        $error = null;
        foreach ($recipients as $recipient) {
            if (!$recipient) {
                continue;
            }
            $this->sendNotificationTo($client, $recipient, $formattedMessage);
            try {
            } catch (Exception $x) {
                $error = $error ?: $x;
            } catch (Throwable $x) {
                $error = $error ?: $x;
            }
        }
        if ($error !== null) {
            throw $error;
        }
    }

    protected function sendNotificationTo(Client $client, $recipient, $formattedMessage)
    {
        $url = "https://api.telegram.org/bot{$this->tgToken}/sendMessage";
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
        if ($client instanceof GuzzleClient) { // ConcreteCMS v9
            $this->postWithGuzzle($client, $url, $json);
        } elseif ($client instanceof ZendClient) { // concrete5 v8
            $this->postWithZend($client, $url, $json);
        } else {
            throw new RuntimeException(t('Unknown HTTP client: %s', get_class($client)));
        }
    }

    /**
     * @param string $url
     * @param string $json
     */
    protected function postWithGuzzle(GuzzleClient $client, $url, $json)
    {
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($json),
            ],
            'body' => $json,
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            $error = null;
            try {
                $decodedBody = json_decode($response->getBody(), true);
                if (is_array($decodedBody) && !empty($decodedBody['description'])) {
                    $error = new RuntimeException($decodedBody['description']);
                }
            } catch (Exception $_) {
            } catch (Throwable $_) {
            }
            throw $error ?: new RuntimeException($response->getReasonPhrase());
        }
    }

    /**
     * @param string $url
     * @param string $json
     */
    protected function postWithZend(ZendClient $client, $url, $json)
    {
        $client->reset();
        $request = new ZendRequest();
        $request->setUri($url)->setMethod('POST')->setContent($json);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->getHeaders()->addHeaderLine('Content-Length', strlen($json));
        $response = $client->send($request);
        if (!$response->isOk()) {
            $error = null;
            try {
                $decodedBody = json_decode($response->getBody(), true);
                if (is_array($decodedBody) && !empty($decodedBody['description'])) {
                    $error = new RuntimeException($decodedBody['description']);
                }
            } catch (Exception $_) {
            } catch (Throwable $_) {
            }
            throw $error ?: new RuntimeException($response->getReasonPhrase());
        }
    }

    /**
     * Format a message to be sent to Telegram.
     *
     * @param string|\Exception|\Throwable $message
     *
     * @return string
     */
    protected function formatMessage($message)
    {
        $url = $this->getRequestURL();
        if ($message instanceof Exception || $message instanceof Throwable) {
            $result = trim($message->getMessage());
            if ($result === '') {
                $result = get_class($message);
            }
            if ($url !== '') {
                $result .= "\n\nURL: {$url}";
            }
            if ($message->getFile()) {
                $result .= "\n\nFile: " . $message->getFile();
                if ($message->getLine()) {
                    $result .= "\nLine: " . $message->getLine();
                }
            }
            if (method_exists($message, 'getTraceAsString')) {
                $result .= "\n\nTrace: " . $message->getTraceAsString();
            }
        } else {
            $result = trim((string) $message);
            if ($result === '') {
                return '';
            }
            if ($url !== '') {
                $result .= "\n\nURL: {$url}";
            }
        }
        if (mb_strlen($result) > 4096) {
            $result = trim(mb_substr($result, 0, 4096));
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getRequestURL()
    {
        if ($this->app->isRunThroughCommandLineInterface()) {
            return '';
        }
        try {
            return $this->app->make(Request::class)->getUri();
        } catch (Exception $x) {
        } catch (Throwable $x) {
        }

        return '';
    }

    /**
     * @param string $formattedMessage
     *
     * @return string
     */
    protected function removeWebrootFromMessage($formattedMessage)
    {
        $replacements = [
            rtrim(str_replace(DIRECTORY_SEPARATOR, '/', DIR_BASE), '/') . '/' => '[webroot]/',
        ];
        if (DIRECTORY_SEPARATOR === '/') {
            return strtr($formattedMessage, $replacements);
        }
        $slashes = '[' . preg_quote('/\\', '/') . ']';
        foreach ($replacements as $search => $replacement) {
            $rxSearch = '/'
                . implode(
                    $slashes,
                    array_map(
                        static function ($chunk) {
                            return preg_quote($chunk, '/');
                        },
                        explode('/', rtrim($search, '/'))
                    )
                )
                . '\b/'
            ;
            $formattedMessage = preg_replace($rxSearch, rtrim($replacement, '/'), $formattedMessage);
        }

        return $formattedMessage;
    }
}
