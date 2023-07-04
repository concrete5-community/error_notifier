<?php

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

$app = Application::getFacadeApplication();
$urlResolver = $app->make(ResolverManagerInterface::class);

$page = Page::getByPath('/dashboard/system/environment/telegram-errors');
echo t('In order to use this package, please go to the %s dashboard page.', '<a href="' . h((string) $urlResolver->resolve([$page])) . '">' . t($page->getCollectionName()) . '</a>');
