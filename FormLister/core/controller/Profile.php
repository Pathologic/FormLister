<?php namespace FormLister;
/**
 * Контроллер для редактирования профиля
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Profile extends FormLister {
    public $userdata = null;
    public function __construct($modx, $cfg = array()) {
        $uid = $modx->getLoginUserId();
        if ($uid) {
            $user = new \modUsers($modx);
            $this->userdata = $user->edit($uid);
            $cfg = array_merge($cfg,array(
                'defaults'=>json_encode($this->userdata->toArray())
            ));
        }
        parent::__construct($modx, $cfg);
        if (!$uid) {
            $modx->sendRedirect($modx->makeUrl($this->getCFGDef('redirectTo',$modx->config['site_start'])));
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
                $this->isValid = false;
                $this->addError(
                    "email",
                    "unique",
                    "Вы не можете использовать этот e-mail"
                );
                $result = false;
            }
        }
        return $result;
    }

    public function process() {
        $newpassword = $this->getField('password');
        if (!is_null($this->userdata)) {
            $password = $this->userdata->get('password');
            $result = $this->userdata->fromArray($this->getFormFields())->save();
            if ($result) {
                if (!empty($newpassword) && ($password !== $this->userdata->getpassword($newpassword))) $this->userdata->logOut();
                $this->modx->sendRedirect($this->modx->makeUrl($this->getCFGDef('redirectTo',$this->modx->config['site_start'])));
            } else {
                $this->addMessage('Не удалось сохранить.');
            }
        }
    }
}