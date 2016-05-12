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
        if ($this->modx->getLoginUserID('web')) {
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl');
            $this->setFormStatus(true);
        };
        return parent::render();
    }

    public function process() {
        $user = $this->user->edit($this->getField($this->getCFGDef('loginField','username')));
        if ($user !== false) {
            $uid = $user->getID();
            if ($user->checkBlock($uid)) {
                $this->addMessage('Пользователь заблокирован. Обратитесь к администратору сайта.');
                return;
            }
            $auth = $user->testAuth($uid,$this->getField($this->getCFGDef('passwordField','password')),false);
            if (!$auth) {
                $this->addMessage('Неверное имя пользователя или пароль.');
                return;
            }
            $user->authUser($uid, true);
            $this->setFormStatus(true);
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl');
        }
    }
}