<?php namespace FormLister;
/**
 * Контроллер для редактирования профиля
 */
if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');

class Profile extends Core {

    public $userdata = null;

    public function __construct($modx, $cfg = array()) {
        parent::__construct($modx, $cfg);
        $this->lexicon->loadLang('profile');
        $uid = $modx->getLoginUserId();
        if ($uid) {
            $user = new \modUsers($modx);
            $this->userdata = $user->edit($uid);
            $userdata = $this->userdata->toArray();
            $this->config->setConfig(array(
                'defaults'=>$userdata
            ));
            $this->allowedFields = array('password','email');
        }
    }

    public function render()
    {
        if (is_null($this->userdata)) {
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->lexicon->getMsg('profile.default_skipTpl'));
        }
        return parent::render();
    }

    public function getValidationRules() {
        parent::getValidationRules();
        $password = $this->getField('password');
        if (empty($password)) {
            if (isset($this->rules['password'])) unset($this->rules['password']);
            if (isset($this->rules['repeatPassword'])) unset($this->rules['repeatPassword']);
        } else {
            if (isset($rules['repeatPassword']['equals'])) {
                $rules['repeatPassword']['equals']['params'] = $this->getField('password');
            }
        }
    }

    public static function uniqueEmail ($fl,$value) {
        $result = true;
        if (!is_null($fl->userdata) && ($fl->userdata->get("email") !== $value)) {
            $fl->userdata->set('email',$value);
            $result = $fl->userdata->checkUnique('web_user_attributes', 'email', 'internalKey');
        }
        return $result;
    }

    public function process() {
        $newpassword = $this->getField('password');
        $password = $this->userdata->get('password');
        $fields = $this->filterFields($this->getFormData('fields'),$this->allowedFields,$this->forbiddenFields);
        $result = $this->userdata->fromArray($fields)->save(true);
        if ($result) {
            $this->setFormStatus(true);
            if (!empty($newpassword) && ($password !== $this->userdata->getPassword($newpassword))) {
                $this->userdata->logOut('WebLoginPE', true);
                $this->redirect('exitTo');
            }
            $this->redirect();
            if ($successTpl = $this->getCFGDef('successTpl')) {
                $this->renderTpl= $successTpl;
            } else {
                $this->addMessage($this->lexicon->getMsg('profile.update_success'));
            }
        } else {
            $this->addMessage($this->lexicon->getMsg('profile.update_failed'));
        }
    }
}