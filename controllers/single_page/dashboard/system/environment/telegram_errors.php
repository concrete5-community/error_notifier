<?php

namespace Concrete\Package\TelegramErrors\Controller\SinglePage\Dashboard\System\Environment;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\TelegramErrors\ErrorNotifier;
use Exception;
use Monolog\Logger as MonologLogger;
use RuntimeException;

defined('C5_EXECUTE') or die('Access Denied.');

class TelegramErrors extends DashboardPageController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function view()
    {
        $config = $this->app->make(Repository::class);
        $canHookExceptionsLog = $this->canHookExceptionsLog();
        $this->set('hookWhoops', (bool) $config->get('telegram_errors::options.whoops', false));
        $this->set('hookExceptionsLog', (bool) $config->get('telegram_errors::options.exceptionsLog', false));
        $this->set('canHookExceptionsLog', $canHookExceptionsLog);
        $this->set('minExceptionsLogLevel', (int) $config->get('telegram_errors::options.minExceptionsLogLevel', 250));
        if ($canHookExceptionsLog) {
            $this->set('exceptionsLogLevels', $this->getMonologDictionary());
        }
        $this->set('tg_token', (string) $config->get('telegram_errors::options.token', ''));
        $this->set('tg_recipients', preg_split('/\s+/', (string) $config->get('telegram_errors::options.recipients', ''), -1, PREG_SPLIT_NO_EMPTY));

        return null;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save()
    {
        if (!$this->token->validate('tg-save')) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $canHookExceptionsLog = $this->canHookExceptionsLog();
            if ($canHookExceptionsLog) {
                $minExceptionsLogLevel = (int) $this->request->request->get('minExceptionsLogLevel');
                try {
                    MonologLogger::getLevelName($minExceptionsLogLevel);
                } catch (Exception $x) {
                    $this->error->add(t('Please specify the minimum log level'));
                }
            }
            $hookWhoops = !empty($this->request->request->get('hookWhoops'));
            $hookExceptionsLog = $canHookExceptionsLog && !empty($this->request->request->get('hookExceptionsLog'));
            $tgToken = trim((string) $this->request->request->get('tg_token', ''));
            $tgRecipients = preg_split('/\s+/', (string) $this->request->request->get('tg_recipients', ''), -1, PREG_SPLIT_NO_EMPTY);
            if ($hookWhoops || $hookExceptionsLog) {
                if ($tgToken === '') {
                    $this->error->add(t('If you enable some of the notifications, you need to specify the Telegram token'));
                }
                if ($tgRecipients === []) {
                    $this->error->add(t('If you enable some of the notifications, you need to specify the Telegram recipients'));
                }
            }
        }
        if ($this->error->has()) {
            return $this->view();
        }
        $config = $this->app->make(Repository::class);
        $config->save('telegram_errors::options.whoops', $hookWhoops);
        $config->save('telegram_errors::options.exceptionsLog', $hookExceptionsLog);
        if ($canHookExceptionsLog) {
            $config->save('telegram_errors::options.minExceptionsLogLevel', $minExceptionsLogLevel);
        }
        $config->save('telegram_errors::options.token', $tgToken);
        $config->save('telegram_errors::options.recipients', implode("\n", $tgRecipients));
        $this->flash('success', t('The settings have been updated.'));

        return $this->buildRedirect($this->action(''));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function tryNow()
    {
        if (!$this->token->validate('tg-tryNow')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $what = trim((string) $this->request->request->get('what', ''));
        switch ($what) {
            case 'exception':
                throw new RuntimeException(t('Sample uncaught exception.'));
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
        if (!$this->token->validate('tg-testTelegram')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $tgToken = trim((string) $this->request->request->get('tg_token', ''));
        if ($tgToken === '') {
            throw new UserMessageException(t('Please specify the token'));
        }
        $tgRecipients = preg_split('/\s+/', (string) $this->request->request->get('tg_recipients', ''), -1, PREG_SPLIT_NO_EMPTY);
        if ($tgRecipients === []) {
            throw new UserMessageException(t('Please specify at least one recipient'));
        }
        $notifier = $this->app->make(ErrorNotifier::class, [
            'tgToken' => $tgToken,
            'stripWebroot' => (bool) $this->app->make(Repository::class)->get('telegram_errors::options.stripWebroot', true),
        ]);
        $error = $notifier->notify($tgRecipients, t('Sample message to check if sending a message to Telegram works.'), true);
        if ($error !== null) {
            throw new UserMessageException($error->getMessage());
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
}
