/**
 * userLogout
 * 
 * Plugin to log out user
 * 
 * @category    plugin
 * @internal    @properties &logoutKey=Request key;text;logout
 * @internal    @events OnWebPageInit
**/
$e = $modx->event;
include_once(MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
if ($e->name == 'OnWebPageInit' && $modx->getLoginUserID('web')) {
    if (isset($_REQUEST[$logoutKey])) {
        $user = new modUsers($modx);
        $user->logOut('WebLoginPE',true);
    }
}