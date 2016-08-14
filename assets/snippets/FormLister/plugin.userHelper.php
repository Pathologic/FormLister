<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 25.06.2016
 * Time: 18:59
 */
$e = $modx->event;
include_once(MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
if ($e->name == 'OnWebLogin') {
    $user = new \modUsers($modx);
    $user->edit($userid);
    $user->set('lastlogin', time());
    $user->set('logincount', (int)$user->get('logincount') + 1);
    $user->save(false,false);
}
if ($e->name == 'OnWebPageInit' || $e->name == 'OnPageNotFound') {
    $user = new \modUsers($modx);
    if ($modx->getLoginUserID('web')) {
        if (isset($_REQUEST[$logoutKey])) {
            $user->logOut('WebLoginPE', true);
        }
    } else {
        $user->AutoLogin();
    }
}