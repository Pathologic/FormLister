<?php namespace FormLister;
/**
 * Контроллер для обычных форм, типа обратной связи
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
class Form extends Core
{
    /**
     * Проверка повторной отправки формы
     * @return bool
     */
    public function checkSubmitProtection()
    {
        $result = false;
        if ($protectSubmit = $this->getCFGDef('protectSubmit', 1)) {
            $hash = $this->getFormHash();
            if (isset($_SESSION[$this->formid . '_hash']) && $_SESSION[$this->formid . '_hash'] == $hash && $hash != '') {
                $result = true;
                $this->addMessage('Данные успешно отправлены. Нет нужды отправлять данные несколько раз.');
            }
        }
        return $result;
    }

    /**
     * Проверка повторной отправки в течение определенного времени, в секундах
     * @return bool
     */
    public function checkSubmitLimit()
    {
        $submitLimit = $this->getCFGDef('submitLimit', 60);
        $result = false;
        if ($submitLimit > 0) {
            if (time() < $submitLimit + $_SESSION[$this->formid . '_limit']) {
                $result = true;
                $this->addMessage('Вы уже отправляли эту форму, попробуйте еще раз через ' . round($submitLimit / 60, 0) . ' мин.');
            } else {
                unset($_SESSION[$this->formid . '_limit'], $_SESSION[$this->formid . '_hash']);
            } //time expired
        }
        return $result;
    }

    public function setSubmitProtection()
    {
        if ($this->getCFGDef('submitLimit', 1)) {
            $_SESSION[$this->formid . '_hash'] = $this->getFormHash();
        } //hash is set earlier
        if ($this->getCFGDef('submitLimit', 60) > 0) {
            $_SESSION[$this->formid . '_limit'] = time();
        }
    }

    public function getFormHash()
    {
        $hash = '';
        $protectSubmit = $this->getCFGDef('protectSubmit', 1);
        if (!is_numeric($protectSubmit)) { //supplied field names
            $protectSubmit = explode(',', $protectSubmit);
            foreach ($protectSubmit as $field) {
                $hash .= $this->getField($field);
            }
        } else //all required fields
        {
            foreach ($this->rules as $field => $rules) {
                foreach ($rules as $rule => $description) {
                    if ($rule == 'required') {
                        $hash .= $this->getField($field);
                    }
                }
            }
        }
        if ($hash) {
            $hash = md5($hash);
        }
        return $hash;
    }

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
                $this->renderTpl = $this->getCFGDef('successTpl','@CODE:Форма успешно отправлена [+form.date.value+]');
            }
        }
    }
}