<?php namespace FormLister;
/**
 * Контроллер для редактирования профиля
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');

class Profile extends Core {

    public $userdata = null;

    public function __construct($modx, $cfg = array()) {
        parent::__construct($modx, $cfg);
        $uid = $modx->getLoginUserId();
        if ($uid) {
            $user = new \modUsers($modx);
            $this->userdata = $user->edit($uid);
            $this->config->setConfig(array(
                'defaults'=>$this->userdata->toArray()
            ));
        } else {
            $this->modx->sendUnauthorizedPage(true);
        }
    }

    public function getValidationRules() {
        parent::getValidationRules();
        $password = $this->getField('password');
        if (!empty($password)) {
            $this->rules = array_merge($this->rules,array(
                "password" => array(
                    "minLength" => array(
                        "params" => array(6),
                        "message" => "Пароль должен быть больше 6 символов"
                    )
                ),
                "repeatpassword" => array(
                    "required" => "Введите новый пароль еще раз",
                    "equals" => array(
                        "params" => array($password),
                        "message" => "Пароли не совпадают"
                    )
                )
            ));
        }
    }
    public function validateForm() {
        $result = parent::validateForm();
        if ($result && !is_null($this->userdata) && ($this->userdata->get("email") !== $this->getField('email'))) {
            $this->userdata->set('email',$this->getField('email'));
            $checkEmail = $this->userdata->checkUnique('web_user_attributes', 'email', 'internalKey');
            if (!$checkEmail) {
                $this->addError(
                    "email",
                    "unique",
                    "Вы не можете использовать этот e-mail"
                );
            }
        }
        return $this->isValid();
    }

    public function process() {
        $newpassword = $this->getField('password');
        if (!is_null($this->userdata)) {
            $password = $this->userdata->get('password');
            $result = $this->userdata->fromArray($this->getFormData('fields'))->save();
            if ($result) {
                if (!empty($newpassword) && ($password !== $this->userdata->getpassword($newpassword))) $this->userdata->logOut();
                $this->modx->sendRedirect($this->modx->makeUrl($this->getCFGDef('redirectTo',$this->modx->config['site_start'])));
            } else {
                $this->addMessage('Не удалось сохранить.');
            }
        }
    }
}