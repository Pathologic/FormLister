<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 15.05.2016
 * Time: 1:26
 */
if (!defined('MODX_BASE_PATH')) {die();}

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['login.user_blocked'] = 'Пользователь заблокирован. Обратитесь к администратору сайта.';
$_lang['login.user_failed'] = 'Неверное имя пользователя или пароль.';
return $_lang;