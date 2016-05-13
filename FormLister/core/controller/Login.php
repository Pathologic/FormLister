<?php namespace FormLister;

/**
 * Контроллер для авторизации пользователя
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Login extends Core
{
    public $user = null;
    
    public  function __construct($modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        $this->user = new \modUsers($this->modx); 
    }

    public function render() {
        if ($uid = $this->modx->getLoginUserID('web')) {
            $this->redirect();
            $user = $this->user->edit($uid);
            if ($user !== false) {
                $this->setFields($user->toArray());
            }
            $this->renderTpl = $this->getCFGDef('successTpl');
            $this->setFormStatus(true);
        };
        return parent::render();
    }

    public function process() {
        $login = $this->getField($this->getCFGDef('loginField','username'));
        $password = $this->getCFGDef('passwordField','password');
        $remember = $this->getCFGDef('rememberField','rememberme');
        if ($this->user->checkBlock($login)) {
            $this->addMessage('Пользователь заблокирован. Обратитесь к администратору сайта.');
            return;
        }
        $auth = $this->user->testAuth($login,$this->getField($password),false);
        if (!$auth) {
            $this->addMessage('Неверное имя пользователя или пароль.');
            return;
        }
        $this->user->authUser($login, $this->getField($remember),'WebLoginPE', true);
        $this->setFormStatus(true);
        $this->redirect();
        $this->setFields($this->user->toArray());
        $this->renderTpl = $this->getCFGDef('successTpl');
    }
}