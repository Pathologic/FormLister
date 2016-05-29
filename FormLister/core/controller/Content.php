<?php namespace FormLister;
/**
 * Контроллер для создания записей
 */
if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
class Content extends Form
{
    protected $mode = 'create';
    protected $id = 0;
    protected $owner = 0;
    /**
     * @var \autoTable $content
     */
    public $content = null;
    public $user = null;

    public function __construct($modx, $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $lang = $this->lexicon->loadLang('content');
        if ($lang) $this->log('Lexicon loaded',array('lexicon'=>$lang));
        $classname = $this->getCFGDef('contentClass','modResource');
        if ($this->loadContentClass()) {
            $this->content = new $classname($modx);
        }
        $this->user = new \modUsers($modx);
        $idField = $this->getCFGDef('idField','id');
        if ($idField && isset($_REQUEST[$idField]) && is_scalar($_REQUEST[$idField]) && $_REQUEST[$idField] > 0) {
            $this->id = $_REQUEST[$idField];
            $this->mode = 'edit';
            $data = $this->content->edit($this->id)->toArray();
            if ($ds = $this->getCFGDef('defaultsSources')) {
                $defaultsSources = "{$ds};array";
            } else {
                $defaultsSources = 'array';
            }
            $this->config->setConfig(array(
                'defaultsSources' => $defaultsSources,
                'defaults' => $data,
                'submitLimit' => 0,
                'protectSubmit' => 0
            ));
            $this->mailConfig['noemail'] = 1;
        }
        $this->log('Content mode is '.$this->mode, array('data'=>$data));
    }

    public function loadContentClass() {
        $out = false;
        $classname = $this->getCFGDef('contentClass','modResource');
        if (!class_exists($classname)) {
            $classPath = $this->getCFGDef('classPath','assets/lib/MODxAPI/modResource.php');
            if ($this->fs->checkFile($classPath)) {
                include_once(MODX_BASE_PATH . $classPath);
                $out = class_exists($classname);
            }
        }
        $this->log('Load content class '.$classname,array('result'=>$out));
        return $out;
    }

    public function render() {
        $uid = $this->modx->getLoginUserID('web');
        $owner = $this->getCFGDef('ownerField','aid');
        $mode = $this->mode;

        //Если пользователь не авторизован и запрет для анонимов создавать записи
        if (!$uid && $this->getCFGDef('onlyUsers',1) && $mode == 'create') {
            $this->redirect('exitTo');
            $this->renderTpl=$this->getCFGDef('skipTpl',$this->lexicon->getMsg('create.default_skipTpl'));
        }

        //Если пользователь не авторизован и пытается редактировать запись
        if (!$uid && $mode == 'edit') {
            $this->redirect('exitTo');
            $this->renderTpl=$this->getCFGDef('skipEditTpl',$this->lexicon->getMsg('edit.default_skipEditTpl'));
        }

        //Если пользователь авторизован, но не состоит в разрешенных группах
        if ($uid && $this->getCFGDef('onlyUsers',1) && !$this->checkUserGroups($uid,$this->getCFGDef('userGroups'))) {
            if ($mode == 'edit') {
                $this->redirect('badGroupTo');
                $this->renderTpl = $this->getCFGDef('badGroupTpl',$this->lexicon->getMsg('edit.default_badGroupTpl'));
            } else {
                $this->redirect('badRecordTo');
                $this->renderTpl = $this->getCFGDef('badGroupTpl',$this->lexicon->getMsg('create.default_badGroupTpl'));
            }
        }

        if ($uid) {
            $this->owner = $uid; //владелец документа
            $userdata = $this->user->edit($uid)->toArray();
            if ($userdata['id']) {
                $this->setFields($userdata,'user');
                $this->log('Set user data',array('data'=>$userdata));
            }
        }

        if ($mode == 'edit') {
            $cid = $this->content->getID();
            if ($cid) {
                if ($this->getCFGDef('onlyAuthors',1) && ($this->content->get($owner) && $this->content->get($owner) != $uid)) {
                    $this->redirect('badOwnerTo');
                    $this->renderTpl = $this->getCFGDef('badOwnerTpl',$this->lexicon->getMsg('edit.default_badOwnerTpl'));
                } else {
                    return parent::render();
                }
            } else {
                $this->redirect('badRecordTo');
                $this->renderTpl = $this->getCFGDef('badRecordTpl',$this->lexicon->getMsg('edit.default_badRecordTpl'));
            }
        }
        return parent::render();
    }

    public function process() {
        $fields = $this->getContentFields();
        $owner = $this->getCFGDef('ownerField','aid');
        $result = false;
        if ($fields  && !is_null($this->content)) {
            $clearCache = $this->getCFGDef('clearCache',false);
            switch ($this->mode) {
                case 'create':
                    if ($this->checkSubmitProtection()) return;
                    if ($this->owner) $fields[$owner] = $this->owner;
                    $result = $this->content->create($fields)->save(true,$clearCache);
                    $this->log('Create record',array('data'=>$fields,'result'=>$result));
                    break;
                case 'edit':
                    $result = $this->content->fromArray($fields)->save(true,$clearCache);
                    $this->log('Update record',array('data'=>$fields,'result'=>$result));
                    break;
                default:
                    break;
            }
            //чтобы не получился косяк, когда плагины обновят поля
            $this->content->close();
            $this->setFields($this->content->edit($this->getField('id'))->toArray());
            $this->log('Update form data',array('data'=>$this->getFormData('fields')));
        }
        if (!$result) {
            $this->addMessage($this->lexicon->getMsg('edit.update_fail'));
        } else {
            if ($this->mode == 'create') $this->setField('content.url',$this->modx->makeUrl($result,'','','full'));
            parent::process();
        }
    }

    public function postProcess()
    {
        $this->setFormStatus(true);
        if ($this->mode == 'create') {
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl',$this->lexicon->getMsg('create.default_successTpl'));
        } else {
            $this->addMessage($this->lexicon->getMsg('edit.update_success'));
        }
    }

    /**
     * @return array
     */
    public function getContentFields() {
        $fields = array();
        $contentFields = $this->config->loadArray($this->getCFGDef('contentFields'));
        foreach ($contentFields as $field => $formField) {
            $formField = $this->getField($formField);
            if ($formField !== '' || $this->getCFGDef('allowEmptyFields',1)) $fields[$field] = $formField;
        }
        return $fields;
    }

    public function getMode() {
        return $this->mode;
    }

    public function checkUserGroups($uid, $groups = '') {
        $flag = true;
        if (is_scalar($groups) && !empty($groups)) {
            $groups = explode(';', $groups);
            if (!empty($groups)) {
                $userGroups = $this->user->getUserGroups($uid);
                $flag = !empty(array_intersect($groups, $userGroups));
            }
        }
        $this->log('Check user groups',array('result'=>$flag));
        return $flag;
    }
}