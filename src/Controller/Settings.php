<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\Signin\Controller;

use Gm;
use Gm\Panel\Helper\ExtCombo;
use Gm\Panel\Widget\SettingsWindow;
use Gm\Panel\Controller\ModuleSettingsController;

/**
 * Контроллер настроек модуля.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\Signin\Controller
 * @since 1.0
 */
class Settings extends ModuleSettingsController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'Settings';

    /**
     * {@inheritdoc}
     */
    public function createWidget(): SettingsWindow
    {
        $mailTemplates = [];

        /** @var \Gm\Theme\Info\ViewsInfo $viewsInfo */
        $viewsInfo = Gm::$app->createBackendTheme()->getViewsInfo();
        if ($viewsInfo->load()) {
            /** @var array $mailTemplates Шаблоны писем */
            $mailTemplates = $viewsInfo->find(['type' => 'mail'], true, ['view', 'description']);
        }
        array_unshift($mailTemplates, ['default', $this->t('default')]);

        /** @var SettingsWindow $window */
        $window = parent::createWidget();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $window->width = 500;
        $window->height = 700;
        $window->responsiveConfig = [
            'height < 700' => ['height' => '99%'],
            'width < 500' => ['width' => '99%'],
        ];

        // панель формы (Gm.view.form.Panel GmJS)
        $window->form->autoScroll = true;
        $window->form->bodyPadding = 0;
        $window->form->controller = 'gm-be-signin-settings';
        // шаблон для текущей версии языка
        $window->form->forceLocalize = true;
        $window->form->useLocalize = true;
        $window->form->loadJSONFile(
            '/settings',
            'items',
            [
                // шаблоны писем для получаталей уведомлений
                '@templateMail' => ExtCombo::themeViews(
                    '#for notification recipients', 
                    'templateMail', 
                    BACKEND, 
                    ['type' => 'mail'],
                    ['view' => 'default', 'description' => $this->t('default')],
                    ['tooltip' => '#This is a generic layout that is used to send messages to notification recipients']
                ),
                // шаблоны писем для пользователей
                '@templateUserMail' => ExtCombo::themeViews(
                    '#for users', 
                    'templateUserMail', 
                    BACKEND, 
                    ['type' => 'mail'],
                    ['view' => 'default', 'description' => $this->t('default')],
                    ['tooltip' => '#This is a generic layout that is used to send messages to users']
                )
            ]
        );

        /** @var \Gm\Panel\Http\Response $response */
        $response = $this->getResponse();
        $response
            ->meta
                ->add('jsPath', ['Gm.be.signin', $this->module->getRequireUrl() . '/js'])
                ->add('requires', 'Gm.be.signin.SettingsController')
                ->add('requires', 'Gm.view.form.field.Field')
                ->add('css', $window->cssSrc('/signin.css'));
        return $window;
    }
}
