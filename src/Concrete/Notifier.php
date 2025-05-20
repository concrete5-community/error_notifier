<?php

namespace Concrete\Package\ErrorNotifier;

use Concrete\Core\Application\Application;
use Concrete\Core\Http\Client\Client;
use Concrete\Core\Http\Request;
use Concrete\Core\User\User;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use Throwable;
use Zend\Http\Client as ZendClient;
use Zend\Http\Request as ZendRequest;

defined('C5_EXECUTE') or die('Access denied.');

abstract class Notifier
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * @var bool
     */
    protected $stripWebroot;

    /**
     * @var \Concrete\Core\Http\Client\Client|null
     */
    private $client;

    /**
     * @param string $tgToken
     * @param bool $stripWebroot
     */
    protected function __construct(Application $app, $stripWebroot)
    {
        $this->app = $app;
        $this->stripWebroot = $stripWebroot;
    }

    /**
     * @param \Exception|\Throwable|string $message
     *
     * @return \Exception[]|\Throwable[]
     */
    abstract public function notify($message);

    /**
     * Format a message to be sent.
     *
     * @param string|\Exception|\Throwable $message
     * @param int|null $maxLength
     *
     * @return string
     */
    protected function formatMessage($message, $maxLength = null)
    {
        $url = $this->getRequestURL();
        $userDescription = $this->getCurrentUserDescription();
        if ($message instanceof Exception || $message instanceof Throwable) {
            $result = trim($message->getMessage());
            if ($result === '') {
                $result = get_class($message);
            }
            $result .= "\n\nUser: {$userDescription}";
            if ($url !== '') {
                $result .= "\n\nURL: {$url}";
            }
            if ($message->getFile()) {
                $result .= "\n\nFile: " . $message->getFile();
                if ($message->getLine()) {
                    $result .= "\nLine: " . $message->getLine();
                }
            }
            $result .= "\n\nTrace: " . $message->getTraceAsString();
        } else {
            $result = trim((string) $message);
            if ($result === '') {
                return '';
            }
            $result .= "\n\nUser: {$userDescription}";
            if ($url !== '') {
                $result .= "\n\nURL: {$url}";
            }
        }
        if ($this->stripWebroot) {
            $result = $this->removeWebrootFromMessage($result);
        }
        if ($maxLength !== null && mb_strlen($result) > $maxLength) {
            $result = trim(mb_substr($result, 0, $maxLength));
        }

        return $result;
    }

    /**
     * @param string $url
     * @param string $contentType
     * @param string $body
     * @param string $authorization
     *
     * @return array First element: response status code; second element: response body
     */
    protected function post($url, $contentType, $body, $authorization = '')
    {
        $client = $this->getClient();
        if ($client instanceof GuzzleClient) { // ConcreteCMS v9
            return $this->postWithGuzzle($client, $url, $contentType, $body, $authorization);
        }
        if ($client instanceof ZendClient) { // concrete5 v8
            return $this->postWithZend($client, $url, $contentType, $body, $authorization);
        }

        throw new RuntimeException(t('Unknown HTTP client: %s', get_class($client)));
    }

    /**
     * @return \Concrete\Core\Http\Client\Client
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = $this->app->make(Client::class);
        }

        return $this->client;
    }

    /**
     * @param string $url
     * @param string $contentType
     * @param string $body
     * @param string $authorization
     *
     * @return array First element: response status code; second element: response body
     */
    private function postWithGuzzle(GuzzleClient $client, $url, $contentType, $body, $authorization)
    {
        $options = [
            'headers' => [
                'Content-Type' => $contentType,
                'Content-Length' => strlen($body),
            ],
            'body' => $body,
            'http_errors' => false,
        ];
        if ($authorization !== '') {
            $options['headers']['Authorization'] = $authorization;
        }
        $response = $client->post($url, $options);

        return [$response->getStatusCode(), $response->getBody()];
    }

    /**
     * @param string $url
     * @param string $contentType
     * @param string $body
     * @param string $authorization
     *
     * @return array First element: response status code; second element: response body
     */
    private function postWithZend(ZendClient $client, $url, $contentType, $body, $authorization)
    {
        $client->reset();
        $request = new ZendRequest();
        if ($authorization !== '') {
            $request->getHeaders()->addHeaderLine('Authorization', $authorization);
        }
        $request->setUri($url)->setMethod('POST')->setContent($body);
        $request->getHeaders()->addHeaderLine('Content-Type', $contentType);
        $request->getHeaders()->addHeaderLine('Content-Length', strlen($body));
        $response = $client->send($request);

        return [$response->getStatusCode(), $response->getBody()];
    }

    /**
     * @return string
     */
    private function getRequestURL()
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
     * @return string
     */
    private function getCurrentUserDescription()
    {
        if ($this->app->isRunThroughCommandLineInterface()) {
            return 'CLI';
        }
        try {
            $user = $this->app->make(User::class);
            if ($user->isRegistered()) {
                $userID = (int) $user->getUserID();
                if ($userID !== 0) {
                    return "ID {$userID}";
                }
            }
        } catch (Exception $x) {
        } catch (Throwable $x) {
        }

        return 'Guest';
    }

    /**
     * @param string $formattedMessage
     *
     * @return string
     */
    private function removeWebrootFromMessage($formattedMessage)
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
