<?php namespace FormLister;

/**
 * Контроллер для авторизации пользователя
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Login extends Core
{
    public function render()
    {
        if ($this->modx->getLoginUserID('web')) {
            if ($redirect = $this->getCFGDef('redirectTo',false)) $this->modx->sendRedirect($this->modx->makeUrl($redirect), 0, 'REDIRECT_HEADER', 'HTTP/1.1 307 Temporary Redirect');
            $this->renderTpl = $this->getCFGDef('successTpl');
        };
        return parent::render();
    }

    public function process() {
        $_user = new \modUsers($this->modx);
        $user = $_user->edit($this->getField($this->getCFGDef('loginField','username')));
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
            if (!$this->getCFGDef('api',0) && $redirect = $this->getCFGDef('redirectTo',false)) $this->modx->sendRedirect($this->modx->makeUrl($redirect), 0, 'REDIRECT_HEADER', 'HTTP/1.1 307 Temporary Redirect');
            $this->renderTpl = $this->getCFGDef('successTpl');
        }

    }
}