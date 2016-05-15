<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 17.01.2016
 * Time: 17:45
 */
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}
if (!isset($formid)) return;

$out = '';
$FLDir = MODX_BASE_PATH . 'assets/snippets/FormLister/';
if (isset($controller)) {
    preg_match('/^(\w+)$/iu', $controller, $controller);
    $controller = $controller[1];
} else {
    $controller = "Form";
}
$classname = '\FormLister\\'.$controller;

$dir = isset($dir) ? MODX_BASE_PATH.$dir : $FLDir . "core/controller/";
if ($classname != 'FormLister' && file_exists($dir . $controller . ".php") && !class_exists($classname, false)) {
    require_once($dir . $controller . ".php");
}

if (!isset($langDir)) $modx->event->params['langDir'] = 'assets/snippets/FormLister/core/lang/';

if (class_exists($classname, false) && $classname != 'FormLister') {
    $FormLister = new $classname($modx, $modx->event->params, $_time);
    if (!$FormLister->getFormId()) return;
    $FormLister->initForm();
    $out = $FormLister->render();
}
return $out;