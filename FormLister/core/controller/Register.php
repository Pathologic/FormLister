<?php namespace FormLister;
/**
 * Контроллер для регистрации пользователя
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Register extends Form {
    public function render()
    {
        if ($this->modx->getLoginUserID()) {
            if ($redirect = $this->getCFGDef('redirectTo',false)) $this->modx->sendRedirect($this->modx->makeUrl($redirect), 0, 'REDIRECT_HEADER', 'HTTP/1.1 307 Temporary Redirect');
            return;
        };
        return parent::render();
    }
}