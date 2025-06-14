<?php

namespace Concrete\Package\ErrorNotifier\Controller\SinglePage\Dashboard\System\Environment;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\ErrorNotifier\Notifier;
use Concrete\Package\ErrorNotifier\Options;
use Exception;
use Monolog\Logger as MonologLogger;
use RuntimeException;

defined('C5_EXECUTE') or die('Access Denied.');

class ErrorNotifier extends DashboardPageController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function view()
    {
        $this->requireAsset('javascript', 'vue');
        $this->addHeaderItem(
            <<<'EOT'
<style>
[v-cloak] {
    display: none;
}
</style>
EOT
        );
        $this->set('canHookExceptionsLog', $this->canHookExceptionsLog());
        $this->set('exceptionsLogLevels', $this->getMonologDictionary());
        $this->set('sampleUncaughtExceptionMessage', $this->getSampleUncaughtExceptionMessage());
        $this->set('options', $this->app->make(Options::class));

        return null;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save()
    {
        if (!$this->token->validate('en-save')) {
            $this->error->add($this->token->getErrorMessage());
        } elseif (get_class($this->app->make(Options::class)) !== Options\Config::class) {
            $this->error->add(t("The configuration is defined by some custom code: you can't change it here."));
        } else {
            $interceptExceptions = !empty($this->request->request->get('interceptExceptions'));
            $canHookExceptionsLog = $this->canHookExceptionsLog();
            $interceptLogWrites = $canHookExceptionsLog && !empty($this->request->request->get('interceptLogWrites'));
            if ($canHookExceptionsLog) {
                $minExceptionsLogLevel = (int) $this->request->request->get('minExceptionsLogLevel');
                try {
                    MonologLogger::getLevelName($minExceptionsLogLevel);
                } catch (Exception $x) {
                    $this->error->add(t('Please specify the minimum log level'));
                }
            }
            $interceptLogWrites = $canHookExceptionsLog && !empty($this->request->request->get('interceptLogWrites'));
            $telegramEnabled = !empty($this->request->request->get('telegramEnabled'));
            if ($telegramEnabled) {
                $telegramToken = trim((string) $this->request->request->get('telegramToken', ''));
                if ($telegramToken === '') {
                    $this->error->add(t('Please specify the Telegram token'));
                }
                $telegramRecipients = preg_split('/\s+/', (string) $this->request->request->get('telegramRecipients', ''), -1, PREG_SPLIT_NO_EMPTY);
                if ($telegramRecipients === []) {
                    $this->error->add(t('Please specify at least one Telegram recipient'));
                }
            }
            $slackEnabled = !empty($this->request->request->get('slackEnabled'));
            if ($slackEnabled) {
                $slackToken = trim((string) $this->request->request->get('slackToken', ''));
                if ($slackToken === '') {
                    $this->error->add(t('Please specify the Slack Bot User OAuth Token'));
                }
                $slackChannels = [];
                foreach (preg_split('/\s+/', (string) $this->request->request->get('slackChannels', ''), -1, PREG_SPLIT_NO_EMPTY) as $slackChannel) {
                    $slackChannel = ltrim($slackChannel, '#');
                    if ($slackChannel !== '') {
                        $slackChannels[] = '#' . $slackChannel;
                    }
                }
                if ($slackChannels === []) {
                    $this->error->add(t('Please specify at least one Slack channel'));
                }
            }
        }
        if ($this->error->has()) {
            return $this->view();
        }
        $config = $this->app->make(Repository::class);
        $config->save('error_notifier::options.interceptExceptions', $interceptExceptions);
        $config->save('error_notifier::options.interceptLogWrites', $interceptLogWrites);
        if ($canHookExceptionsLog) {
            $config->save('error_notifier::options.minExceptionsLogLevel', $minExceptionsLogLevel);
        }
        $config->save('error_notifier::options.telegram.enabled', $telegramEnabled);
        if ($telegramEnabled) {
            $config->save('error_notifier::options.telegram.token', $telegramToken);
            $config->save('error_notifier::options.telegram.recipients', implode(' ', $telegramRecipients));
        }
        $config->save('error_notifier::options.slack.enabled', $slackEnabled);
        if ($slackEnabled) {
            $config->save('error_notifier::options.slack.token', $slackToken);
            $config->save('error_notifier::options.slack.channels', implode(' ', $slackChannels));
        }

        return $this->buildRedirect($this->action(''));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function tryNow()
    {
        if (!$this->token->validate('en-tryNow')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $what = trim((string) $this->request->request->get('what', ''));
        switch ($what) {
            case 'uncaughtException':
                throw new RuntimeException($this->getSampleUncaughtExceptionMessage());
            case 'log/exceptions':
                $this->app->make('log/exceptions')->emergency(t('Sample emergency log entry'));

                return $this->app->make(ResponseFactoryInterface::class)->json([
                    'message' => t('An error has been written to the %s log', 'log/exceptions'),
                ]);
            default:
                throw new UserMessageException(t('Invalid parameters received.'));
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function testTelegram()
    {
        if (!$this->token->validate('en-testTelegram')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $telegramToken = trim((string) $this->request->request->get('telegramToken', ''));
        if ($telegramToken === '') {
            throw new UserMessageException(t('Please specify the Telegram token'));
        }
        $telegramRecipients = preg_split('/\s+/', (string) $this->request->request->get('telegramRecipients', ''), -1, PREG_SPLIT_NO_EMPTY);
        if ($telegramRecipients === []) {
            throw new UserMessageException(t('Please specify at least one Telegram recipient'));
        }
        $options = $this->app->make(Options::class);
        $notifier = $this->app->make(Notifier\Telegram::class, [
            'token' => $telegramToken,
            'recipients' => $telegramRecipients,
            'stripWebroot' => $options->isStripWebroot(),
        ]);
        $errors = $notifier->notify(t('Sample message to check if sending a message to Telegram works.'));
        if ($errors !== []) {
            throw new UserMessageException($errors[0]->getMessage());
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function testSlack()
    {
        if (!$this->token->validate('en-testSlack')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $slackToken = trim((string) $this->request->request->get('slackToken', ''));
        if ($slackToken === '') {
            throw new UserMessageException(t('Please specify the Slack Bot User OAuth Token'));
        }
        $slackChannels = preg_split('/\s+/', (string) $this->request->request->get('slackChannels', ''), -1, PREG_SPLIT_NO_EMPTY);
        if ($slackChannels === []) {
            throw new UserMessageException(t('Please specify at least one Slack channel'));
        }
        $options = $this->app->make(Options::class);
        $notifier = $this->app->make(Notifier\Slack::class, [
            'token' => $slackToken,
            'channels' => $slackChannels,
            'stripWebroot' => $options->isStripWebroot(),
        ]);
        $errors = $notifier->notify(t('Sample message to check if sending a message to Slack works.'));
        if ($errors !== []) {
            throw new UserMessageException($errors[0]->getMessage());
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    protected function canHookExceptionsLog()
    {
        return $this->app->make('log/exceptions') instanceof MonologLogger;
    }

    /**
     * @return array
     */
    protected function getMonologDictionary()
    {
        $map = [
            MonologLogger::DEBUG => t('Debug'),
            MonologLogger::INFO => t('Info'),
            MonologLogger::NOTICE => t('Notice'),
            MonologLogger::WARNING => t('Warning'),
            MonologLogger::ERROR => t('Error'),
            MonologLogger::CRITICAL => t('Critical'),
            MonologLogger::ALERT => t('Alert'),
            MonologLogger::EMERGENCY => t('Emergency'),
        ];
        $result = [];
        foreach (MonologLogger::getLevels() as $name => $value) {
            $result[$value] = isset($map[$value]) ? $map[$value] : $name;
        }

        return $result;
    }

    protected function getSampleUncaughtExceptionMessage()
    {
        return t('Sample uncaught exception.');
    }
}
