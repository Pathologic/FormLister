<?php namespace FormLister;
/**
 * Контроллер для регистрации пользователя
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Register extends Form {
    public $user = null;

    public function __construct($modx, $cfg = array()) {
        parent::__construct($modx, $cfg);
        $this->user = new \modUsers($modx);
        $this->lexicon->loadLang('register');
    }

    public function render()
    {
        if ($this->modx->getLoginUserID('web')) {
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl');
            $this->setFormStatus(true);
        };
        return parent::render();
    }

    public function validateForm() {
        $result = parent::validateForm();
        if ($result && !is_null($this->user)) {
            $this->user->set('email', $this->getField('email'));
            $checkEmail = $this->user->checkUnique('web_user_attributes', 'email', 'internalKey');
            if (!$checkEmail) {
                $this->addError(
                    "email",
                    "unique",
                    $this->lexicon->getMsg('register.email_in_use')
                );
            }
            if ($username = $this->getField('username') != '') {
                $this->user->set('username',$username);
                $checkUser = $this->user->checkUnique('web_users', 'username');
                if (!$checkUser) {
                    $this->addError(
                        "username",
                        "unique",
                        $this->lexicon->getMsg('register.username_in_use')
                    );
                }
            }
        }
        return $this->isValid();
    }

    public function process() {
        if($this->checkSubmitProtection() || $this->checkSubmitLimit()) return false;
        //регистрация без логина, по емейлу
        if ($this->getField('username') == '') $this->setField('username', $this->getField('email'));
        //регистрация со случайным паролем
        if ($this->getField('password') == '') $this->setField('password',\APIHelpers::genPass(8));
        $result = $this->user->create($this->getFormData('fields'))->save(true);
        if (!$result) {
            $this->addFormMessage($this->lexicon->getMsg('register.registration_failed'));
            $this->setFormStatus(false);
            return false;
        } else {
            $this->addWebUserToGroups($this->user->getID(),$this->getCFGDef('userGroups'));
            parent::process();
        }
    }

    public function addWebUserToGroups($uid = 0, $groups = '') {
        if ($groups == '' || !$uid) return;
        $groups = explode('||',$groups);
        foreach ($groups as &$group) {
            $group = $this->modx->db->escape(trim($group));
        }
        $groups = "'".implode("','", $groups)."'";
        $groupNames = $this->modx->db->query("SELECT `id` FROM ".$this->modx->getFullTableName('webgroup_names')." WHERE `name` IN (".$groups.")");
        while ($row = $this->modx->db->getRow($groupNames)) {
            $webGroupId = $row['id'];
            $this->modx->db->query("REPLACE INTO ".$this->modx->getFullTableName('web_groups')." (`webgroup`, `webuser`) VALUES ('".$webGroupId."', '".$uid."')");
        }
    }
}