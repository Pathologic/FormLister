<?php namespace FormLister;

/**
 * Контроллер для авторизации пользователя
 */
if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Login extends Core
{
    public $user = null;
    
    public  function __construct($modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        $this->user = new \modUsers($this->modx);
        $this->lexicon->loadLang('login');
    }

    public function render() {
        if ($uid = $this->modx->getLoginUserID('web')) {
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('skipTpl',$this->lexicon->getMsg('login.default_skipTpl'));
            $this->setFormStatus(true);
        };
        return parent::render();
    }

    public function process() {
        $login = $this->getField($this->getCFGDef('loginField','username'));
        $password = $this->getField($this->getCFGDef('passwordField','password'));
        $remember = $this->getField($this->getCFGDef('rememberField','rememberme'));
        if ($this->user->checkBlock($login)) {
            $this->addMessage($this->lexicon->getMsg('login.user_blocked'));
            return;
        }
        $auth = $this->user->testAuth($login,$password,false,true);
        if (!$auth) {
            $this->addMessage($this->lexicon->getMsg('login.user_failed'));
            return;
        }
        $this->user->authUser($login, $remember,'WebLoginPE', true);
        $this->setFormStatus(true);
        $this->redirect();
        $this->setFields($this->user->toArray());
        $this->renderTpl = $this->getCFGDef('successTpl');
    }
}