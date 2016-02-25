<?php namespace FormLister;
/**
 * Контроллер для обычных форм, типа обратной связи
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
class Form extends Core
{
    public function process() {
        //если сработала защита, то не отправляем
        if($this->checkSubmitProtection() || $this->checkSubmitLimit()) return false;

        $this->setField('form.date',date($this->getCFGDef('dateFormat','m.d.Y H:i:s')));
        if ($this->sendForm()) {
            $this->setFormStatus(true);
            $this->setSubmitProtection();
            if ($redirectTo = $this->getCFGDef('redirectTo',0)) {
                $this->modx->sendRedirect($this->modx->makeUrl($redirectTo), 0, 'REDIRECT_HEADER', 'HTTP/1.1 307 Temporary Redirect');
            } else {
                $this->renderTpl = $this->getCFGDef('successTpl','@CODE:Форма успешно отправлена [+form.date+]');
            }
        }
    }
}