<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var bool $_bookmarked
 * @var Concrete\Package\TelegramErrors\Controller\SinglePage\Dashboard\System\Environment\TelegramErrors $controller
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 * @var bool $hookWhoops
 * @var bool $hookExceptionsLog
 * @var bool $canHookExceptionsLog
 * @var int $minExceptionsLogLevel
 * @var array $exceptionsLogLevels (set if $canHookExceptionsLog is true)
 * @var string[] $tg_token
 * @var string[] $tg_recipients
 */

?>
<form method="POST" action="<?= h((string) $view->action('save')) ?>">
    <?php $token->output('tg-save') ?>

    <div class="form-group">
        <?= $form->label('', t('Options')) ?>
        <div class="checkbox">
            <label>
                <?= $form->checkbox('hookWhoops', '1', $hookWhoops) ?>
                <span>
                    <?= t('Send a notification when uncaught exceptions occur') ?>
                </span>
            </label>
            <?php
            if ($hookWhoops) {
                ?>
                <span class="small"><br /><a href="javascript:void(0)" class="tg-try-now" data-what="exception"><?= t('Throw an uncaught exception now') ?></a></span>
                <?php
            }
            ?>
        </div>
        <?php
        if ($canHookExceptionsLog) {
            ?>
            <br /><div class="checkbox">
                <label>
                    <?= $form->checkbox('hookExceptionsLog', '1', $hookExceptionsLog) ?>
                    <span><?= t('Send a notification when an event is written to the %s log', '<code>log/exceptions</code>') ?></span>
                </label>
                <?php
                if ($hookExceptionsLog) {
                    ?>
                    <span class="small"><br /><a href="javascript:void(0)" class="tg-try-now" data-what="log/exceptions"><?= t('Write to the %s log now', '<code>log/exceptions</code>') ?></a></span>
                    <?php
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>

    <?php
    if ($canHookExceptionsLog) {
        ?>
        <div class="form-group">
            <?= $form->label('minExceptionsLogLevel', t('Minimum level of the %s log', '<code>log/exceptions</code>')) ?>
            <?= $form->select('minExceptionsLogLevel', $exceptionsLogLevels, $minExceptionsLogLevel) ?>
        </div>
        <?php
    }
    ?>

    <div class="form-group">
        <?= $form->label('tg_token', t('Telegram token')) ?>
        <?= $form->password('tg_token', $tg_token) ?>
        <div class="small text-muted"><?= t('For detailed instructions %ssee here%s.', '<a href="https://marketplace.concretecms.com/marketplace/addons/notify-via-telegram/getting-telegram-tokens-and-telegram-recipients/" target="_blank">', '</a>') ?></div>
    </div>

    <div class="form-group">
        <?= $form->label('tg_recipients', t('Telegram recipients')) ?>
        <?= $form->textarea('tg_recipients', $tg_recipients === [] ? '' : (implode("\n", $tg_recipients) . "\n")) ?>
        <div class="small text-muted">
            <?= t('Separate multiple recipients with spaces or new lines.') ?>
            <?= t('For detailed instructions %ssee here%s.', '<a href="https://marketplace.concretecms.com/marketplace/addons/notify-via-telegram/getting-telegram-tokens-and-telegram-recipients/" target="_blank">', '</a>') ?>
        </div>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="float-end pull-right">
                <button type="button" class="btn btn-secondary" id="te-test-telegram"><?= t('Try sending a Telegram message') ?></button>
                <input type="submit" class="btn btn-primary " value="<?= t('Save') ?>" />
            </div>
        </div>
    </div>
</form>
<script>
$(document).ready(function() {

var somethingChanged = false;
$('#tg_token,#tg_recipients').on('input', function() {
    somethingChanged = true;
});

$('.tg-try-now').on('click', function() {
    if (somethingChanged) {
        ConcreteAlert.info({
            message: <?= json_encode(t('You need to save the settings before testing them.')) ?>
        });
        return;
    }
    var what = $(this).data('what');
    var send = {
        url: <?= json_encode($view->action('tryNow')) ?>,
        data: {
            <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('tg-tryNow')) ?>,
            what: what
        }
    };
    switch (what) {
        case 'exception':
            send.error = function(xhr, status, err) {
                if (xhr.status >= 500) {
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
    $.concreteAjax(send);
});

$('#te-test-telegram').on('click', function() {
    var $token = $('#tg_token');
    var token = $.trim($token.val());
    if (token === '') {
        $token.focus();
        ConcreteAlert.error({
            message: <?= json_encode(t('Please specify the token')) ?>
        });
        return;
    }
    var $recipients = $('#tg_recipients');
    var recipients = $recipients.val().split(/\s+g/).filter(function (recipient) {
        return recipient !== '';
    });
    if (recipients.length === 0) {
        $recipients.focus();
        ConcreteAlert.error({
            message: <?= json_encode(t('Please specify at least one recipient')) ?>
        });
        return;
    }
    $('#te-test-telegram').attr('disabled', 'disabled');
    $.concreteAjax({
        url: <?= json_encode($view->action('testTelegram')) ?>,
        data: {
            <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('tg-testTelegram')) ?>,
            tg_token: token,
            tg_recipients: recipients.join('\n'),
        },
        success: function(data) {
            $('#te-test-telegram').attr('disabled', 'disabled');
            ConcreteAlert.info({
                message: <?= json_encode(t('The test message has been successfully sent')) ?>
            });
        },
        complete: function(xhr, status) {
            $('#te-test-telegram').removeAttr('disabled');
            $.fn.dialog.hideLoader();
        }
    });
});

});
</script>