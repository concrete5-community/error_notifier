<?php

namespace Concrete\Package\ErrorNotifier;

defined('C5_EXECUTE') or die('Access denied.');

interface Options
{
    /**
     * Send a notification when uncaught exceptions occur?
     *
     * @return bool
     */
    public function isInterceptExceptions();

    /**
     * Send a notification when an event is written to the log/exceptions log?
     *
     * @return bool
     */
    public function isInterceptLogWrites();

    /**
     * Get the minimum level of the log/exceptions log, as defined by Monolog.
     * Applicable only for concrete5 v8 and ConcreteCMS from v9.0 to v9.3
     *
     * @see \Monolog\Logger::DEBUG
     * @see \Monolog\Logger::INFO
     * @see \Monolog\Logger::NOTICE
     * @see \Monolog\Logger::WARNING
     * @see \Monolog\Logger::ERROR
     * @see \Monolog\Logger::CRITICAL
     * @see \Monolog\Logger::ALERT
     * @see \Monolog\Logger::EMERGENCY
     *
     * @return int
     */
    public function getMinExceptionsLogLevel();

    /**
     * Should we remove the path of the root directory from stack traces?
     *
     * @return bool
     */
    public function isStripWebroot();

    /**
     * Send notifications to Telegram?
     *
     * @return bool
     */
    public function isTelegramEnabled();

    /**
     * Get the token of the bot to be used to send Telegram notifications.
     *
     * @return string
     */
    public function getTelegramToken();

    /**
     * Get the user IDs or chat IDs of the recipients.
     *
     * @return string[]
     */
    public function getTelegramRecipients();

    /**
     * Send notifications to Slack?
     *
     * @return bool
     */
    public function isSlackEnabled();

    /**
     * Get the bot user OAuth token.
     *
     * @return string
     */
    public function getSlackToken();

    /**
     * Get the channels where messages should be sent to.
     *
     * @return string[]
     */
    public function getSlackChannels();
}
