<?php

use Concrete\Package\ErrorNotifier\Options\Config;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 *
 * @var bool $canHookExceptionsLog
 * @var array $exceptionsLogLevels
 * @var string $sampleUncaughtExceptionMessage
 * @var Concrete\Package\ErrorNotifier\Options $options
 */

?>
<form method="POST" action="<?= h((string) $view->action('save')) ?>" id="en-form" v-cloak>
    <?php $token->output('en-save') ?>
    <div v-if="readonlyOptions" class="alert alert-info">
        <?= t("The configuration is defined by some custom code: you can't change it here.") ?>
    </div>
    <fieldset>
        <legend><?= t('Options') ?></legend>
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <?= $form->checkbox('interceptExceptions', '1', $options->isInterceptExceptions(), ['v-model' => 'current.interceptExceptions', 'v-bind:disabled' => 'readonlyOptions']) ?>
                    <span>
                        <?= t('Send a notification when uncaught exceptions occur') ?>
                    </span>
                </label>
                <div class="small" v-bind:class="current.interceptExceptions ? '' : 'invisible'">
                    <a href="#" v-on:click.prevent="tryNow('uncaughtException')"><?= t('Throw an uncaught exception now') ?></a>
                </div>
            </div>
            <div class="checkbox" v-if="canHookExceptionsLog">
                <label>
                    <?= $form->checkbox('interceptLogWrites', '1', $options->isInterceptLogWrites(), ['v-model' => 'current.interceptLogWrites', 'v-bind:disabled' => 'readonlyOptions']) ?>
                    <span><?= t('Send a notification when an event is written to the %s log', '<code>log/exceptions</code>') ?></span>
                </label>
                <div class="small" v-bind:class="current.interceptLogWrites ? '' : 'invisible'">
                    <a href="#" v-on:click.prevent="tryNow('log/exceptions')"><?= t('Write to the %s log now', '<code>log/exceptions</code>') ?></a>
                </div>
            </div>
        </div>
        <div class="form-group" v-if="canHookExceptionsLog">
            <?= $form->label('minExceptionsLogLevel', t('Minimum level of the %s log', '<code>log/exceptions</code>')) ?>
            <?= $form->select('minExceptionsLogLevel', $exceptionsLogLevels, $options->getMinExceptionsLogLevel(), ['v-model' => 'current.minExceptionsLogLevel', 'v-bind:disabled' => 'readonlyOptions']) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend>Telegram</legend>
        <div class="small text-muted"><?= t('For detailed instructions %ssee here%s.', '<a href="https://market.concretecms.com/products/error-notifier/a3e557a6-d743-11ee-b9df-0a97d4ce16b9/telegram-configuration" target="_blank">', '</a>') ?></div>
        <div class="checkbox">
            <label>
                <?= $form->checkbox('telegramEnabled', '1', $options->isTelegramEnabled(), ['v-model' => 'current.telegramEnabled', 'v-bind:disabled' => 'readonlyOptions']) ?>
                <span>
                    <?= t('Enabled') ?>
                </span>
            </label>
        </div>
        <div v-if="current.telegramEnabled">
            <div class="form-group">
                <?= $form->label('telegramToken', t('Token')) ?>
                <?= $form->password('telegramToken', $options->getTelegramToken(), ['required' => 'required', 'v-model.trim' => 'current.telegramToken', 'ref' => 'telegramToken', 'v-bind:readonly' => 'readonlyOptions']) ?>
            </div>
            <div class="form-group">
                <?= $form->label('telegramRecipients', t('Recipients')) ?>
                <?= $form->textarea('telegramRecipients', implode("\n", $options->getTelegramRecipients()), ['required' => 'required', 'spellcheck' => 'false', 'v-model' => 'current.telegramRecipients', 'ref' => 'telegramRecipients', 'v-bind:readonly' => 'readonlyOptions']) ?>
                <div class="small text-muted">
                    <?= t('Separate multiple recipients with spaces or new lines.') ?>
                </div>
            </div>
            <button class="btn btn-default btn-secondary" v-bind:disabled="busy" v-on:click.prevent="testTelegram"><?= t('Try sending a Telegram message') ?></button>
        </div>
    </fieldset>

    <fieldset>
        <legend>Slack</legend>
        <div class="small text-muted"><?= t('For detailed instructions %ssee here%s.', '<a href="https://market.concretecms.com/products/error-notifier/a3e557a6-d743-11ee-b9df-0a97d4ce16b9/slack-configuration" target="_blank">', '</a>') ?></div>
        <div class="checkbox">
            <label>
                <?= $form->checkbox('slackEnabled', '1', $options->isSlackEnabled(), ['v-model' => 'current.slackEnabled', 'v-bind:disabled' => 'readonlyOptions']) ?>
                <span>
                    <?= t('Enabled') ?>
                </span>
            </label>
        </div>
        <div v-if="current.slackEnabled">
            <div class="form-group">
                <?= $form->label('slackToken', t('Bot User OAuth Token')) ?>
                <?= $form->password('slackToken', $options->getSlackToken(), ['required' => 'required', 'v-model.trim' => 'current.slackToken', 'v-bind:readonly' => 'readonlyOptions']) ?>
            </div>
            <div class="form-group">
                <?= $form->label('slackChannels', t('Channels')) ?>
                <?= $form->textarea('slackChannels', implode("\n", $options->getSlackChannels()), ['required' => 'required', 'spellcheck' => 'false', 'v-model' => 'current.slackChannels', 'v-bind:readonly' => 'readonlyOptions']) ?>
                <div class="small text-muted">
                    <?= t('Separate multiple channels with spaces or new lines.') ?>
                </div>
            </div>
            <button class="btn btn-default btn-secondary" v-bind:disabled="busy" v-on:click.prevent="testSlack"><?= t('Try sending a Slack message') ?></button>
        </div>
    </fieldset>
    <div class="ccm-dashboard-form-actions-wrapper" v-if="!readonlyOptions">
        <div class="ccm-dashboard-form-actions">
            <div class="float-end pull-right">
                <input type="submit" class="btn btn-primary " value="<?= t('Save') ?>" />
            </div>
        </div>
    </div>
</form>
<script>
$(document).ready(function() {

new Vue({
    el: '#en-form',
    data() {
        <?php
        $savedData = json_encode([
            'interceptExceptions' => $options->isInterceptExceptions(),
            'interceptLogWrites' => $options->isInterceptLogWrites(),
            'minExceptionsLogLevel' => $canHookExceptionsLog ? $options->getMinExceptionsLogLevel() : null,
            'telegramEnabled' => $options->isTelegramEnabled(),
            'telegramToken' => $options->getTelegramToken(),
            'telegramRecipients' => implode("\n", $options->getTelegramRecipients()),
            'slackEnabled' => $options->isSlackEnabled(),
            'slackToken' => $options->getSlackToken(),
            'slackChannels' => implode("\n", $options->getSlackChannels()),
        ]);
        ?>
        return {
            canHookExceptionsLog: <?= json_encode($canHookExceptionsLog) ?>,
            readonlyOptions: <?= json_encode(get_class($options) !== Config::class) ?>,
            busy: false,
            stored: <?= $savedData ?>,
            current: <?= $savedData ?>,
        };
    },
    created() {
        Object.keys(this.current).forEach((key) => {
            if (typeof this.current[key] === 'boolean') {
                this.current[key] = $(`#${key}`).is(':checked');
            } else {
                this.current[key] = $(`#${key}`).val();
            }
        });
    },
    computed: {
        canTryNow() {
            return !Object.keys(this.current).some((key) => {
                let stored = this.stored[key];
                let current = this.current[key];
                switch (key) {
                    case 'minExceptionsLogLevel':
                        stored = parseInt(stored);
                        current = parseInt(current);
                        break;
                    case 'telegramRecipients':
                    case 'slackChannels':
                        stored = $.trim(stored.replace(/\s/g, ' '));
                        current = $.trim(stored.replace(/\s/g, ' '));
                        break;
                }
                return stored !== current;
            });
        },
    },
    methods: {
        tryNow(what) {
            if (this.busy) {
                return;
            }
            if (!this.canTryNow) {
                ConcreteAlert.error({
                    message: <?= json_encode(t('You need to save the settings before testing them.')) ?>
                });
                return;
            }
            const send = {
                url: <?= json_encode($view->action('tryNow')) ?>,
                complete: () => {
                    this.busy = false;
                    $.fn.dialog.hideLoader();
                },
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('en-tryNow')) ?>,
                    what,
                },
            };
            switch (what) {
                case 'uncaughtException':
                    send.error = function(xhr, status, err) {
                        if (xhr?.responseJSON?.error?.message === <?= json_encode($sampleUncaughtExceptionMessage) ?> || xhr?.responseJSON?.error?.message === <?= json_encode(t('An error occurred while processing this request.')) ?>) {
                            ConcreteAlert.info({
                                message: <?= json_encode(t('The exception has been thrown')) ?>
                            });
                        } else {
                            ConcreteAlert.error({
                                message: ConcreteAjaxRequest.renderErrorResponse(xhr, false)
                            });
                        }
                    };
                    break;
                case 'log/exceptions':
                    send.success = function(data) {
                        ConcreteAlert.info({
                            message: data.message
                        });
                    };
                    break;
            }
            this.busy = true;
            $.concreteAjax(send);
        },
        testTelegram() {
            if (this.busy) {
                return;
            }
            const data = {
                <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('en-testTelegram')) ?>,
                telegramToken: this.current.telegramToken,
                telegramRecipients: $.trim(this.current.telegramRecipients.replace(/\s+/g, ' ')),
            };
            if (data.telegramToken === '') {
                ConcreteAlert.error({
                    message: <?= json_encode(t('Please specify the Telegram token')) ?>
                });
                document.getElementById('telegramToken').focus();
                return;
            }
            if (data.telegramRecipients === '') {
                ConcreteAlert.error({
                    message: <?= json_encode(t('Please specify at least one Telegram recipient')) ?>
                });
                document.getElementById('telegramRecipients').focus();
                return;
            }
            this.busy = true;
            $.concreteAjax({
                url: <?= json_encode($view->action('testTelegram')) ?>,
                complete: () => {
                    this.busy = false;
                    $.fn.dialog.hideLoader();
                },
                data,
                success: () => {
                    ConcreteAlert.info({
                        message: <?= json_encode(t('The test message has been successfully sent')) ?>
                    });
                },
            });
        },
        testSlack() {
            if (this.busy) {
                return;
            }
            const data = {
                <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('en-testSlack')) ?>,
                slackToken: this.current.slackToken,
                slackChannels: $.trim(this.current.slackChannels.replace(/\s+/g, ' ')),
            };
            if (data.slackToken === '') {
                ConcreteAlert.error({
                    message: <?= json_encode(t('Please specify the Slack Bot User OAuth Token')) ?>
                });
                document.getElementById('slackToken').focus();
                return;
            }
            if (data.slackChannels === '') {
                ConcreteAlert.error({
                    message: <?= json_encode(t('Please specify at least one Slack channel')) ?>
                });
                document.getElementById('slackChannels').focus();
                return;
            }
            this.busy = true;
            $.concreteAjax({
                url: <?= json_encode($view->action('testSlack')) ?>,
                complete: () => {
                    this.busy = false;
                    $.fn.dialog.hideLoader();
                },
                data,
                success: () => {
                    ConcreteAlert.info({
                        message: <?= json_encode(t('The test message has been successfully sent')) ?>
                    });
                },
            });
        },
    },
});

});
</script>
