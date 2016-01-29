<?php namespace FormLister;

include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Login extends FormLister
{
    public function render()
    {
        if ($this->modx->getLoginUserID()) return;
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
            if (!$this->getCFGDef('api',0)) $this->modx->sendRedirect($this->modx->makeUrl($this->getCFGDef('redirectTo',$this->modx->documentIdentifier)), 0, 'REDIRECT_HEADER', 'HTTP/1.1 307 Temporary Redirect');
        }

    }
}