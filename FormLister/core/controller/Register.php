<?php namespace FormLister;
/**
 * Контроллер для регистрации пользователя
 */
if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Register extends Form {
    public $user = null;

    public function __construct($modx, $cfg = array()) {
        parent::__construct($modx, $cfg);
        $this->user = new \modUsers($modx);
        $this->lexicon->loadLang('register');
        $this->allowedFields = array('username','email','password');
    }

    public function render()
    {
        if ($uid = $this->modx->getLoginUserID('web')) {
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl',$this->lexicon->getMsg('register.default_skipTpl'));
        };
        return parent::render();
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
     * Custom validation rule
     * Проверяет уникальность email
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueEmail ($fl,$value) {
        $result = true;
        if (!is_null($fl->user)) {
            $fl->user->set('email',$value);
            $result = $fl->user->checkUnique('web_user_attributes', 'email', 'internalKey');
        }
        return $result;
    }

    /**
     * Custom validation rule
     * Проверяет уникальность имени пользователя
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueUsername ($fl,$value) {
        $result = true;
        if (!is_null($fl->user)) {
            $fl->user->set('username', $value);
            $result = $fl->user->checkUnique('web_users', 'username');
        }
        return $result;
    }

    public function process() {
        //регистрация без логина, по емейлу
        if ($this->getField('username') == '') {
            $this->setField('username', $this->getField('email'));
        }
        if ($this->checkSubmitLimit() || $this->checkSubmitProtection()) return;
        //регистрация со случайным паролем
        if ($this->getField('password') == '' && !isset($this->rules['password'])) {
            $this->setField('password',\APIhelpers::genPass(6));
        }
        $fields = $this->filterFields($this->getFormData('fields'),$this->allowedFields,$this->forbiddenFields);
        $result = $this->user->create($fields)->save(true);
        if (!$result) {
            $this->addMessage($this->lexicon->getMsg('register.registration_failed'));
        } else {
            $this->addWebUserToGroups($this->user->getID(),$this->getCFGDef('userGroups'));
            parent::process();
        }
    }

    public function postProcess()
    {
        $this->redirect();
        $this->setFormStatus(true); //результат отправки писем значения не имеет
        $this->renderTpl = $this->getCFGDef('successTpl',$this->lexicon->getMsg('register.default_successTpl'));
    }

    /**
     * Добавляет пользователя в группы
     * @param int $uid
     * @param string $groups
     */
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