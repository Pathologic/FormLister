<?php namespace FormLister;

/**
 * Контроллер для восстановления паролей
 */
if (!defined('MODX_BASE_PATH')) die();

include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');

class Reminder extends Form {
    protected $user = null;

    protected $mode = 'hash';
    protected $userField = '';
    protected $uidField = '';
    protected $hashField = '';

    public function __construct(\DocumentParser $modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        $this->user = new \modUsers($modx);
        $this->lexicon->loadLang('reminder');
        $hashField = $this->getCFGDef('hashField','hash');
        $uidField = $this->getCFGDef('uidField','id');
        $userField = $this->getCFGDef('userField','email');
        $this->hashField = $hashField;
        $this->uidField = $uidField;
        $this->userField = $userField;
        $this->config->setConfig(array(
            'allowedFields' => implode(',',array($uidField,$userField,$hashField,$this->getCFGDef('allowedFields')))
        ));
        if ((isset($_REQUEST[$hashField]) && !empty($_REQUEST[$hashField])) && (isset($_REQUEST[$uidField]) && !empty($_REQUEST[$uidField]))) {
            $this->mode = 'reset';
            $this->config->setConfig(array(
                'rules' => $this->getCFGDef('resetRules'),
                'reportTpl' => $this->getCFGDef('resetReportTpl'),
                'submitLimit' => 0
            ));
        }
    }

    public function render() {
        if ($uid = $this->modx->getLoginUserID('web')) {
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl',$this->lexicon->getMsg('reminder.default_skipTpl'));
            return parent::render();
        };
        $out = '';
        switch ($this->mode) {
            case 'hash':
                $out = $this->renderHash();
                break;
            case 'reset':
                $out = $this->renderReset();
                break;
        }
        if ($out) return parent::render();
    }

    public function renderReset() {
        $hash = $this->getField($this->hashField);
        $uid = $this->getField($this->uidField);
        if ($hash && $hash == $this->getUserHash($uid)) {
            if ($this->getCFGDef('resetTpl')) {
                $this->setField('user.hash', $hash);
                $this->renderTpl = $this->getCFGDef('resetTpl');
            } else {
                $this->process();
            }
        }
        return true;
    }

    public function renderHash() {
        return true;
    }

    public function getValidationRules()
    {
        parent::getValidationRules();
        $rules = &$this->rules;
        if (isset($rules['password']) && isset($rules['repeatPassword']) && !empty($this->getField('password'))) {
            if (isset($rules['repeatPassword']['equals'])) {
                $rules['repeatPassword']['equals']['params'] = $this->getField('password');
            }
        }
    }

    /**
     * @param $uid
     * @return bool|string
     */
    public function getUserHash($uid) {
        $userdata = $this->user->edit($uid)->toArray();
        $hash = $userdata['id'] ? md5(json_encode($userdata)) : false;
        return $hash;
    }

    public function process() {
        switch ($this->mode) {
            /**
             * Задаем хэш, отправляем пользователю ссылку для восстановления пароля
             */
            case "hash":
                $uid = $this->getField($this->userField);
                if ($hash = $this->getUserHash($uid)) {
                    $this->setFields($this->user->toArray());
                    $url = $this->getCFGDef('resetTo',$this->modx->documentIdentifier);
                    $this->setField('reset.url',$this->modx->makeUrl($url,"","&{$this->uidField}={$this->getField($this->uidField)}&{$this->hashField}={$hash}",'full'));
                    $this->mailConfig['to'] = $this->user->edit($uid)->get('email');
                    parent::process();
                } else {
                    $this->addMessage($this->lexicon->getMsg('reminder.users_only'));
                }
                break;
            /**
             * Если пароль не задан, то создаем пароль
             * Отправляем пользователю письмо с паролем, если указан шаблон такого письма
             * Если не указан, то запрещаем отправку письма, пароль будет показан на экране
             */
            case "reset":
                $uid = $this->getField($this->uidField);
                $hash = $this->getField($this->hashField);
                if ($hash && $hash == $this->getUserHash($uid)) {
                    if ($this->getField('password') == '' && !isset($this->rules['password'])) $this->setField('password',\APIhelpers::genPass(6));
                    $result = $this->user->edit($uid)->fromArray($this->getFormData('fields'))->save(true);
                    if (!$result) {
                        $this->addMessage($this->lexicon->getMsg('reminder.update_failed'));
                    } else {
                        $this->setField('newpassword',$this->getField('password'));
                        $this->setFields($this->user->toArray());
                        if ($this->getCFGDef('resetReportTpl')) {
                            $this->mailConfig['to'] = $this->getField('email');
                        }
                        parent::process();
                    }
                } else {
                    $this->addMessage($this->lexicon->getMsg('reminder.update_failed'));
                    parent::process();
                }
                break;
        }
    }

    public function postProcess() {
        $this->setFormStatus(true);
        switch ($this->mode) {
            case "hash":
                $this->renderTpl = $this->getCFGDef('successTpl',
                    $this->lexicon->getMsg('reminder.default_successTpl'));
                break;
            case "reset":
                $this->redirect();
                $this->renderTpl = $this->getCFGDef('resetSuccessTpl',
                    $this->lexicon->getMsg('reminder.default_resetSuccessTpl'));
        }
    }
}