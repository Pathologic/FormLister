<?php namespace FormLister;

include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
class Form extends FormLister
{
    public function process() {
        $this->setField('form.date',date($this->getCFGDef('dateFormat','m.d.Y H:i:s')));
        if ($this->sendForm()) {
            if ($redirectTo = $this->getCFGDef('redirectTo',0)) {
                $this->modx->sendRedirect($this->modx->makeUrl($redirectTo), 0, 'REDIRECT_HEADER', 'HTTP/1.1 307 Temporary Redirect');
            } else {
                $this->renderTpl = $this->getCFGDef('successTpl','@CODE:Форма успешно отправлена [+form.date+]');
            }
        }
    }
}