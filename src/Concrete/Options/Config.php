<?php

namespace Concrete\Package\ErrorNotifier\Options;

defined('C5_EXECUTE') or die('Access denied.');

use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\ErrorNotifier\Options;

final class Config implements Options
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isInterceptExceptions()
     */
    public function isInterceptExceptions()
    {
        return (bool) $this->config->get('error_notifier::options.interceptExceptions', false);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isInterceptLogWrites()
     */
    public function isInterceptLogWrites()
    {
        return (bool) $this->config->get('error_notifier::options.interceptLogWrites', false);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getMinExceptionsLogLevel()
     */
    public function getMinExceptionsLogLevel()
    {
        return (int) $this->config->get('error_notifier::options.minExceptionsLogLevel', \Monolog\Logger::NOTICE);
    }

    public function isStripWebroot()
    {
        return (bool) $this->config->get('error_notifier::options.stripWebroot', true);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isTelegramEnabled()
     */
    public function isTelegramEnabled()
    {
        return (bool) $this->config->get('error_notifier::options.telegram.enabled', false);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getTelegramToken()
     */
    public function getTelegramToken()
    {
        return (string) $this->config->get('error_notifier::options.telegram.token', '');
    }

    /**
     * @return string[]
     */
    public function getTelegramRecipients()
    {
        return preg_split('/\s+/', (string) $this->config->get('error_notifier::options.telegram.recipients', ''), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::isSlackEnabled()
     */
    public function isSlackEnabled()
    {
        return (bool) $this->config->get('error_notifier::options.slack.enabled', false);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getSlackToken()
     */
    public function getSlackToken()
    {
        return (string) $this->config->get('error_notifier::options.slack.token', '');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\ErrorNotifier\Options::getSlackChannels()
     */
    public function getSlackChannels()
    {
        return preg_split('/\s+/', (string) $this->config->get('error_notifier::options.slack.channels', ''), -1, PREG_SPLIT_NO_EMPTY);
    }
}
