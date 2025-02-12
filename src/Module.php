<?php
/**
 * Модуль веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\Signin;

/**
 * Модуль авторизации пользователя.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\Signin
 * @since 1.0
 */
class Module extends \Gm\Panel\Module\Module
{
    /**
     * {@inheritdoc}
     */
    public string $id = 'gm.be.signin';

    /**
     * {@inheritdoc}
     */
    public string|array $layout = 'signin';
}
