By default, the Error Notifier package can be configured via the Dashboard page, at `System & Settings` > `Environment` > `Error Notifications`.

Since version 1.2.0 you can also configure Error Notifier via some custom PHP code.

First of all, you need to create the code that provides the configuration.

This can be for example a file stored at `application/src/Concrete/ErrorNotifierOptions.php` with this code:

```
<?php

namespace Application\Concrete;

class ErrorNotifierOptions implements \Concrete\Package\ErrorNotifier\Options
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isInterceptExceptions()
     */
    public function isInterceptExceptions()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isInterceptLogWrites()
     */
    public function isInterceptLogWrites()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getMinExceptionsLogLevel()
     */
    public function getMinExceptionsLogLevel()
    {
        return \Monolog\Logger::NOTICE;
    }

    public function isStripWebroot()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isTelegramEnabled()
     */
    public function isTelegramEnabled()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getTelegramToken()
     */
    public function getTelegramToken()
    {
        return 'my-telegram-token';
    }

    /**
     * @return string[]
     */
    public function getTelegramRecipients()
    {
        return ['chat-id'];
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isSlackEnabled()
     */
    public function isSlackEnabled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getSlackToken()
     */
    public function getSlackToken()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getSlackChannels()
     */
    public function getSlackChannels()
    {
        return [];
    }
}
```

Then you have to have to add these lines in the `application/bootstrap/app.php` file:

```
$app->singleton(
    Concrete\Package\ErrorNotifier\Options::class,
    static function () {
        return app(Application\Concrete\ErrorNotifierOptions::class);
    }
);
```

Of course, you can also create a configuration class withing a custom package, and register it with `$this->app->singleton` in the `on_start()` method of your package controller.
