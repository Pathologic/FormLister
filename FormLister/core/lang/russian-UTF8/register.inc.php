<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 15.05.2016
 * Time: 3:26
 */
if (!defined('MODX_BASE_PATH')) {die();}

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['register.email_in_use'] = 'Этот email уже используется.';
$_lang['register.username_in_use'] = 'Это имя пользователя уже занято.';
$_lang['register.registration_failed'] = 'Не удалось зарегистрировать пользователя.';
return $_lang;